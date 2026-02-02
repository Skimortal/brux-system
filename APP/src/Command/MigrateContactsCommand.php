<?php

namespace App\Command;

use App\Entity\Contact;
use App\Entity\ContactCategory;
use App\Entity\Technician;
use App\Entity\Volunteer;
use App\Entity\Cleaning;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate:contacts',
    description: 'Migriert bestehende Techniker, Freiwillige und Reinigungsfirmen zu Kontakten'
)]
class MigrateContactsCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Starten der Kontakt-Migration');

        // Kategorien erstellen/laden
        $io->section('Erstelle oder lade Kontakt-Kategorien');

        $technicianCategory = $this->getOrCreateCategory('Techniker');
        $volunteerCategory = $this->getOrCreateCategory('Freiwillige');
        $cleaningCategory = $this->getOrCreateCategory('Reinigung');

        $io->success('Kategorien erstellt/geladen');

        // Techniker migrieren
        $io->section('Migriere Techniker');
        $technicianCount = $this->migrateTechnicians($technicianCategory);
        $io->success("$technicianCount Techniker migriert");

        // Freiwillige migrieren
        $io->section('Migriere Freiwillige');
        $volunteerCount = $this->migrateVolunteers($volunteerCategory);
        $io->success("$volunteerCount Freiwillige migriert");

        // Reinigungsfirmen migrieren
        $io->section('Migriere Reinigungsfirmen');
        $cleaningCount = $this->migrateCleanings($cleaningCategory);
        $io->success("$cleaningCount Reinigungsfirmen migriert");

        $io->success(sprintf(
            'Migration abgeschlossen! Insgesamt %d Kontakte erstellt.',
            $technicianCount + $volunteerCount + $cleaningCount
        ));

        return Command::SUCCESS;
    }

    private function getOrCreateCategory(string $name): ContactCategory
    {
        $category = $this->entityManager->getRepository(ContactCategory::class)->findOneBy(['name' => $name]);

        if (!$category) {
            $category = new ContactCategory();
            $category->setName($name);
            $this->entityManager->persist($category);
            $this->entityManager->flush();
        }

        return $category;
    }

    private function migrateTechnicians(ContactCategory $category): int
    {
        $technicians = $this->entityManager->getRepository(Technician::class)->findAll();
        $count = 0;

        foreach ($technicians as $technician) {
            $contact = new Contact();
            $contact->setName($technician->getName() ?? 'Techniker');
            $contact->setEmail($technician->getEmail());
            $contact->setPhone($technician->getPhone());
            $contact->setCategory($category);

            $this->entityManager->persist($contact);
            $count++;
        }

        $this->entityManager->flush();
        return $count;
    }

    private function migrateVolunteers(ContactCategory $category): int
    {
        $volunteers = $this->entityManager->getRepository(Volunteer::class)->findAll();
        $count = 0;

        foreach ($volunteers as $volunteer) {
            $contact = new Contact();
            $contact->setName($volunteer->getName() ?? 'Freiwillige/r');
            $contact->setCategory($category);

            $this->entityManager->persist($contact);
            $count++;
        }

        $this->entityManager->flush();
        return $count;
    }

    private function migrateCleanings(ContactCategory $category): int
    {
        $cleanings = $this->entityManager->getRepository(Cleaning::class)->findAll();
        $count = 0;

        foreach ($cleanings as $cleaning) {
            $contact = new Contact();
            $contact->setName($cleaning->getName() ?? 'Reinigungsfirma');
            $contact->setCategory($category);

            $this->entityManager->persist($contact);
            $count++;
        }

        $this->entityManager->flush();
        return $count;
    }
}
