<?php

namespace App\Controller;

use App\Calendar\CalendarBuilder;
use App\Calendar\CalendarPage;
use App\Calendar\CalendarQuery;
use App\Entity\Production;
use App\Entity\ProductionPrice;
use App\Form\ProductionType;
use App\Repository\ProductionRepository;
use App\Service\FieldOwnership;
use App\Service\WebsiteContent;
use App\Service\WordPressPushService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/produktionen')]
final class ProductionController extends AbstractController
{
    use CrudTrait;

    #[Route('', name: 'app_production_index', methods: ['GET'])]
    public function index(Request $request, ProductionRepository $productions): Response
    {
        $showAll = $request->query->getBoolean('alle');
        $term = trim($request->query->getString('q'));

        return $this->render('production/index.html.twig', [
            'rows' => $productions->findOverview($showAll, $term),
            'show_all' => $showAll,
            'term' => $term,
        ]);
    }

    #[Route('/neu', name: 'app_production_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, WordPressPushService $push, CalendarBuilder $calendar, SluggerInterface $slugger, FieldOwnership $ownership, CalendarPage $page, WebsiteContent $content): Response
    {
        $production = (new Production())->setGeneralInfo(WebsiteContent::DEFAULT_GENERAL_INFO);
        // every production on the website offers these two; the Kulturpass has a fixed limit of
        // 5 seats, the Freikarte (only visible internally) always fits in 70
        foreach (['Kulturpass “Hunger auf Kunst und Kultur”' => 5, 'Freikarte' => 70] as $category => $seats) {
            $production->addPriceList((new ProductionPrice())->setPriceIndex(\count($production->getPriceList()))->setCategoryLabel($category)->setPriceLabel('0')->setParentReserved($seats));
        }

        return $this->edit($production, $request, $em, $push, $calendar, $slugger, $ownership, $page, $content);
    }

    #[Route('/{id}', name: 'app_production_show', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Production $production, Request $request, EntityManagerInterface $em, WordPressPushService $push, CalendarBuilder $calendar, SluggerInterface $slugger, FieldOwnership $ownership, CalendarPage $page, WebsiteContent $content): Response
    {
        // posts from WordPress are split into their sections the first time they are edited here
        $content->fillSections($production);
        $form = $this->createForm(ProductionType::class, $production);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->keepSingleMainContact($production);
            if ($form->has('imageFile') && ($image = $form->get('imageFile')->getData()) !== null) {
                $production->setPostThumbnailUrl($this->storeImage($image, $request, $slugger));
            }
            $index = 0;
            foreach ($production->getPriceList() as $price) {
                $price->setPriceIndex($index++)->setProduction($production);
            }
            foreach ($production->getEvents() as $performance) {
                $performance->alignPricesWithProduction();
            }
            $production->setContentHtml($content->render($production));
            $em->persist($production);
            $em->flush();
            $result = $push->pushProduction($production);
            if ($result['status'] === 'error') {
                $this->addFlash('warning', 'Gespeichert, aber die Übertragung an die Website ist fehlgeschlagen: ' . ($result['message'] ?? ''));
            } else {
                $this->addFlash('success', 'Produktion gespeichert.');
            }

            // created from the calendar: continue with the first performance on that day
            $start = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $request->query->getString('vorstellung'));
            if ($start !== false && $page->canAddPerformance($production)) {
                return $this->redirectToRoute('app_production_show', ['id' => $production->getId(), 'monat' => $start->format('Y-m'), 'panel' => 'pe-new', 'production' => $production->getId(), 'start' => $start->format('Y-m-d\TH:i')], 303);
            }

            return $this->redirectToRoute('app_production_show', ['id' => $production->getId()], 303);
        }

        $performances = $production->getEvents()->toArray();
        usort($performances, static fn ($a, $b) => [$a->getDate(), $a->getTimeFrom()] <=> [$b->getDate(), $b->getTimeFrom()]);

        // month calendar: requested month, else the month of the next (or last) performance
        $month = \DateTimeImmutable::createFromFormat('!Y-m', $request->query->getString('monat')) ?: null;
        if ($month === null) {
            $today = new \DateTimeImmutable('today');
            $upcoming = array_values(array_filter($performances, static fn ($p) => $p->getDate() !== null && $p->getDate() >= $today));
            $reference = $upcoming[0] ?? (end($performances) ?: null);
            $month = $reference?->getDate() !== null ? \DateTimeImmutable::createFromInterface($reference->getDate()) : $today;
        }
        $month = $month->modify('first day of this month')->setTime(0, 0);

        // a performance of this production opens in the side panel of this page
        $panel = $production->getId() !== null ? $this->performancePanel($production, $request, $page) : null;

        return $this->render('production/show.html.twig', [
            'production' => $production,
            'form' => $form,
            'performances' => $performances,
            'today' => new \DateTimeImmutable('today'),
            'cal' => $production->getId() !== null ? $calendar->buildForProduction($production, $month) : null,
            'month' => $month,
            'can_add_performance' => $production->getId() !== null && $page->canAddPerformance($production),
            'panel' => $panel,
            'query' => CalendarQuery::fromRequest(new Request(['date' => ($panel['performance'] ?? null)?->getDate()?->format('Y-m-d') ?? $month->format('Y-m-d')]), $month),
            'return_url' => $request->getRequestUri(),
            'can_create_performances' => $ownership->canCreatePerformances(),
            'now' => new \DateTimeImmutable(),
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}/loeschen', name: 'app_production_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Production $production, Request $request, EntityManagerInterface $em, WordPressPushService $push): Response
    {
        if (!$this->isCsrfTokenValid('delete-production-' . $production->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        // otherwise the next import would bring it back
        $result = $push->deleteProduction($production);
        if ($result['status'] === 'error') {
            $this->addFlash('danger', 'Nicht gelöscht, die Website hat abgelehnt: ' . ($result['message'] ?? ''));

            return $this->redirectToRoute('app_production_show', ['id' => $production->getId()], 303);
        }

        return $this->deleteEntity($request, $em, $production, 'delete-production-' . $production->getId(), 'app_production_index', 'Produktion');
    }

    /** Only one contact person can be the main contact; the last one ticked wins. */
    private function keepSingleMainContact(Production $production): void
    {
        $main = null;
        foreach ($production->getContactPersons() as $person) {
            if ($person->isHauptansprechperson()) {
                $main?->setHauptansprechperson(false);
                $main = $person;
            }
        }
    }

    /**
     * Stores an uploaded production image and returns its absolute URL, so the
     * website can load it once this system pushes the production.
     */
    private function storeImage(UploadedFile $image, Request $request, SluggerInterface $slugger): string
    {
        $name = $slugger->slug(pathinfo($image->getClientOriginalName(), \PATHINFO_FILENAME))->lower()
            . '-' . uniqid() . '.' . ($image->guessExtension() ?: 'jpg');
        $image->move($this->getParameter('production_images_dir'), $name);

        return $request->getSchemeAndHttpHost() . '/uploads/productions/' . $name;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function performancePanel(Production $production, Request $request, CalendarPage $page): ?array
    {
        $id = $request->query->getString('panel');
        if (!preg_match('/^pe-(\d+|new)$/', $id)) {
            return null;
        }
        $panel = $page->panel($id, $request);

        return $panel['performance']->getProduction() === $production || $id === 'pe-new' ? $panel : null;
    }
}
