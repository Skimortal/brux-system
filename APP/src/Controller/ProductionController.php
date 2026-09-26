<?php

namespace App\Controller;

use App\Calendar\CalendarBuilder;
use App\Entity\Production;
use App\Form\ProductionType;
use App\Repository\ProductionRepository;
use App\Service\FieldOwnership;
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
    public function new(Request $request, EntityManagerInterface $em, WordPressPushService $push, CalendarBuilder $calendar, SluggerInterface $slugger, FieldOwnership $ownership): Response
    {
        return $this->edit(new Production(), $request, $em, $push, $calendar, $slugger, $ownership);
    }

    #[Route('/{id}', name: 'app_production_show', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Production $production, Request $request, EntityManagerInterface $em, WordPressPushService $push, CalendarBuilder $calendar, SluggerInterface $slugger, FieldOwnership $ownership): Response
    {
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
            $em->persist($production);
            $em->flush();
            $result = $push->pushProduction($production);
            if ($result['status'] === 'error') {
                $this->addFlash('warning', 'Gespeichert, aber die Übertragung an die Website ist fehlgeschlagen: ' . ($result['message'] ?? ''));
            } else {
                $this->addFlash('success', 'Produktion gespeichert.');
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

        return $this->render('production/show.html.twig', [
            'production' => $production,
            'form' => $form,
            'performances' => $performances,
            'today' => new \DateTimeImmutable('today'),
            'cal' => $production->getId() !== null ? $calendar->buildForProduction($production, $month) : null,
            'month' => $month,
            'can_add_performance' => $production->getId() !== null && (!$production->isFromWordPress() || $ownership->canCreatePerformances()),
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}/loeschen', name: 'app_production_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Production $production, Request $request, EntityManagerInterface $em): Response
    {
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
}
