<?php

declare(strict_types=1);

namespace App\Auth\Command;

use App\Auth\Repository\PendingUserRepository;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:auth:cleanup-pending-users',
    description: 'Remove expired pending user registrations from the database',
)]
class CleanupPendingUsersCommand extends Command
{
    public function __construct(
        private readonly PendingUserRepository $pendingUserRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Cleaning up expired pending users');

        try {
            $deletedCount = $this->pendingUserRepository->deleteExpired();

            $io->success(sprintf('Successfully removed %d expired pending user(s)', $deletedCount));

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}