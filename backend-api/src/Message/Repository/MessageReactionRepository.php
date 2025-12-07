<?php

declare(strict_types=1);

namespace App\Message\Repository;

use App\Message\Entity\MessageReaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageReaction>
 */
class MessageReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageReaction::class);
    }

    public function save(MessageReaction $reaction, bool $flush = true): void
    {
        $this->getEntityManager()->persist($reaction);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MessageReaction $reaction, bool $flush = true): void
    {
        $this->getEntityManager()->remove($reaction);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find reaction by message, user and emoji
     */
    public function findByMessageUserEmoji(int $messageId, int $userId, string $emoji): ?MessageReaction
    {
        return $this->findOneBy([
            'message' => $messageId,
            'user' => $userId,
            'emoji' => $emoji,
        ]);
    }

    /**
     * Get grouped reactions for message
     */
    public function getGroupedReactions(int $messageId): array
    {
        $reactions = $this->createQueryBuilder('mr')
            ->leftJoin('mr.user', 'u')
            ->addSelect('u')
            ->where('mr.message = :messageId')
            ->setParameter('messageId', $messageId)
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($reactions as $reaction) {
            $emoji = $reaction->getEmoji();
            if (!isset($grouped[$emoji])) {
                $grouped[$emoji] = [
                    'emoji' => $emoji,
                    'count' => 0,
                    'users' => [],
                ];
            }
            $grouped[$emoji]['count']++;
            $grouped[$emoji]['users'][] = [
                'id' => $reaction->getUser()->getId(),
                'name' => $reaction->getUser()->getFullName(),
            ];
        }

        return array_values($grouped);
    }
}
