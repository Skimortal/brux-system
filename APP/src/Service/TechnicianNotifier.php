<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\AppointmentTechnician;
use App\Entity\AppointmentVolunteer;
use App\Entity\Contact;
use App\Entity\DigestItem;
use App\Entity\User;
use App\Enum\DigestKind;
use App\Enum\EmailTemplateKey;
use App\Enum\TechnicianStatus;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * E-mails around technician assignments: to the technician when assigned or confirmed and
 * for open calls, to the office when a technician answers. Volunteers hear when assigned.
 * News for technicians and volunteers waits for the daily digest unless it is short notice.
 */
class TechnicianNotifier
{
    private const FROM = 'no-reply@brux.at';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly MailTemplateRenderer $templates,
        private readonly UrlGeneratorInterface $urls,
        private readonly UriSigner $signer,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
        private readonly AppSettings $settings,
        private readonly UserRepository $users,
        private readonly TechnicianDigest $digest,
    ) {
    }

    /**
     * Mail for a new status of one person's assignments (several for a series), about the
     * first one still to come; past appointments stay quiet.
     *
     * @param list<AppointmentTechnician> $assignments same person and status, by date
     */
    public function statusChanged(array $assignments): void
    {
        $now = $this->clock->now();
        $upcoming = array_values(array_filter($assignments, static fn (AppointmentTechnician $a) => $a->getAppointment()?->getEndDate() !== null && $a->getAppointment()->getEndDate() >= $now));
        if ($upcoming === []) {
            return;
        }
        $assignment = $upcoming[0];
        $appointment = $assignment->getAppointment();
        $contact = $assignment->getContact();
        if ($contact === null) {
            return;
        }
        $kind = match ($assignment->getStatus()) {
            TechnicianStatus::ASSIGNED => DigestKind::ASSIGNED,
            TechnicianStatus::CONFIRMED => DigestKind::CONFIRMED,
            // answers go to the office right away
            default => null,
        };
        if ($kind !== null && $this->digest->defers($appointment)) {
            $this->digest->queue($kind, $contact, $appointment, \count($upcoming) - 1);

            return;
        }
        $context = [
            'name' => $contact->getName(),
            'appointment' => $this->describe($appointment, $assignment),
            // further dates, e.g. of a series
            'more' => \count($upcoming) - 1,
        ];

        // without an account the mail itself carries the answers; with one it leads to their area
        $account = $this->users->findOneBy(['contact' => $contact]) !== null;
        $links = $account
            ? ['url' => $this->technicianUrl($appointment)]
            : ['url' => null, 'accept_url' => $this->answerUrl($assignment, TechnicianStatus::ACCEPTED), 'decline_url' => $this->answerUrl($assignment, TechnicianStatus::DECLINED)];

        match ($assignment->getStatus()) {
            TechnicianStatus::ASSIGNED => $this->send(EmailTemplateKey::TECHNICIAN_ASSIGNED, $contact->getEmail(), $context + $links),
            TechnicianStatus::CONFIRMED => $this->send(EmailTemplateKey::TECHNICIAN_CONFIRMED, $contact->getEmail(), $context + ['url' => $links['url']]),
            default => $this->send(EmailTemplateKey::TECHNICIAN_ANSWERED, $this->settings->officeEmail(), $context + [
                'status' => $assignment->getStatus()->label(),
                'url' => $this->officeUrl($appointment),
            ], replyTo: $contact->getEmail()),
        };
    }

    /**
     * Mail to a volunteer newly assigned (several for a series), about the first one still to
     * come; past appointments stay quiet. Only for information, there is nothing to answer.
     *
     * @param list<AppointmentVolunteer> $assignments same person, by date
     */
    public function volunteerAssigned(array $assignments): void
    {
        $now = $this->clock->now();
        $upcoming = array_values(array_filter($assignments, static fn (AppointmentVolunteer $a) => $a->getAppointment()?->getEndDate() !== null && $a->getAppointment()->getEndDate() >= $now));
        if ($upcoming === [] || ($contact = $upcoming[0]->getContact()) === null) {
            return;
        }
        if ($this->digest->defers($upcoming[0]->getAppointment())) {
            $this->digest->queue(DigestKind::VOLUNTEER, $contact, $upcoming[0]->getAppointment(), \count($upcoming) - 1);

            return;
        }
        $tasks = array_map(static fn ($task) => $task->label(), $upcoming[0]->getTaskEnums());
        $this->send(EmailTemplateKey::VOLUNTEER_ASSIGNED, $contact->getEmail(), [
            'name' => $contact->getName(),
            'appointment' => ['tasks' => $tasks !== [] ? implode(', ', $tasks) : null] + $this->describe($upcoming[0]->getAppointment(), null),
            'more' => \count($upcoming) - 1,
        ]);
    }

    /**
     * Sends the open call to the given technicians, or adds it to their digest.
     *
     * @param iterable<Contact> $contacts
     *
     * @return int how many people get it (by mail now or with the digest)
     */
    public function openCall(Appointment $appointment, iterable $contacts): int
    {
        $sent = 0;
        $deferred = $this->digest->defers($appointment);
        foreach ($contacts as $contact) {
            if ($deferred) {
                $this->digest->queue(DigestKind::OPEN_CALL, $contact, $appointment);
                ++$sent;
                continue;
            }
            $sent += $this->send(EmailTemplateKey::TECHNICIAN_OPEN_CALL, $contact->getEmail(), [
                'name' => $contact->getName(),
                'appointment' => $this->describe($appointment, null),
                'interest_url' => $this->interestUrl($appointment, $contact),
                // people with an account also find all open calls in their area
                'area_url' => $this->users->findOneBy(['contact' => $contact]) !== null ? $this->urls->generate('app_technician', [], UrlGeneratorInterface::ABSOLUTE_URL) : null,
            ]) ? 1 : 0;
        }

        return $sent;
    }

    /**
     * The daily digest for one person: what is still true of the news collected for them.
     *
     * @param list<DigestItem> $items same person, earliest appointment first
     *
     * @return bool whether a mail went out (false when nothing is left to tell)
     */
    public function digest(Contact $contact, array $items): bool
    {
        $now = $this->clock->now();
        $account = $this->users->findOneBy(['contact' => $contact]) !== null;
        $sections = ['assigned' => [], 'confirmed' => [], 'open_calls' => [], 'volunteer' => []];
        $seen = [];
        foreach ($items as $item) {
            $appointment = $item->getAppointment();
            if ($appointment === null || $appointment->getEndDate() === null || $appointment->getEndDate() < $now || isset($seen[$item->getKind()->value . '|' . $appointment->getId()])) {
                continue;
            }
            $seen[$item->getKind()->value . '|' . $appointment->getId()] = true;
            $assignment = $appointment->getAssignmentOf($contact);
            $line = ['more' => $item->getMore()];
            // only what still holds: the office may have changed its mind since
            switch ($item->getKind()) {
                case DigestKind::ASSIGNED:
                    if ($assignment?->getStatus() !== TechnicianStatus::ASSIGNED) {
                        continue 2;
                    }
                    $sections['assigned'][] = $line + [
                        'appointment' => $this->describe($appointment, $assignment),
                        'accept_url' => $account ? null : $this->answerUrl($assignment, TechnicianStatus::ACCEPTED),
                        'decline_url' => $account ? null : $this->answerUrl($assignment, TechnicianStatus::DECLINED),
                    ];
                    break;
                case DigestKind::CONFIRMED:
                    if ($assignment?->getStatus() !== TechnicianStatus::CONFIRMED) {
                        continue 2;
                    }
                    $sections['confirmed'][] = $line + ['appointment' => $this->describe($appointment, $assignment)];
                    break;
                case DigestKind::OPEN_CALL:
                    if ($appointment->getOpenCallAt() === null || $assignment !== null) {
                        continue 2;
                    }
                    $sections['open_calls'][] = $line + [
                        'appointment' => $this->describe($appointment, null),
                        'interest_url' => $this->interestUrl($appointment, $contact),
                    ];
                    break;
                case DigestKind::VOLUNTEER:
                    $volunteer = null;
                    foreach ($appointment->getAppointmentVolunteers() as $candidate) {
                        if ($candidate->getContact() === $contact) {
                            $volunteer = $candidate;
                        }
                    }
                    if ($volunteer === null) {
                        continue 2;
                    }
                    $tasks = array_map(static fn ($task) => $task->label(), $volunteer->getTaskEnums());
                    $sections['volunteer'][] = $line + ['appointment' => ['tasks' => $tasks !== [] ? implode(', ', $tasks) : null] + $this->describe($appointment, null)];
                    break;
            }
        }
        $count = array_sum(array_map('count', $sections));
        if ($count === 0) {
            return false;
        }

        return $this->send(EmailTemplateKey::TECHNICIAN_DIGEST, $contact->getEmail(), $sections + [
            'name' => $contact->getName(),
            'count' => $count,
            // people with an account answer and find everything in their area
            'url' => $account ? $this->urls->generate('app_technician', [], UrlGeneratorInterface::ABSOLUTE_URL) : null,
        ]);
    }

    public function invite(User $user, string $setPasswordUrl, bool $board = false): bool
    {
        return $this->send($board ? EmailTemplateKey::BOARD_INVITATION : EmailTemplateKey::TECHNICIAN_INVITATION, $user->getEmail(), [
            'name' => $user->getFirstName(),
            'username' => $user->getUsername(),
            'set_password_url' => $setPasswordUrl,
            'login_url' => $this->urls->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }

    /** Link that answers an assignment without logging in. */
    public function answerUrl(AppointmentTechnician $assignment, TechnicianStatus $status): string
    {
        return $this->signer->sign($this->urls->generate('app_technician_answer', [
            'id' => $assignment->getId(),
            'status' => $status->value,
        ], UrlGeneratorInterface::ABSOLUTE_URL));
    }

    /** Link that registers interest in an open appointment without logging in. */
    public function interestUrl(Appointment $appointment, Contact $contact): string
    {
        return $this->signer->sign($this->urls->generate('app_technician_interest', [
            'id' => $appointment->getId(),
            'contact' => $contact->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL));
    }

    /**
     * Title, date and room of an appointment for the mails.
     *
     * @return array{title: string, date: string, when: string, room: ?string, tasks: ?string}
     */
    public function describe(Appointment $appointment, ?AppointmentTechnician $assignment): array
    {
        $performance = $appointment->getProductionEvent();
        $title = $performance !== null
            ? 'Vorstellung ' . $performance->getProduction()?->getTitle()
            : $appointment->getDisplayTitle();
        $start = \DateTimeImmutable::createFromInterface($appointment->getStartDate());
        $end = \DateTimeImmutable::createFromInterface($appointment->getEndDate());
        $day = (new \IntlDateFormatter('de_AT', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, null, null, 'EEEE, d. MMMM y'))->format($start);
        $when = $appointment->isAllDay() ? $day . ', ganztägig' : $day . ', ' . $start->format('H:i') . '–' . $end->format('H:i') . ' Uhr';
        $tasks = [];
        if ($assignment !== null) {
            foreach (['Licht' => $assignment->isLighting(), 'Ton' => $assignment->isSound(), 'Aufbau' => $assignment->isSetup()] as $task => $on) {
                if ($on) {
                    $tasks[] = $task;
                }
            }
        }

        return [
            'title' => $title,
            'date' => $start->format('d.m.Y'),
            'when' => $when,
            'room' => $appointment->getRoom()?->getName(),
            'tasks' => $tasks !== [] ? implode(', ', $tasks) : null,
        ];
    }

    private function technicianUrl(Appointment $appointment): string
    {
        return $this->urls->generate('app_technician', [
            'date' => $appointment->getStartDate()?->format('Y-m-d'),
            'panel' => self::panelOf($appointment),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function officeUrl(Appointment $appointment): string
    {
        return $this->urls->generate('app_calendar', [
            'date' => $appointment->getStartDate()?->format('Y-m-d'),
            'panel' => self::panelOf($appointment),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /** Calendar panel of an appointment: the performance it staffs, or itself. */
    public static function panelOf(Appointment $appointment): string
    {
        $performance = $appointment->getProductionEvent();

        return $performance !== null ? 'pe-' . $performance->getId() : 'a-' . $appointment->getId();
    }

    /** @param array<string, mixed> $context */
    private function send(EmailTemplateKey $key, ?string $to, array $context, ?string $replyTo = null): bool
    {
        if ($to === null || trim($to) === '') {
            return false;
        }
        try {
            $rendered = $this->templates->render($key, 'de', $context);
            $email = (new Email())
                ->from(new Address(self::FROM, 'BRUX Haussystem'))
                ->to(trim($to))
                ->replyTo($replyTo !== null && trim($replyTo) !== '' ? trim($replyTo) : $this->settings->officeEmail())
                ->subject($rendered->subject)
                ->html($rendered->html);
            if ($rendered->text) {
                $email->text($rendered->text);
            }
            $this->mailer->send($email);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Technician mail failed', ['key' => $key->value, 'to' => $to, 'exception' => $e]);

            return false;
        }
    }
}
