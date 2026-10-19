<?php

namespace App\Controller;

use App\Entity\Production;
use App\Entity\ProductionFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Documents of a production for the technicians (riders, cue lists, plans).
 */
final class ProductionFileController extends AbstractController
{
    /** Shown in the browser; everything else is downloaded. */
    private const INLINE_TYPES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'txt', 'csv', 'zip'];

    private const ALLOWED_TYPES = [
        'application/pdf',
        'image/jpeg', 'image/png', 'image/webp',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.spreadsheet',
        'text/plain', 'text/csv', 'application/zip',
    ];

    public function __construct(
        #[Autowire('%production_files_dir%')] private readonly string $dir,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/produktionen/{id}/dateien', name: 'app_production_file_upload', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function upload(Production $production, Request $request, ValidatorInterface $validator): Response
    {
        if (!$this->isCsrfTokenValid('production-file', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Ungültiges Formular. Bitte Seite neu laden.');
        }
        $constraint = new File(
            maxSize: '10M',
            // the extension and the sniffed type must both fit
            extensions: self::ALLOWED_EXTENSIONS,
            mimeTypes: self::ALLOWED_TYPES,
            extensionsMessage: 'Dieser Dateityp wird nicht unterstützt (PDF, Bilder, Office-Dokumente, Text, ZIP).',
            maxSizeMessage: 'Die Datei ist zu groß (höchstens 10 MB).',
            mimeTypesMessage: 'Dieser Dateityp wird nicht unterstützt (PDF, Bilder, Office-Dokumente, Text, ZIP).',
        );
        /** @var list<UploadedFile> $uploads */
        $uploads = array_filter((array) $request->files->get('files'));
        $saved = 0;
        foreach ($uploads as $upload) {
            $errors = $validator->validate($upload, $constraint);
            if (\count($errors) > 0) {
                $this->addFlash('danger', $upload->getClientOriginalName() . ': ' . $errors[0]->getMessage());
                continue;
            }
            $file = (new ProductionFile())
                ->setProduction($production)
                ->setOriginalName(mb_substr($upload->getClientOriginalName(), 0, 255))
                ->setStoredName(bin2hex(random_bytes(16)))
                ->setMimeType((string) ($upload->getMimeType() ?: 'application/octet-stream'))
                ->setSize((int) $upload->getSize());
            $upload->move($this->dir, (string) $file->getStoredName());
            $this->em->persist($file);
            ++$saved;
        }
        $this->em->flush();
        if ($saved > 0) {
            $this->addFlash('success', $saved === 1 ? 'Datei hochgeladen.' : $saved . ' Dateien hochgeladen.');
        }

        return $this->redirectToRoute('app_production_show', ['id' => $production->getId(), '_fragment' => 'dateien'], Response::HTTP_SEE_OTHER);
    }

    #[Route('/dateien/{id}', name: 'app_production_file_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TECHNICIAN')]
    public function download(ProductionFile $file): Response
    {
        $path = $this->dir . '/' . $file->getStoredName();
        if (!is_file($path)) {
            throw $this->createNotFoundException('Die Datei ist nicht mehr vorhanden.');
        }
        $response = new BinaryFileResponse($path);
        $inline = \in_array($file->getMimeType(), self::INLINE_TYPES, true);
        $response->headers->set('Content-Type', $inline ? $file->getMimeType() : 'application/octet-stream');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setContentDisposition(
            $inline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            (string) $file->getOriginalName(),
            (string) preg_replace('/[^A-Za-z0-9._ -]/', '_', (string) $file->getOriginalName()),
        );
        $response->setPrivate();

        return $response;
    }

    #[Route('/dateien/{id}/loeschen', name: 'app_production_file_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function delete(ProductionFile $file, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('production-file', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Ungültiges Formular. Bitte Seite neu laden.');
        }
        $production = $file->getProduction();
        // the file on disk goes with the record (ProductionFileListener)
        $this->em->remove($file);
        $this->em->flush();
        $this->addFlash('success', 'Datei gelöscht.');

        return $this->redirectToRoute('app_production_show', ['id' => $production?->getId(), '_fragment' => 'dateien'], Response::HTTP_SEE_OTHER);
    }
}
