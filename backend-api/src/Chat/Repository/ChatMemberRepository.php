<?php

declare(strict_types=1);

namespace App\Chat\Repository;

use App\Chat\Entity\ChatMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatMember>
 */
class ChatMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMember::class);
    }

    public function save(ChatMember $member, bool $flush = true): void
    {
        $this->getEntityManager()->persist($member);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ChatMember $member, bool $flush = true): void
    {
        $this->getEntityManager()->remove($member);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find member by chat and user
     */
    public function findByChatAndUser(int $chatId, int $userId): ?ChatMember
    {
        return $this->findOneBy([
            'chat' => $chatId,
            'user' => $userId,
        ]);
    }

    /**
     * Find active member (not left) by chat and user
     */
    public function findActiveByChatAndUser(int $chatId, int $userId): ?ChatMember
    {
        return $this->createQueryBuilder('cm')
            ->where('cm.chat = :chatId')
            ->andWhere('cm.user = :userId')
            ->andWhere('cm.leftAt IS NULL')
            ->setParameter('chatId', $chatId)
            ->setParameter('userId', $userId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all active members of a chat
     */
    public function findActiveMembersByChat(int $chatId): array
    {
        return $this->createQueryBuilder('cm')
            ->where('cm.chat = :chatId')
            ->andWhere('cm.leftAt IS NULL')
            ->setParameter('chatId', $chatId)
            ->orderBy('cm.joinedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count active members in a chat
     */
    public function countActiveMembersByChat(int $chatId): int
    {
        return (int) $this->createQueryBuilder('cm')
            ->select('COUNT(cm.id)')
            ->where('cm.chat = :chatId')
            ->andWhere('cm.leftAt IS NULL')
            ->setParameter('chatId', $chatId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Check if user is member of chat
     */
    public function isMemberOfChat(int $chatId, int $userId): bool
    {
        $result = $this->createQueryBuilder('cm')
            ->select('COUNT(cm.id)')
            ->where('cm.chat = :chatId')
            ->andWhere('cm.user = :userId')
            ->andWhere('cm.leftAt IS NULL')
            ->setParameter('chatId', $chatId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Find owner of the chat
     */
    public function findOwnerByChat(int $chatId): ?ChatMember
    {
        return $this->createQueryBuilder('cm')
            ->where('cm.chat = :chatId')
            ->andWhere('cm.role = :role')
            ->andWhere('cm.leftAt IS NULL')
            ->setParameter('chatId', $chatId)
            ->setParameter('role', ChatMember::ROLE_OWNER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all admins and owner of the chat
     */
    public function findAdminsByChat(int $chatId): array
    {
        return $this->createQueryBuilder('cm')
            ->where('cm.chat = :chatId')
            ->andWhere('cm.role IN (:roles)')
            ->andWhere('cm.leftAt IS NULL')
            ->setParameter('chatId', $chatId)
            ->setParameter('roles', [ChatMember::ROLE_OWNER, ChatMember::ROLE_ADMIN])
            ->orderBy('cm.role', 'ASC')
            ->addOrderBy('cm.joinedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find members with pagination and filters
     *
     * @return array{items: ChatMember[], total: int}
     */
    public function findMembersWithPagination(
        int $chatId,
        int $offset,
        int $limit,
        ?string $search = null,
        ?string $role = null,
        ?string $status = null,
        string $sortBy = 'joined_at',
        string $sortOrder = 'asc'
    ): array {
        // Build query for items
        $qb = $this->createQueryBuilder('cm')
            ->innerJoin('cm.user', 'u')
            ->addSelect('u')
            ->where('cm.chat = :chatId')
            ->andWhere('cm.leftAt IS NULL')
            ->setParameter('chatId', $chatId);

        // Apply search filter
        if ($search !== null && $search !== '') {
            $qb->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.phone LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Apply role filter
        if ($role !== null) {
            $qb->andWhere('cm.role = :role')
                ->setParameter('role', $role);
        }

        // Apply status filter
        if ($status !== null) {
            $qb->andWhere('u.onlineStatus = :status')
                ->setParameter('status', $status);
        }

        // Apply sorting
        switch ($sortBy) {
            case 'role':
                // Owner first, then admin, then member
                $qb->addOrderBy('cm.role', $sortOrder);
                break;
            case 'name':
                $qb->addOrderBy('u.firstName', $sortOrder);
                $qb->addOrderBy('u.lastName', $sortOrder);
                break;
            case 'online':
                // Online users first (based on status)
                $qb->addOrderBy(
                    "CASE
                        WHEN u.onlineStatus = 'available' THEN 1
                        WHEN u.onlineStatus = 'busy' THEN 2
                        WHEN u.onlineStatus = 'away' THEN 3
                        ELSE 4
                    END",
                    $sortOrder
                );
                $qb->addOrderBy('u.lastSeenAt', 'DESC');
                break;
            case 'joined_at':
            default:
                $qb->addOrderBy('cm.joinedAt', $sortOrder);
                break;
        }

        // Count total (before pagination)
        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(cm.id)')
            ->setFirstResult(null)
            ->setMaxResults(null)
            ->getQuery()
            ->getSingleScalarResult();

        // Apply pagination
        $items = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}
