<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Auth\Entity\User;
use App\Chat\Entity\Chat;
use App\Chat\Entity\ChatMember;
use App\Message\Entity\Message;
use App\Message\Entity\MessageAttachment;
use App\Message\Entity\MessageReaction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class MessageFixtures extends Fixture implements DependentFixtureInterface
{
    private const MESSAGES_PER_CHAT_MIN = 5;
    private const MESSAGES_PER_CHAT_MAX = 30;
    private const POPULAR_EMOJIS = ['👍', '❤️', '😂', '🔥', '👏', '😍', '🎉', '💯', '🤔', '😢', '😊', '🙏'];

    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        // Получаем все ID чатов заранее
        $chatIds = [];
        $chatIndex = 0;
        $maxExpectedChats = ChatFixtures::GROUP_CHAT_COUNT + ChatFixtures::DIRECT_CHAT_COUNT;

        while ($chatIndex < $maxExpectedChats * 2) {
            try {
                /** @var Chat $chat */
                $chat = $this->getReference(ChatFixtures::CHAT_PREFIX . $chatIndex, Chat::class);
                $chatIds[] = $chat->getId();
            } catch (\OutOfBoundsException $e) {
                // Если ссылка не найдена, пропускаем
            }
            $chatIndex++;
        }

        // Теперь обрабатываем каждый чат по ID
        foreach ($chatIds as $chatId) {
            $chat = $manager->find(Chat::class, $chatId);
            if (!$chat) {
                continue;
            }

            // Получаем активных участников чата
            $activeMembers = array_filter(
                $chat->getMembers()->toArray(),
                fn(ChatMember $member) => !$member->hasLeft()
            );

            if (empty($activeMembers)) {
                continue;
            }

            // Генерируем сообщения для чата
            $messageCount = $this->faker->numberBetween(
                self::MESSAGES_PER_CHAT_MIN,
                self::MESSAGES_PER_CHAT_MAX
            );

            $previousMessage = null;

            for ($i = 0; $i < $messageCount; $i++) {
                /** @var ChatMember $randomMember */
                $randomMember = $this->faker->randomElement($activeMembers);
                $user = $randomMember->getUser();

                $message = $this->createMessage($chat, $user, $previousMessage);
                $manager->persist($message);

                // Добавляем вложения к некоторым сообщениям
                if ($this->faker->boolean(15)) {
                    $this->addAttachments($message, $manager);
                }

                // Добавляем реакции к некоторым сообщениям
                if ($this->faker->boolean(30)) {
                    $this->addReactions($message, $activeMembers, $manager);
                }

                $previousMessage = $message;
            }

            // Flush после каждого чата для экономии памяти
            $manager->flush();
            $manager->clear();
        }
    }

    private function createMessage(Chat $chat, User $user, ?Message $replyTo = null): Message
    {
        $message = new Message();
        $message->setChat($chat);
        $message->setUser($user);

        // Определяем тип сообщения
        $typeWeights = [
            Message::TYPE_TEXT => 85,
            Message::TYPE_IMAGE => 10,
            Message::TYPE_FILE => 4,
            Message::TYPE_SYSTEM => 1,
        ];

        $type = $this->weightedRandom($typeWeights);
        $message->setType($type);

        // Генерируем текст в зависимости от типа
        switch ($type) {
            case Message::TYPE_TEXT:
                $message->setText($this->generateMessageText());
                break;
            case Message::TYPE_IMAGE:
                $message->setText($this->faker->optional(0.7, '')->sentence(5));
                break;
            case Message::TYPE_FILE:
                $message->setText($this->faker->optional(0.5, '')->sentence(4));
                break;
            case Message::TYPE_SYSTEM:
                $systemMessages = [
                    'User joined the chat',
                    'User left the chat',
                    'Chat name was changed',
                    'User was promoted to admin',
                ];
                $message->setText($this->faker->randomElement($systemMessages));
                break;
        }

        // Убедимся, что текст не null (требование базы данных)
        if ($message->getText() === null) {
            $message->setText('');
        }

        // Иногда сообщение является ответом на предыдущее
        if ($replyTo !== null && $this->faker->boolean(10)) {
            $message->setReplyTo($replyTo);
        }

        // Устанавливаем дату создания
        $chatCreatedAt = $chat->getCreatedAt();
        $now = new \DateTime();
        $message->setCreatedAt($this->faker->dateTimeBetween($chatCreatedAt, $now));

        // Некоторые сообщения были отредактированы
        if ($type === Message::TYPE_TEXT && $this->faker->boolean(5)) {
            $editedAt = $this->faker->dateTimeBetween($message->getCreatedAt(), $now);
            $message->setEditedAt($editedAt);
            $message->setUpdatedAt($editedAt);
        }

        // Некоторые сообщения были удалены
        if ($this->faker->boolean(2)) {
            $deletedAt = $this->faker->dateTimeBetween($message->getCreatedAt(), $now);
            $message->setDeletedAt($deletedAt);
        }

        // Добавляем упоминания (mentions) для групповых чатов
        if ($chat->isGroup() && $this->faker->boolean(15)) {
            $mentionCount = $this->faker->numberBetween(1, 3);
            $mentions = [];
            for ($i = 0; $i < $mentionCount; $i++) {
                $mentions[] = $this->faker->numberBetween(1, UserFixtures::USER_COUNT);
            }
            $message->setMentions(array_unique($mentions));
        }

        return $message;
    }

    private function addAttachments(Message $message, ObjectManager $manager): void
    {
        $attachmentCount = $this->faker->numberBetween(1, 3);

        $fileTypes = [
            ['type' => 'image/jpeg', 'ext' => 'jpg', 'category' => 'image'],
            ['type' => 'image/png', 'ext' => 'png', 'category' => 'image'],
            ['type' => 'application/pdf', 'ext' => 'pdf', 'category' => 'document'],
            ['type' => 'application/msword', 'ext' => 'doc', 'category' => 'document'],
            ['type' => 'video/mp4', 'ext' => 'mp4', 'category' => 'video'],
        ];

        for ($i = 0; $i < $attachmentCount; $i++) {
            $fileInfo = $this->faker->randomElement($fileTypes);

            $attachment = new MessageAttachment();
            $attachment->setMessage($message);
            $attachment->setFileName($this->faker->word() . '.' . $fileInfo['ext']);
            $attachment->setFileType($fileInfo['type']);
            $attachment->setFileSize($this->faker->numberBetween(1024, 10485760)); // 1KB - 10MB

            if ($fileInfo['category'] === 'image') {
                $attachment->setFileUrl($this->faker->imageUrl(800, 600));
                $attachment->setThumbnailUrl($this->faker->imageUrl(200, 150));
            } else {
                $attachment->setFileUrl($this->faker->url() . '/' . $attachment->getFileName());
            }

            $attachment->setCreatedAt($message->getCreatedAt());

            $message->addAttachment($attachment);
            $manager->persist($attachment);
        }
    }

    private function addReactions(Message $message, array $members, ObjectManager $manager): void
    {
        $reactionCount = $this->faker->numberBetween(1, min(5, count($members)));
        $reactingMembers = $this->faker->randomElements($members, $reactionCount);

        foreach ($reactingMembers as $member) {
            /** @var ChatMember $member */
            $reaction = new MessageReaction();
            $reaction->setMessage($message);
            $reaction->setUser($member->getUser());
            $reaction->setEmoji($this->faker->randomElement(self::POPULAR_EMOJIS));

            $reactionTime = $this->faker->dateTimeBetween(
                $message->getCreatedAt(),
                min(new \DateTime(), (clone $message->getCreatedAt())->modify('+1 day'))
            );
            $reaction->setCreatedAt($reactionTime);

            $manager->persist($reaction);
        }
    }

    private function generateMessageText(): string
    {
        $patterns = [
            fn() => $this->faker->sentence($this->faker->numberBetween(3, 20)),
            fn() => $this->faker->realText($this->faker->numberBetween(50, 200)),
            fn() => implode(' ', $this->faker->words($this->faker->numberBetween(2, 10))),
            fn() => $this->faker->paragraph(2),
        ];

        return $this->faker->randomElement($patterns)();
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
            ChatFixtures::class,
        ];
    }
}
