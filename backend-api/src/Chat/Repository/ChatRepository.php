<?php

declare(strict_types=1);

namespace App\Chat\Repository;

use App\Chat\Entity\Chat;
use App\Chat\Entity\ChatMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Chat>
 */
class ChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chat::class);
    }

    public function save(Chat $chat, bool $flush = true): void
    {
        $this->getEntityManager()->persist($chat);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Chat $chat, bool $flush = true): void
    {
        $this->getEntityManager()->remove($chat);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find direct chat between two users
     */
    public function findDirectChatBetweenUsers(int $userId1, int $userId2): ?Chat
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.members', 'm1')
            ->innerJoin('c.members', 'm2')
            ->where('c.type = :type')
            ->andWhere('m1.user = :user1')
            ->andWhere('m2.user = :user2')
            ->andWhere('m1.leftAt IS NULL')
            ->andWhere('m2.leftAt IS NULL')
            ->setParameter('type', Chat::TYPE_DIRECT)
            ->setParameter('user1', $userId1)
            ->setParameter('user2', $userId2)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all chats where user is a member
     */
    public function findUserChats(int $userId): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.members', 'm')
            ->where('m.user = :userId')
            ->andWhere('m.leftAt IS NULL')
            ->setParameter('userId', $userId)
            ->orderBy('c.updatedAt', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all group chats where user is a member
     */
    public function findUserGroupChats(int $userId): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.members', 'm')
            ->where('c.type = :type')
            ->andWhere('m.user = :userId')
            ->andWhere('m.leftAt IS NULL')
            ->setParameter('type', Chat::TYPE_GROUP)
            ->setParameter('userId', $userId)
            ->orderBy('c.updatedAt', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all direct chats where user is a member
     */
    public function findUserDirectChats(int $userId): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.members', 'm')
            ->where('c.type = :type')
            ->andWhere('m.user = :userId')
            ->andWhere('m.leftAt IS NULL')
            ->setParameter('type', Chat::TYPE_DIRECT)
            ->setParameter('userId', $userId)
            ->orderBy('c.updatedAt', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if chat exists by ID and user is a member
     */
    public function existsForUser(int $chatId, int $userId): bool
    {
        $result = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->innerJoin('c.members', 'm')
            ->where('c.id = :chatId')
            ->andWhere('m.user = :userId')
            ->andWhere('m.leftAt IS NULL')
            ->setParameter('chatId', $chatId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }
}
