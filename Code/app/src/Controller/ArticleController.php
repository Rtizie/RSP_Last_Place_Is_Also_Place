<?php
// src/Controller/ArticleController.php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ArticleController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // Přidání článku (povolení pro autory i adminy)
    #[Route('/add-article', name: 'add-article')]
    public function addArticle(Request $request): Response
    {
        if (!$this->isGranted('ROLE_AUTHOR') && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Nemáte oprávnění přidávat články.');
        }

        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
                $article->setImage($newFilename);
            }

            $article->setAuthor($this->getUser()->getUsername());

            $this->entityManager->persist($article);
            $this->entityManager->flush();

            return $this->redirectToRoute('article_list');
        }

        return $this->render('article/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Seznam všech článků (dostupné pro všechny)
    #[Route('/article-list', name: 'article_list')]
    public function articleList(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAll();
        return $this->render('article/list.html.twig', [
            'articles' => $articles,
        ]);
    }

    // Detail článku s dalšími články (dostupné pro všechny)
    #[Route('/article/{id}', name: 'article_detail')]
    public function articleDetail(int $id, ArticleRepository $articleRepository): Response
    {
        $article = $articleRepository->find($id);

        if (!$article) {
            throw $this->createNotFoundException('Článek nenalezen.');
        }

        // Načtení dalších článků (vyjma aktuálního článku)
        $relatedArticles = $articleRepository->createQueryBuilder('a')
            ->where('a.id != :id')
            ->setParameter('id', $id)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('article/detail.html.twig', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
        ]);
    }

    // Mazání článku (pouze pro adminy)
    #[Route('/article/{id}/delete', name: 'article_delete')]
    public function deleteArticle(int $id, ArticleRepository $articleRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Nemáte oprávnění mazat články.');
        }

        $article = $articleRepository->find($id);

        if (!$article) {
            throw $this->createNotFoundException('Článek nenalezen.');
        }

        $this->entityManager->remove($article);
        $this->entityManager->flush();

        return $this->redirectToRoute('article_list');
    }
}
