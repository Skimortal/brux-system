<?php

namespace App\Command;

use App\Service\WordPressPushService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:wordpress-check',
    description: 'Check the connection and credentials of the WordPress write API',
)]
class WordPressCheckCommand extends Command
{
    public function __construct(
        private WordPressPushService $push
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->push->isConfigured()) {
            $io->warning('WORDPRESS_API_URL, WORDPRESS_API_USER and WORDPRESS_API_PASSWORD must all be set.');

            return Command::FAILURE;
        }

        $io->text('Checking ' . $this->push->getApiUrl());
        $result = $this->push->checkConnection();
        if ($result['status'] !== 'success') {
            $io->error(sprintf('%s (HTTP %s)', $result['message'] ?? 'Unknown error', $result['status_code'] ?? '-'));

            return Command::FAILURE;
        }

        $io->success('Connected.');
        $io->text(json_encode($result['response'], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES));

        return Command::SUCCESS;
    }
}
