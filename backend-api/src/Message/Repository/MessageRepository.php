<?php

declare(strict_types=1);

namespace App\Message\Repository;

use App\Message\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function save(Message $message, bool $flush = true): void
    {
        $this->getEntityManager()->persist($message);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Message $message, bool $flush = true): void
    {
        $this->getEntityManager()->remove($message);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find messages by chat with pagination
     */
    public function findByChatId(int $chatId, ?int $beforeId = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->leftJoin('m.replyTo', 'r')
            ->leftJoin('m.attachments', 'a')
            ->addSelect('u', 'r', 'a')
            ->where('m.chat = :chatId')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('chatId', $chatId)
            ->orderBy('m.id', 'DESC')
            ->setMaxResults($limit);

        if ($beforeId !== null) {
            $qb->andWhere('m.id < :beforeId')
               ->setParameter('beforeId', $beforeId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Search messages by text
     */
    public function search(string $query, ?int $chatId = null, ?int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u')
            ->where('m.deletedAt IS NULL')
            ->andWhere('m.text LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($chatId !== null) {
            $qb->andWhere('m.chat = :chatId')
               ->setParameter('chatId', $chatId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count unread messages for user in chat
     */
    public function countUnread(int $chatId, int $userId): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->leftJoin('m.readReceipts', 'rr', 'WITH', 'rr.user = :userId')
            ->where('m.chat = :chatId')
            ->andWhere('m.user != :userId')
            ->andWhere('m.deletedAt IS NULL')
            ->andWhere('rr.id IS NULL')
            ->setParameter('chatId', $chatId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find messages with mentions of user
     */
    public function findMentions(int $userId, ?int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->leftJoin('m.chat', 'c')
            ->addSelect('u', 'c')
            ->where('m.deletedAt IS NULL')
            ->andWhere('JSON_CONTAINS(m.mentions, :userId, \'$\') = 1')
            ->setParameter('userId', json_encode($userId))
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get last message in chat
     */
    public function getLastMessageInChat(int $chatId): ?Message
    {
        return $this->createQueryBuilder('m')
            ->where('m.chat = :chatId')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('chatId', $chatId)
            ->orderBy('m.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
