<?php

// src/Repository/ArticleRepository.php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

class ArticleRepository extends ServiceEntityRepository
{
    private $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Article::class);
        $this->entityManager = $entityManager;  // Store the entity manager
    }

    public function archiveArticle(int $id): ?Article
    {
        $article = $this->find($id);

        if ($article) {
            $article->setStatus('archived');  // Example action on the article
            $this->entityManager->flush();   // Use entity manager to flush changes
        }

        return $article;
    }
}

