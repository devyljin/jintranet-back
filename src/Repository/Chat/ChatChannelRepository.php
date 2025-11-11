<?php

namespace App\Repository\Chat;

use App\Entity\Chat\ChatChannel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatChannel>
 */
class ChatChannelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatChannel::class);
    }

    /**
     * @return ChatChannel[] Returns an array of ChatChannel objects
     */
    public function findChannels(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parentChannel IS NULL')
            ->getQuery()
            ->getResult()
        ;
    }

//    public function findOneBySomeField($value): ?ChatChannel
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
