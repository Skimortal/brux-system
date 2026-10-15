<?php

namespace App\Command;

use App\Service\BruxApiSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-brux-api',
    description: 'Synchronize productions and events from Brux API',
)]
class SyncBruxApiCommand extends Command
{
    public function __construct(
        private BruxApiSyncService $syncService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('take', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Production field to take from WordPress for existing productions in this run (e.g. categories, once after it was added), or "all" for every field of productions and performances');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Brux API Synchronization');
        $io->text('Starting synchronization from ' . $this->syncService->getApiUrl());

        $stats = $this->syncService->syncFromApi($input->getOption('take'));

        $io->success('Synchronization completed!');

        $io->section('Statistics');
        $io->listing([
            sprintf('Productions created: %d', $stats['productions_created']),
            sprintf('Productions updated: %d', $stats['productions_updated']),
            sprintf('Events created: %d', $stats['events_created']),
            sprintf('Events updated: %d', $stats['events_updated']),
            sprintf('Categories created: %d', $stats['categories_created']),
        ]);

        if (!empty($stats['errors'])) {
            $io->section('Errors');
            $io->error($stats['errors']);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
