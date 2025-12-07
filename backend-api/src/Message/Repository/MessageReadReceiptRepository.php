<?php

declare(strict_types=1);

namespace App\Message\Repository;

use App\Message\Entity\MessageReadReceipt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageReadReceipt>
 */
class MessageReadReceiptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageReadReceipt::class);
    }

    public function save(MessageReadReceipt $receipt, bool $flush = true): void
    {
        $this->getEntityManager()->persist($receipt);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MessageReadReceipt $receipt, bool $flush = true): void
    {
        $this->getEntityManager()->remove($receipt);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find receipt by message and user
     */
    public function findByMessageAndUser(int $messageId, int $userId): ?MessageReadReceipt
    {
        return $this->findOneBy([
            'message' => $messageId,
            'user' => $userId,
        ]);
    }

    /**
     * Mark multiple messages as read
     */
    public function markAsRead(array $messageIds, int $userId): void
    {
        $em = $this->getEntityManager();

        foreach ($messageIds as $messageId) {
            // Check if already exists
            $existing = $this->findByMessageAndUser($messageId, $userId);
            if ($existing) {
                continue;
            }

            // Create new receipt
            $message = $em->getReference(\App\Message\Entity\Message::class, $messageId);
            $user = $em->getReference(\App\Auth\Entity\User::class, $userId);

            $receipt = new MessageReadReceipt();
            $receipt->setMessage($message);
            $receipt->setUser($user);

            $this->save($receipt, false);
        }

        $em->flush();
    }

    /**
     * Check if message is read by user
     */
    public function isReadBy(int $messageId, int $userId): bool
    {
        return $this->findByMessageAndUser($messageId, $userId) !== null;
    }

    /**
     * Get read receipts count for message
     */
    public function countForMessage(int $messageId): int
    {
        return (int) $this->createQueryBuilder('rr')
            ->select('COUNT(rr.id)')
            ->where('rr.message = :messageId')
            ->setParameter('messageId', $messageId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
