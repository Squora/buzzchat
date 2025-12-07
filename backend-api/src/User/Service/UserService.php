<?php

declare(strict_types=1);

namespace App\User\Service;

use App\Auth\Entity\User;
use App\Auth\Repository\UserRepository;
use App\User\DTO\CreateDepartmentRequest;
use App\User\DTO\UpdateDepartmentRequest;
use App\User\DTO\UpdateOnlineStatusRequest;
use App\User\DTO\UpdateProfileRequest;
use App\User\DTO\UpdateSettingsRequest;
use App\User\Entity\Department;
use App\User\Entity\UserSettings;
use App\User\Exception\AccessDeniedException;
use App\User\Exception\DepartmentNotFoundException;
use App\User\Repository\DepartmentRepository;
use App\User\Repository\UserSettingsRepository;

final class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly DepartmentRepository $departmentRepository,
        private readonly UserSettingsRepository $settingsRepository,
    ) {}

    /**
     * Get user profile
     */
    public function getUserProfile(int $userId): User
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new \RuntimeException("User with ID {$userId} not found");
        }

        return $user;
    }

    /**
     * Update user profile
     */
    public function updateProfile(User $user, UpdateProfileRequest $dto, User $currentUser): User
    {
        // Only admins can update other users' profiles
        if ($user->getId() !== $currentUser->getId() && !$currentUser->isAdmin()) {
            throw AccessDeniedException::cannotModifyOtherUsers();
        }

        if ($dto->firstName !== null) {
            $user->setFirstName($dto->firstName);
        }

        if ($dto->lastName !== null) {
            $user->setLastName($dto->lastName);
        }

        if ($dto->position !== null) {
            $user->setPosition($dto->position);
        }

        if ($dto->statusMessage !== null) {
            $user->setStatusMessage($dto->statusMessage);
        }

        if ($dto->photoUrl !== null) {
            $user->setPhotoUrl($dto->photoUrl);
        }

        if ($dto->departmentId !== null) {
            $department = $this->departmentRepository->find($dto->departmentId);
            if (!$department) {
                throw DepartmentNotFoundException::withId($dto->departmentId);
            }
            $user->setDepartment($department);
        }

        $this->userRepository->save($user);

        return $user;
    }

    /**
     * Update online status
     */
    public function updateOnlineStatus(User $user, UpdateOnlineStatusRequest $dto): User
    {
        $user->setOnlineStatus($dto->status);
        $this->userRepository->save($user);

        return $user;
    }

    /**
     * Deactivate user (admin only)
     */
    public function deactivateUser(int $userId, User $admin): User
    {
        if (!$admin->isAdmin()) {
            throw AccessDeniedException::adminOnly();
        }

        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new \RuntimeException("User with ID {$userId} not found");
        }

        $user->setIsActive(false);
        $this->userRepository->save($user);

        return $user;
    }

    /**
     * Activate user (admin only)
     */
    public function activateUser(int $userId, User $admin): User
    {
        if (!$admin->isAdmin()) {
            throw AccessDeniedException::adminOnly();
        }

        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new \RuntimeException("User with ID {$userId} not found");
        }

        $user->setIsActive(true);
        $this->userRepository->save($user);

        return $user;
    }

    /**
     * Get user settings
     */
    public function getUserSettings(User $user): UserSettings
    {
        return $this->settingsRepository->getOrCreateForUser($user);
    }

    /**
     * Update user settings
     */
    public function updateSettings(User $user, UpdateSettingsRequest $dto): UserSettings
    {
        $settings = $this->settingsRepository->getOrCreateForUser($user);

        if ($dto->theme !== null) {
            $settings->setTheme($dto->theme);
        }

        if ($dto->notificationsEnabled !== null) {
            $settings->setNotificationsEnabled($dto->notificationsEnabled);
        }

        if ($dto->soundEnabled !== null) {
            $settings->setSoundEnabled($dto->soundEnabled);
        }

        if ($dto->emailNotifications !== null) {
            $settings->setEmailNotifications($dto->emailNotifications);
        }

        if ($dto->showOnlineStatus !== null) {
            $settings->setShowOnlineStatus($dto->showOnlineStatus);
        }

        if ($dto->language !== null) {
            $settings->setLanguage($dto->language);
        }

        $this->settingsRepository->save($settings);

        return $settings;
    }

    /**
     * Get all users (with optional filters)
     */
    public function getAllUsers(?int $departmentId = null, ?bool $onlineOnly = false): array
    {
        $qb = $this->userRepository->createQueryBuilder('u');

        if ($departmentId !== null) {
            $qb->andWhere('u.department = :departmentId')
               ->setParameter('departmentId', $departmentId);
        }

        if ($onlineOnly) {
            $qb->andWhere('u.onlineStatus IN (:statuses)')
               ->setParameter('statuses', [User::STATUS_AVAILABLE, User::STATUS_BUSY, User::STATUS_AWAY]);
        }

        return $qb->orderBy('u.lastName', 'ASC')
                  ->addOrderBy('u.firstName', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Get paginated users with filters and search
     *
     * @return array{users: User[], total: int}
     */
    public function getPaginatedUsers(int $page, int $limit, array $filters = []): array
    {
        $qb = $this->userRepository->createQueryBuilder('u')
            ->leftJoin('u.department', 'd')
            ->addSelect('d');

        // Search filter (by name, email, phone, or position)
        if (isset($filters['search']) && $filters['search'] !== '') {
            $searchTerm = '%' . $filters['search'] . '%';
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(u.firstName)', 'LOWER(:search)'),
                    $qb->expr()->like('LOWER(u.lastName)', 'LOWER(:search)'),
                    $qb->expr()->like('LOWER(u.email)', 'LOWER(:search)'),
                    $qb->expr()->like('u.phone', ':search'),
                    $qb->expr()->like('LOWER(u.position)', 'LOWER(:search)')
                )
            )->setParameter('search', $searchTerm);
        }

        // Department filter
        if (isset($filters['department_id'])) {
            $qb->andWhere('u.department = :departmentId')
               ->setParameter('departmentId', $filters['department_id']);
        }

        // Online status filter
        if (isset($filters['online_status'])) {
            $qb->andWhere('u.onlineStatus = :status')
               ->setParameter('status', $filters['online_status']);
        }

        // Active/inactive filter
        if (isset($filters['is_active'])) {
            $qb->andWhere('u.isActive = :isActive')
               ->setParameter('isActive', $filters['is_active']);
        }

        // Count total results
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Get paginated results
        $offset = ($page - 1) * $limit;
        $users = $qb->orderBy('u.lastName', 'ASC')
                    ->addOrderBy('u.firstName', 'ASC')
                    ->setFirstResult($offset)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();

        return [
            'users' => $users,
            'total' => $total,
        ];
    }

    /**
     * Create department (admin only)
     */
    public function createDepartment(CreateDepartmentRequest $dto, User $admin): Department
    {
        if (!$admin->isAdmin()) {
            throw AccessDeniedException::adminOnly();
        }

        $department = new Department();
        $department->setName($dto->name);
        $department->setDescription($dto->description);

        $this->departmentRepository->save($department);

        return $department;
    }

    /**
     * Update department (admin only)
     */
    public function updateDepartment(int $id, UpdateDepartmentRequest $dto, User $admin): Department
    {
        if (!$admin->isAdmin()) {
            throw AccessDeniedException::adminOnly();
        }

        $department = $this->departmentRepository->find($id);
        if (!$department) {
            throw DepartmentNotFoundException::withId($id);
        }

        if ($dto->name !== null) {
            $department->setName($dto->name);
        }

        if ($dto->description !== null) {
            $department->setDescription($dto->description);
        }

        if ($dto->isActive !== null) {
            $department->setIsActive($dto->isActive);
        }

        $this->departmentRepository->save($department);

        return $department;
    }

    /**
     * Get all departments
     */
    public function getAllDepartments(bool $activeOnly = false): array
    {
        if ($activeOnly) {
            return $this->departmentRepository->findActive();
        }

        return $this->departmentRepository->findAll();
    }

    /**
     * Get department by ID
     */
    public function getDepartment(int $id): Department
    {
        $department = $this->departmentRepository->find($id);
        if (!$department) {
            throw DepartmentNotFoundException::withId($id);
        }

        return $department;
    }
}
