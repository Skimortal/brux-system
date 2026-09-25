<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:user:create', description: 'Create a user or reset the password of an existing one')]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED)
            ->addArgument('email', InputArgument::REQUIRED)
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'ROLE_USER, ROLE_ADMIN or ROLE_SUPERUSER', 'ROLE_USER')
            ->addOption('first-name', null, InputOption::VALUE_REQUIRED, '', '')
            ->addOption('last-name', null, InputOption::VALUE_REQUIRED, '', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = (string) $input->getArgument('username');
        $password = (string) $io->askHidden('Password (min. 8 characters)');
        if (\strlen($password) < 8) {
            $io->error('Password too short.');

            return Command::FAILURE;
        }

        $user = $this->users->findOneBy(['username' => $username]) ?? new User();
        $user->setUsername($username);
        $user->setEmail((string) $input->getArgument('email'));
        $user->setFirstName((string) $input->getOption('first-name') ?: $username);
        $user->setLastName((string) $input->getOption('last-name'));
        $role = (string) $input->getOption('role');
        $user->setRoles($role === 'ROLE_USER' ? [] : [$role]);
        $user->setIsActive(true);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('User "%s" saved.', $username));

        return Command::SUCCESS;
    }
}
