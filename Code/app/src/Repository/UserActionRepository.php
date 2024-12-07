<?php

// src/Repository/UserActionRepository.php

namespace App\Repository;

use App\Entity\UserAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserAction>
 */
class UserActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAction::class);
    }

    public function findRecentActions(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.timestamp', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByUsername(string $username): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.username = :username')
            ->setParameter('username', $username)
            ->orderBy('u.timestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
