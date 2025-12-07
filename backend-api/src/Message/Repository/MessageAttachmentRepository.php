<?php

declare(strict_types=1);

namespace App\Message\Repository;

use App\Message\Entity\MessageAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageAttachment>
 */
class MessageAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageAttachment::class);
    }

    public function save(MessageAttachment $attachment, bool $flush = true): void
    {
        $this->getEntityManager()->persist($attachment);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MessageAttachment $attachment, bool $flush = true): void
    {
        $this->getEntityManager()->remove($attachment);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
