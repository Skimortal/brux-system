<?php

namespace App\Controller;

use App\Calendar\CalendarBuilder;
use App\Calendar\CalendarEntry;
use App\Calendar\CalendarQuery;
use App\Calendar\EntryGroup;
use App\Entity\CalendarFeed;
use App\Entity\User;
use App\Enum\FeedProfile;
use App\Repository\AppointmentRepository;
use App\Repository\CalendarFeedRepository;
use App\Repository\ProductionEventRepository;
use App\Service\CalendarFeedLinks;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The board's own area: performances, closed and school events and released rehearsals,
 * read-only, with their calendar subscription.
 */
#[IsGranted('ROLE_BOARD')]
final class BoardController extends AbstractController
{
    #[Route('/vorstand', name: 'app_board', methods: ['GET'])]
    public function index(Request $request, CalendarBuilder $builder, ClockInterface $clock, ProductionEventRepository $performances, AppointmentRepository $appointments, CalendarFeedRepository $feeds, CalendarFeedLinks $links, EntityManagerInterface $em): Response
    {
        $query = CalendarQuery::fromRequest($request, $clock->now()->setTime(0, 0));
        // stays and key loans are not part of what the board follows
        $query = new CalendarQuery($query->date, $query->view, $query->layout, [EntryGroup::KEYS, EntryGroup::STAY], $query->panel, $query->side);
        $cal = $builder->build($query, static fn (CalendarEntry $e) => $e->boardVisible);

        $panel = null;
        [$kind, $id] = array_pad(explode('-', (string) $query->panel), 2, null);
        if ($kind === 'pe' && ($performance = $performances->find((int) $id)) !== null) {
            $panel = ['performance' => $performance, 'production' => $performance->getProduction(), 'appointment' => null];
        } elseif ($kind === 'a' && ($appointment = $appointments->find((int) $id)) !== null
            && ($appointment->isBoardVisible() || \in_array($appointment->getType()->value, ['closed_event', 'school_event'], true))) {
            $panel = ['performance' => null, 'production' => $appointment->getProduction(), 'appointment' => $appointment];
        }

        // the personal subscription of this board member
        $user = $this->getUser();
        $contact = $user instanceof User ? $user->getContact() : null;
        $feedLinks = [];
        if ($contact !== null) {
            $feed = $feeds->findOneForContact($contact, FeedProfile::BOARD);
            if ($feed === null) {
                $feed = (new CalendarFeed())->setName((string) $contact->getName())->setProfile(FeedProfile::BOARD)->setContact($contact);
                $em->persist($feed);
                $em->flush();
            }
            $feedLinks = $links->links($feed);
        }

        return $this->render('board/index.html.twig', [
            'query' => $query,
            'cal' => $cal,
            'panel' => $panel,
            'feed_links' => $feedLinks,
            'return_url' => $request->getRequestUri(),
            'now' => $clock->now(),
        ]);
    }
}
