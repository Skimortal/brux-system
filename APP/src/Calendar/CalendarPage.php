<?php

namespace App\Calendar;

use App\Entity\Appointment;
use App\Entity\KeyLoan;
use App\Enum\AppointmentTypeEnum;
use App\Form\AppointmentType;
use App\Form\KeyLoanType;
use App\Form\PerformanceContactsType;
use App\Form\PerformanceType;
use App\Entity\EventPrice;
use App\Entity\Production;
use App\Entity\ProductionEvent;
use App\Enum\EventReservationStatus;
use App\Enum\EventStatus;
use App\Repository\ProductionRepository;
use App\Repository\AppointmentRepository;
use App\Repository\CleaningScheduleRepository;
use App\Repository\ContactRepository;
use App\Repository\KeyLoanRepository;
use App\Repository\KeyManagementRepository;
use App\Repository\ProductionEventRepository;
use App\Repository\RoomRepository;
use App\Repository\TodoRepository;
use App\Enum\ContactRole;
use App\Service\AppointmentManager;
use App\Service\BruxApiSyncService;
use App\Service\FieldOwnership;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Renders the calendar page with its side column: keys and todos, or the panel of the
 * selected entry. Actions re-render it with their invalid form on validation errors.
 */
final class CalendarPage
{
    public function __construct(
        private readonly CalendarBuilder $builder,
        private readonly Environment $twig,
        private readonly FormFactoryInterface $forms,
        private readonly UrlGeneratorInterface $urls,
        private readonly ClockInterface $clock,
        private readonly AppointmentRepository $appointments,
        private readonly ProductionEventRepository $performances,
        private readonly CleaningScheduleRepository $schedules,
        private readonly KeyManagementRepository $keys,
        private readonly KeyLoanRepository $loans,
        private readonly RoomRepository $rooms,
        private readonly TodoRepository $todos,
        private readonly ContactRepository $contacts,
        private readonly AppointmentManager $appointmentManager,
        private readonly FieldOwnership $ownership,
        private readonly BruxApiSyncService $sync,
        private readonly ProductionRepository $productions,
    ) {
    }

    /**
     * Calendar state for an action: taken from the "_return" URL the form carried.
     */
    public function queryFromReturn(Request $request): CalendarQuery
    {
        $return = (string) $request->request->get('_return', '');
        $query = [];
        if (str_starts_with($return, '/')) {
            parse_str((string) parse_url($return, \PHP_URL_QUERY), $query);
        }

        return CalendarQuery::fromRequest(new Request($query), $this->clock->now()->setTime(0, 0));
    }

    /**
     * Where a panel action returns to: the page the form came from (calendar or a
     * production page), with the overrides applied to its query.
     *
     * @param array<string, string|null> $overrides
     */
    public function returnUrl(Request $request, array $overrides = []): string
    {
        $return = (string) $request->request->get('_return', '');
        $path = parse_url($return, \PHP_URL_PATH);
        $local = \is_string($path) && str_starts_with($path, '/') && !str_starts_with($path, '//') && !str_contains($path, '\\');
        if (!$local || $path === '/') {
            return $this->url($this->queryFromReturn($request), $overrides);
        }
        parse_str((string) parse_url($return, \PHP_URL_QUERY), $params);
        // a production page shows the month of the date; source and production only prefill a new panel
        if (isset($overrides['date']) && str_starts_with($path, '/produktionen/')) {
            $params['monat'] = substr((string) $overrides['date'], 0, 7);
        }
        unset($overrides['date'], $params['from'], $params['production']);
        foreach ($overrides as $key => $value) {
            if ($value === null) {
                unset($params[$key]);
            } else {
                $params[$key] = $value;
            }
        }

        return $path . ($params ? '?' . http_build_query($params) : '');
    }

    /**
     * @param array<string, string|null> $overrides
     */
    public function url(CalendarQuery $query, array $overrides = []): string
    {
        return $this->urls->generate('app_calendar', $query->params($overrides));
    }

    /**
     * @param array<string, FormInterface> $formOverrides forms to show instead of fresh ones (with errors)
     */
    public function render(CalendarQuery $query, Request $request, array $formOverrides = [], int $status = 200): Response
    {
        $html = $this->twig->render('calendar/index.html.twig', [
            'query' => $query,
            'cal' => $this->builder->build($query),
            'panel' => $query->panel !== null ? $this->panel((string) $query->panel, $request, $formOverrides) : null,
            'keys' => $this->keys->findAllWithLoans(),
            'todos' => $this->todos->findBy(['done' => false], ['id' => 'ASC']),
            'last_sync' => $this->sync->getLastSyncTime(),
            'cleaning_contacts' => $this->contacts->findByRole(ContactRole::CLEANING),
            'return_url' => $this->url($query),
            'can_create_performances' => $this->ownership->canCreatePerformances(),
            'now' => $this->clock->now(),
        ]);

        return new Response($html, $status);
    }

    /**
     * Template data of one panel, e.g. "pe-12", "a-new" or "kl-3".
     *
     * @param array<string, FormInterface> $overrides
     *
     * @return array<string, mixed>
     */
    public function panel(string $panel, Request $request, array $overrides = []): array
    {
        $parts = explode('-', $panel);
        $kind = $parts[0];
        $id = $parts[1] ?? null;

        return match ($kind) {
            'pe' => $id === 'new' ? $this->newPerformancePanel($request, $overrides) : $this->performancePanel((int) $id, $overrides),
            'a' => $id === 'new' ? $this->newAppointmentPanel($request, $overrides) : $this->appointmentPanel((int) $id, $overrides),
            'cr' => $this->cleaningPanel((int) $id, $parts[2] ?? ''),
            'k' => $this->keyPanel((int) $id),
            'kl' => $id === 'new' ? $this->newLoanPanel($request, $overrides) : $this->loanPanel((int) $id, $overrides),
            default => throw new NotFoundHttpException(),
        };
    }

    /** @param array<string, FormInterface> $overrides */
    private function performancePanel(int $id, array $overrides): array
    {
        $performance = $this->performances->find($id) ?? throw new NotFoundHttpException('Vorstellung nicht gefunden.');
        $staffing = $this->appointmentManager->staffingFor($performance);

        return [
            'kind' => 'performance',
            'performance' => $performance,
            'production' => $performance->getProduction(),
            'locked' => $this->ownership->isLocked($performance, 'date'),
            'series' => $this->series($performance),
            'can_add' => $this->canAddPerformance($performance->getProduction()),
            'editable' => $editable = $this->hasEditableFields($performance),
            'performance_form' => $editable ? ($overrides['performance'] ?? $this->forms->create(PerformanceType::class, $performance))->createView() : null,
            'contacts_form' => ($overrides['contacts'] ?? $this->forms->create(PerformanceContactsType::class, $performance))->createView(),
            'staffing_form' => ($overrides['staffing'] ?? $this->forms->create(AppointmentType::class, $staffing, ['staffing' => true]))->createView(),
            'staffing' => $staffing,
            'loans' => $this->loans->findBy(['productionEvent' => $performance], ['startAt' => 'ASC']),
        ];
    }

    /** @param array<string, FormInterface> $overrides */
    private function newPerformancePanel(Request $request, array $overrides): array
    {
        $performance = $this->newPerformance($request);

        return [
            'kind' => 'performance_new',
            'performance' => $performance,
            'form' => ($overrides['performance'] ?? $this->forms->create(PerformanceType::class, $performance))->createView(),
        ];
    }

    /**
     * Defaults for a new performance: production and date from the query.
     */
    public function newPerformance(Request $request): ProductionEvent
    {
        $start = \DateTime::createFromFormat('Y-m-d\TH:i', $request->query->getString('start')) ?: null;
        $date = $start ?? (\DateTime::createFromFormat('!Y-m-d', $request->query->getString('date')) ?: new \DateTime('today'));
        $room = $request->query->getInt('room') > 0 ? $this->rooms->find($request->query->getInt('room')) : null;
        $production = $request->query->getInt('production') > 0 ? $this->productions->find($request->query->getInt('production')) : null;

        $source = $request->query->getInt('from') > 0 ? $this->performances->find($request->query->getInt('from')) : null;
        if ($source !== null) {
            return $this->copyOf($source);
        }

        return (new ProductionEvent())
            ->setProduction($production)
            ->setDate((clone $date)->setTime(0, 0))
            ->setTimeFrom($start?->format('H:i') ?? '19:30')
            ->setRoom($room)
            ->setStatus(EventStatus::ACTIVE)
            ->setReservationStatus(EventReservationStatus::INACTIVE);
    }

    /**
     * All performances of the same production, by date.
     *
     * @return ProductionEvent[]
     */
    private function series(ProductionEvent $performance): array
    {
        $series = $performance->getProduction()?->getEvents()->toArray() ?? [];
        usort($series, static fn (ProductionEvent $a, ProductionEvent $b) => [$a->getDate(), $a->getTimeFrom()] <=> [$b->getDate(), $b->getTimeFrom()]);

        return $series;
    }

    /** New performances: always for own productions, for WordPress ones once this system owns dates. */
    public function canAddPerformance(?Production $production): bool
    {
        return $production !== null && (!$production->isFromWordPress() || $this->ownership->canCreatePerformances());
    }

    /**
     * The next performance of a series: same room, times, seats and prices, on the day
     * after the last performance (or tomorrow, if the series lies in the past).
     */
    private function copyOf(ProductionEvent $source): ProductionEvent
    {
        $series = $this->series($source);
        $last = end($series) ?: $source;
        $tomorrow = new \DateTime('tomorrow');
        $date = $last->getDate() !== null ? (clone $last->getDate())->modify('+1 day') : $tomorrow;
        if ($date < $tomorrow) {
            $date = $tomorrow;
        }

        $copy = (new ProductionEvent())
            ->setProduction($source->getProduction())
            ->setDate($date)
            ->setTimeFrom($source->getTimeFrom())
            ->setTimeTo($source->getTimeTo())
            ->setRoom($source->getRoom())
            ->setStatus(EventStatus::ACTIVE)
            ->setReservationStatus($source->getReservationStatus() ?? EventReservationStatus::INACTIVE)
            ->setQuota($source->getQuota())
            ->setReservationNote($source->getReservationNote());
        foreach ($source->getPriceList() as $price) {
            $copy->addPriceList((new EventPrice())
                ->setCategoryLabel($price->getCategoryLabel())
                ->setPriceLabel($price->getPriceLabel())
                ->setReservedSeats($price->getReservedSeats()));
        }

        return $copy;
    }

    /** Whether any field of this performance can be edited here. */
    private function hasEditableFields(ProductionEvent $performance): bool
    {
        foreach (['date', 'timeFrom', 'timeTo', 'room', 'status', 'reservationStatus', 'quota', 'incomingTotal', 'freeSeats', 'reservationNote', 'categories', 'priceList'] as $field) {
            if (!$this->ownership->isLocked($performance, $field)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, FormInterface> $overrides */
    private function appointmentPanel(int $id, array $overrides): array
    {
        $appointment = $this->appointments->find($id) ?? throw new NotFoundHttpException('Termin nicht gefunden.');
        if ($appointment->getProductionEvent() !== null) {
            return $this->performancePanel((int) $appointment->getProductionEvent()->getId(), $overrides);
        }

        return [
            'kind' => 'appointment',
            'appointment' => $appointment,
            'form' => ($overrides['appointment'] ?? $this->forms->create(AppointmentType::class, $appointment))->createView(),
            'series' => $appointment->isInSeries() ? $this->appointments->findSeries($appointment) : [],
            'loans' => $this->loans->findBy(['appointment' => $appointment], ['startAt' => 'ASC']),
        ];
    }

    /** @param array<string, FormInterface> $overrides */
    private function newAppointmentPanel(Request $request, array $overrides): array
    {
        $appointment = $this->newAppointment($request);

        return [
            'kind' => 'appointment',
            'appointment' => $appointment,
            'form' => ($overrides['appointment'] ?? $this->forms->create(AppointmentType::class, $appointment))->createView(),
            'series' => [],
            'loans' => [],
        ];
    }

    /**
     * Defaults for a new appointment from the quick-create parameters.
     */
    public function newAppointment(Request $request): Appointment
    {
        $type = AppointmentTypeEnum::tryFrom($request->query->getString('type')) ?? AppointmentTypeEnum::PRIVATE;
        $start = \DateTime::createFromFormat('Y-m-d\TH:i', $request->query->getString('start'))
            ?: (new \DateTime())->setTime((int) date('H') + 1, 0);
        $room = $request->query->getInt('room') > 0 ? $this->rooms->find($request->query->getInt('room')) : null;

        return (new Appointment())
            ->setType($type)
            ->setStartDate($start)
            ->setEndDate((clone $start)->modify('+1 hour'))
            ->setRoom($type === AppointmentTypeEnum::CLEANING ? null : $room);
    }

    private function cleaningPanel(int $scheduleId, string $date): array
    {
        $schedule = $this->schedules->find($scheduleId) ?? throw new NotFoundHttpException('Reinigungsplan nicht gefunden.');
        $day = \DateTimeImmutable::createFromFormat('!Ymd', $date) ?: throw new NotFoundHttpException();

        return [
            'kind' => 'cleaning',
            'schedule' => $schedule,
            'day' => $day,
            'start' => $schedule->startOn($day),
            'end' => $schedule->endOn($day),
            'cancelled' => $schedule->isCancelledOn($day),
        ];
    }

    private function keyPanel(int $id): array
    {
        $key = $this->keys->find($id) ?? throw new NotFoundHttpException('Schlüssel nicht gefunden.');

        return ['kind' => 'key', 'key' => $key];
    }

    /** @param array<string, FormInterface> $overrides */
    private function loanPanel(int $id, array $overrides): array
    {
        $loan = $this->loans->find($id) ?? throw new NotFoundHttpException('Ausleihe nicht gefunden.');

        return [
            'kind' => 'loan',
            'loan' => $loan,
            'form' => ($overrides['loan'] ?? $this->forms->create(KeyLoanType::class, $loan))->createView(),
        ];
    }

    /** @param array<string, FormInterface> $overrides */
    private function newLoanPanel(Request $request, array $overrides): array
    {
        $loan = $this->newLoan($request);

        return [
            'kind' => 'loan',
            'loan' => $loan,
            'form' => ($overrides['loan'] ?? $this->forms->create(KeyLoanType::class, $loan))->createView(),
        ];
    }

    /**
     * Defaults for a new key loan; it can be tied to a key, an appointment or a performance.
     */
    public function newLoan(Request $request): KeyLoan
    {
        $loan = new KeyLoan();
        $now = $this->clock->now();
        $loan->setStartAt($now->setTime((int) $now->format('H'), 0));
        if ($request->query->getInt('key') > 0) {
            $loan->setKey($this->keys->find($request->query->getInt('key')));
        }
        if ($request->query->getInt('appointment') > 0 && ($appointment = $this->appointments->find($request->query->getInt('appointment'))) !== null) {
            $loan->setAppointment($appointment);
            $start = \DateTimeImmutable::createFromInterface($appointment->getStartDate());
            $loan->setStartAt($start->modify('-1 hour'))->setDueAt(\DateTimeImmutable::createFromInterface($appointment->getEndDate())->modify('+1 hour'));
        }
        if ($request->query->getInt('performance') > 0 && ($performance = $this->performances->find($request->query->getInt('performance'))) !== null) {
            $loan->setProductionEvent($performance);
            if (($start = $performance->getStartsAt()) !== null) {
                $loan->setStartAt($start->modify('-2 hours'))->setDueAt($performance->getEndsAt()?->modify('+1 hour'));
            }
        }

        return $loan;
    }
}
