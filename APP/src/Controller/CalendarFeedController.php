<?php

namespace App\Controller;

use App\Entity\CalendarFeed;
use App\Enum\FeedProfile;
use App\Repository\CalendarFeedRepository;
use App\Repository\RoomRepository;
use App\Service\CalendarFeedBuilder;
use App\Service\CalendarFeedLinks;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Calendar subscriptions (iCal): the feeds themselves, reached by their secret token, and
 * their administration.
 */
final class CalendarFeedController extends AbstractController
{
    public function __construct(
        private readonly CalendarFeedRepository $feeds,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/kalender/{token}.ics', name: 'app_calendar_feed', methods: ['GET'], requirements: ['token' => '[0-9a-f]{40}'])]
    public function feed(string $token, Request $request, CalendarFeedBuilder $builder, RoomRepository $rooms): Response
    {
        $feed = $this->feeds->findOneBy(['token' => $token]) ?? throw new NotFoundHttpException();
        $parts = $feed->getProfile()->parts();
        $part = $request->query->getString('teil', (string) array_key_first($parts));
        if (!isset($parts[$part])) {
            throw new NotFoundHttpException();
        }
        $room = $request->query->getInt('raum') > 0 && $parts[$part]['rooms'] ? $rooms->find($request->query->getInt('raum')) : null;

        $response = new Response($builder->build($feed, $part, $room));
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Content-Disposition', 'inline; filename="brux.ics"');
        // private: the link is personal; a short cache keeps calendar apps from hammering
        $response->setPrivate();
        $response->setMaxAge(600);

        return $response;
    }

    #[Route('/verwaltung/kalender-abos', name: 'app_calendar_feed_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request, CalendarFeedLinks $links): Response
    {
        if ($request->isMethod('POST')) {
            $this->requireToken($request);
            $name = trim($request->request->getString('name'));
            $profile = FeedProfile::tryFrom($request->request->getString('profile'));
            if ($name === '' || $profile === null || $profile === FeedProfile::MINE) {
                $this->addFlash('danger', 'Bitte Namen und Art des Abos angeben.');
            } else {
                $this->em->persist((new CalendarFeed())->setName(mb_substr($name, 0, 120))->setProfile($profile));
                $this->em->flush();
                $this->addFlash('success', 'Abo für ' . $name . ' angelegt. Schick der Person die Links unten.');
            }

            return $this->redirectToRoute('app_calendar_feed_index', [], Response::HTTP_SEE_OTHER);
        }

        $feeds = $this->feeds->findBy([], ['profile' => 'ASC', 'name' => 'ASC']);

        return $this->render('calendar_feed/index.html.twig', [
            'feeds' => $feeds,
            'links' => array_combine(array_map(static fn (CalendarFeed $f) => (int) $f->getId(), $feeds), array_map($links->links(...), $feeds)),
            'profiles' => [FeedProfile::BOARD, FeedProfile::TECHNICIANS, FeedProfile::MANAGEMENT],
        ]);
    }

    #[Route('/verwaltung/kalender-abos/{id}/erneuern', name: 'app_calendar_feed_renew', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function renew(CalendarFeed $feed, Request $request): Response
    {
        $this->requireToken($request);
        $feed->renewToken();
        $this->em->flush();
        $this->addFlash('success', 'Neue Links für ' . $feed->getName() . ' – die alten funktionieren nicht mehr.');

        return $this->redirectToRoute('app_calendar_feed_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/verwaltung/kalender-abos/{id}/loeschen', name: 'app_calendar_feed_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(CalendarFeed $feed, Request $request): Response
    {
        $this->requireToken($request);
        $this->em->remove($feed);
        $this->em->flush();
        $this->addFlash('success', 'Abo gelöscht, seine Links funktionieren nicht mehr.');

        return $this->redirectToRoute('app_calendar_feed_index', [], Response::HTTP_SEE_OTHER);
    }

    private function requireToken(Request $request): void
    {
        if (!$this->isCsrfTokenValid('calendar-feed', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Ungültiges Formular. Bitte Seite neu laden.');
        }
    }
}
