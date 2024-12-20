<?php

namespace App\Repository;

use App\Entity\ReviewComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ReviewComment|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReviewComment|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReviewComment[]    findAll()
 * @method ReviewComment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReviewCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReviewComment::class);
    }

    /**
     * Najde všechny komentáře k danému článku.
     *
     * @param int $articleId
     * @return ReviewComment[]
     */
    public function findByArticleId(int $articleId): array
    {
        return $this->createQueryBuilder('rc')
            ->andWhere('rc.article = :articleId')
            ->setParameter('articleId', $articleId)
            ->orderBy('rc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
