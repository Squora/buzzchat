<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Auth\Entity\User;
use App\User\Entity\Department;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public const USER_PREFIX = 'user_';
    public const USER_COUNT = 50;
    private const ADMIN_COUNT = 3;

    private Generator $faker;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        // Создаем админов
        for ($i = 0; $i < self::ADMIN_COUNT; $i++) {
            $user = $this->createUser(isAdmin: true);
            $manager->persist($user);
            $this->addReference(self::USER_PREFIX . $i, $user);
        }

        // Создаем обычных пользователей
        for ($i = self::ADMIN_COUNT; $i < self::USER_COUNT; $i++) {
            $user = $this->createUser();
            $manager->persist($user);
            $this->addReference(self::USER_PREFIX . $i, $user);
        }

        $manager->flush();
    }

    private function createUser(bool $isAdmin = false): User
    {
        $user = new User();

        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();

        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setEmail($this->faker->unique()->safeEmail());

        // Генерируем телефон в формате +7XXXXXXXXXX
        $phone = '+7' . $this->faker->numerify('##########');
        $user->setPhone($phone);

        // Устанавливаем пароль (для тестов будет "password")
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password');
        $user->setPassword($hashedPassword);

        if ($isAdmin) {
            $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        }

        // Назначаем департамент
        if ($this->faker->boolean(80)) {
            $departmentIndex = $this->faker->numberBetween(0, DepartmentFixtures::DEPARTMENT_COUNT - 1);
            /** @var Department $department */
            $department = $this->getReference(DepartmentFixtures::DEPARTMENT_PREFIX . $departmentIndex, Department::class);
            $user->setDepartment($department);
        }

        // Устанавливаем позицию
        $user->setPosition($this->faker->optional(0.9)->jobTitle());

        // Устанавливаем статус сообщения
        $user->setStatusMessage($this->faker->optional(0.3)->sentence(6));

        // Устанавливаем онлайн статус
        $statuses = [User::STATUS_AVAILABLE, User::STATUS_BUSY, User::STATUS_AWAY, User::STATUS_OFFLINE];
        $weights = [30, 15, 10, 45]; // Веса для распределения статусов
        $status = $this->faker->randomElement(array_merge(...array_map(
            fn($status, $weight) => array_fill(0, $weight, $status),
            $statuses,
            $weights
        )));
        $user->setOnlineStatus($status);

        // Устанавливаем lastSeenAt
        if ($status === User::STATUS_OFFLINE) {
            $user->setLastSeenAt($this->faker->dateTimeBetween('-7 days', 'now'));
        } else {
            $user->setLastSeenAt(new \DateTime());
        }

        // Устанавливаем фото профиля (иногда)
        if ($this->faker->boolean(40)) {
            $user->setPhotoUrl($this->faker->imageUrl(200, 200, 'people'));
        }

        // Иногда деактивируем пользователя
        $user->setIsActive($this->faker->boolean(95));

        // Устанавливаем дату создания
        $createdAt = $this->faker->dateTimeBetween('-1 year', '-1 month');
        $user->setCreatedAt($createdAt);

        return $user;
    }

    public function getDependencies(): array
    {
        return [
            DepartmentFixtures::class,
        ];
    }
}
