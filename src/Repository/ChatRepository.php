<?php

namespace App\Repository;

use App\Entity\Chat;
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

    public function findChat(int $userId1, int $userId2):?Chat
    {
        return $this->createQueryBuilder('c')
            ->where('(c.userOne = :userId1 AND c.userTwo = :userId2) OR (c.userOne = :userId2 AND c.userTwo = :userId1)')
            ->setParameter('userId1', $userId1)
            ->setParameter('userId2', $userId2)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
