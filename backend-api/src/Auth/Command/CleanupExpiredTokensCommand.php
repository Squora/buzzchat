<?php

declare(strict_types=1);

namespace App\Auth\Command;

use App\Auth\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:auth:cleanup-tokens',
    description: 'Remove expired refresh tokens from the database',
)]
class CleanupExpiredTokensCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Cleaning up expired refresh tokens');

        try {
            $qb = $this->entityManager->createQueryBuilder();
            $deletedCount = $qb->delete(RefreshToken::class, 'rt')
                ->where('rt.valid < :now')
                ->setParameter('now', new \DateTime())
                ->getQuery()
                ->execute();

            $io->success(sprintf('Successfully removed %d expired token(s)', $deletedCount));

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}