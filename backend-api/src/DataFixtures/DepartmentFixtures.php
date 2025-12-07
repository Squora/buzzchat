<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\User\Entity\Department;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class DepartmentFixtures extends Fixture
{
    public const DEPARTMENT_PREFIX = 'department_';
    public const DEPARTMENT_COUNT = 10;

    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        // Создаем стандартные департаменты
        $standardDepartments = [
            'IT Development',
            'Human Resources',
            'Sales',
            'Marketing',
            'Customer Support',
        ];

        foreach ($standardDepartments as $index => $name) {
            $department = new Department();
            $department->setName($name);
            $department->setDescription($this->faker->sentence(10));
            $department->setIsActive(true);

            $manager->persist($department);
            $this->addReference(self::DEPARTMENT_PREFIX . $index, $department);
        }

        // Создаем дополнительные случайные департаменты
        for ($i = count($standardDepartments); $i < self::DEPARTMENT_COUNT; $i++) {
            $department = new Department();
            $department->setName($this->faker->unique()->jobTitle() . ' Department');
            $department->setDescription($this->faker->optional(0.8)->sentence(12));
            $department->setIsActive($this->faker->boolean(90));

            $manager->persist($department);
            $this->addReference(self::DEPARTMENT_PREFIX . $i, $department);
        }

        $manager->flush();
    }
}
