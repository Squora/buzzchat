<?php

declare(strict_types=1);

namespace App\Auth\Repository;

use App\Auth\Entity\PendingUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PendingUser>
 */
class PendingUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PendingUser::class);
    }

    public function save(PendingUser $pendingUser): void
    {
        $this->getEntityManager()->persist($pendingUser);
        $this->getEntityManager()->flush();
    }

    public function remove(PendingUser $pendingUser): void
    {
        $this->getEntityManager()->remove($pendingUser);
        $this->getEntityManager()->flush();
    }

    public function findByPhone(string $phone): ?PendingUser
    {
        return $this->findOneBy(['phone' => $phone]);
    }

    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('pu')
            ->delete()
            ->where('pu.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
