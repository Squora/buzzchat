<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\Auth\Entity\User;
use App\User\Entity\UserSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSettings>
 */
class UserSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSettings::class);
    }

    public function save(UserSettings $settings, bool $flush = true): void
    {
        $this->getEntityManager()->persist($settings);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserSettings $settings, bool $flush = true): void
    {
        $this->getEntityManager()->remove($settings);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find settings by user
     */
    public function findByUser(User $user): ?UserSettings
    {
        return $this->findOneBy(['user' => $user]);
    }

    /**
     * Get or create settings for user
     */
    public function getOrCreateForUser(User $user): UserSettings
    {
        $settings = $this->findByUser($user);

        if (!$settings) {
            $settings = new UserSettings();
            $settings->setUser($user);
            $this->save($settings);
        }

        return $settings;
    }
}
