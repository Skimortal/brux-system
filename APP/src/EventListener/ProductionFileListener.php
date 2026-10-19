<?php

namespace App\EventListener;

use App\Entity\ProductionFile;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Removes a document from disk once its record is gone, also when its production is deleted.
 */
#[AsEntityListener(event: Events::postRemove, entity: ProductionFile::class)]
final class ProductionFileListener
{
    public function __construct(#[Autowire('%production_files_dir%')] private readonly string $dir)
    {
    }

    public function postRemove(ProductionFile $file): void
    {
        $path = $this->dir . '/' . $file->getStoredName();
        if ($file->getStoredName() !== null && is_file($path)) {
            unlink($path);
        }
    }
}
