<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Auth\Entity\User;
use App\User\Entity\UserSettings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class UserSettingsFixtures extends Fixture implements DependentFixtureInterface
{
    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < UserFixtures::USER_COUNT; $i++) {
            /** @var User $user */
            $user = $this->getReference(UserFixtures::USER_PREFIX . $i, User::class);

            $settings = new UserSettings();
            $settings->setUser($user);

            // Устанавливаем тему с весами: light - 30%, dark - 50%, auto - 20%
            $themeWeights = [
                UserSettings::THEME_LIGHT => 30,
                UserSettings::THEME_DARK => 50,
                UserSettings::THEME_AUTO => 20,
            ];
            $settings->setTheme($this->weightedRandom($themeWeights));

            // Включены ли уведомления (90% да)
            $settings->setNotificationsEnabled($this->faker->boolean(90));

            // Включен ли звук (80% да)
            $settings->setSoundEnabled($this->faker->boolean(80));

            // Email уведомления (70% да)
            $settings->setEmailNotifications($this->faker->boolean(70));

            // Показывать онлайн статус (85% да)
            $settings->setShowOnlineStatus($this->faker->boolean(85));

            // Язык: русский - 70%, английский - 30%
            $language = $this->faker->randomElement(['ru', 'ru', 'ru', 'en']);
            $settings->setLanguage($language);

            // Устанавливаем дату создания
            $settings->setCreatedAt($user->getCreatedAt());

            // Иногда настройки были обновлены
            if ($this->faker->boolean(40)) {
                $updatedAt = $this->faker->dateTimeBetween($user->getCreatedAt(), 'now');
                $settings->setUpdatedAt($updatedAt);
            }

            $manager->persist($settings);
        }

        $manager->flush();
    }

    private function weightedRandom(array $weights): string
    {
        $total = array_sum($weights);
        $random = $this->faker->numberBetween(1, $total);
        $cumulative = 0;

        foreach ($weights as $key => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $key;
            }
        }

        return array_key_first($weights);
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
