<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\Department;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Department>
 */
class DepartmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Department::class);
    }

    public function save(Department $department, bool $flush = true): void
    {
        $this->getEntityManager()->persist($department);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Department $department, bool $flush = true): void
    {
        $this->getEntityManager()->remove($department);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all active departments
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find department by name
     */
    public function findByName(string $name): ?Department
    {
        return $this->findOneBy(['name' => $name]);
    }
}
