<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Auth\Entity\User;
use App\Chat\Entity\Chat;
use App\Chat\Entity\ChatMember;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class ChatFixtures extends Fixture implements DependentFixtureInterface
{
    public const CHAT_PREFIX = 'chat_';
    public const GROUP_CHAT_COUNT = 20;
    public const DIRECT_CHAT_COUNT = 30;

    private Generator $faker;
    private int $totalChatsCreated = 0;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        $chatIndex = 0;

        // Создаем групповые чаты
        for ($i = 0; $i < self::GROUP_CHAT_COUNT; $i++) {
            $chat = $this->createGroupChat($manager);
            $manager->persist($chat);
            $this->addReference(self::CHAT_PREFIX . $chatIndex, $chat);
            $chatIndex++;
        }

        // Создаем личные чаты (пытаемся создать больше, чтобы гарантировать нужное количество)
        $createdDirectChats = 0;
        $attempts = 0;
        $maxAttempts = self::DIRECT_CHAT_COUNT * 3;

        while ($createdDirectChats < self::DIRECT_CHAT_COUNT && $attempts < $maxAttempts) {
            $chat = $this->createDirectChat($manager);
            if ($chat !== null) {
                $manager->persist($chat);
                $this->addReference(self::CHAT_PREFIX . $chatIndex, $chat);
                $chatIndex++;
                $createdDirectChats++;
            }
            $attempts++;
        }

        $this->totalChatsCreated = $chatIndex;
        $manager->flush();
    }

    public function getTotalChatsCreated(): int
    {
        return $this->totalChatsCreated;
    }

    private function createGroupChat(ObjectManager $manager): Chat
    {
        $chat = new Chat();
        $chat->setType(Chat::TYPE_GROUP);
        $chat->setName($this->faker->catchPhrase());
        $chat->setDescription($this->faker->optional(0.7)->sentence(10));

        // Устанавливаем фото группы (иногда)
        if ($this->faker->boolean(30)) {
            $chat->setPhotoUrl($this->faker->imageUrl(200, 200, 'abstract'));
        }

        // Устанавливаем дату создания
        $createdAt = $this->faker->dateTimeBetween('-6 months', '-1 day');
        $chat->setCreatedAt($createdAt);

        // Добавляем участников (от 3 до 15)
        $memberCount = $this->faker->numberBetween(3, 15);
        $userIndices = $this->faker->randomElements(
            range(0, UserFixtures::USER_COUNT - 1),
            $memberCount
        );

        // Первый участник - владелец
        $ownerIndex = array_shift($userIndices);
        /** @var User $owner */
        $owner = $this->getReference(UserFixtures::USER_PREFIX . $ownerIndex, User::class);
        $ownerMember = new ChatMember();
        $ownerMember->setChat($chat);
        $ownerMember->setUser($owner);
        $ownerMember->setRole(ChatMember::ROLE_OWNER);
        $ownerMember->setJoinedAt($createdAt);
        $chat->addMember($ownerMember);
        $manager->persist($ownerMember);

        // Назначаем админов (1-2 человека)
        $adminCount = $this->faker->numberBetween(1, min(2, count($userIndices)));
        $adminIndices = array_splice($userIndices, 0, $adminCount);

        foreach ($adminIndices as $userIndex) {
            /** @var User $user */
            $user = $this->getReference(UserFixtures::USER_PREFIX . $userIndex, User::class);
            $member = new ChatMember();
            $member->setChat($chat);
            $member->setUser($user);
            $member->setRole(ChatMember::ROLE_ADMIN);
            $member->setJoinedAt($this->faker->dateTimeBetween($createdAt, 'now'));
            $chat->addMember($member);
            $manager->persist($member);
        }

        // Остальные участники - обычные члены
        foreach ($userIndices as $userIndex) {
            /** @var User $user */
            $user = $this->getReference(UserFixtures::USER_PREFIX . $userIndex, User::class);
            $member = new ChatMember();
            $member->setChat($chat);
            $member->setUser($user);
            $member->setRole(ChatMember::ROLE_MEMBER);
            $member->setJoinedAt($this->faker->dateTimeBetween($createdAt, 'now'));

            // Некоторые участники могли выйти из чата
            if ($this->faker->boolean(10)) {
                $member->setLeftAt($this->faker->dateTimeBetween($member->getJoinedAt(), 'now'));
            }

            $chat->addMember($member);
            $manager->persist($member);
        }

        return $chat;
    }

    private function createDirectChat(ObjectManager $manager): ?Chat
    {
        // Выбираем двух случайных пользователей
        $user1Index = $this->faker->numberBetween(0, UserFixtures::USER_COUNT - 1);
        $user2Index = $this->faker->numberBetween(0, UserFixtures::USER_COUNT - 1);

        // Проверяем, что пользователи разные
        if ($user1Index === $user2Index) {
            return null;
        }

        /** @var User $user1 */
        $user1 = $this->getReference(UserFixtures::USER_PREFIX . $user1Index, User::class);
        /** @var User $user2 */
        $user2 = $this->getReference(UserFixtures::USER_PREFIX . $user2Index, User::class);

        $chat = new Chat();
        $chat->setType(Chat::TYPE_DIRECT);

        // Устанавливаем дату создания
        $createdAt = $this->faker->dateTimeBetween('-6 months', '-1 day');
        $chat->setCreatedAt($createdAt);

        // Добавляем двух участников
        foreach ([$user1, $user2] as $user) {
            $member = new ChatMember();
            $member->setChat($chat);
            $member->setUser($user);
            $member->setRole(ChatMember::ROLE_MEMBER);
            $member->setJoinedAt($createdAt);
            $chat->addMember($member);
            $manager->persist($member);
        }

        return $chat;
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
