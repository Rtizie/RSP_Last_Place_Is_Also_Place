<?php
// src/Controller/ArticleController.php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;  // Přidání správného importu pro EntityManager

class ArticleController extends AbstractController
{
    private $entityManager;

    // Přidání EntityManagerInterface do konstruktoru
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager; // Přiřazení EntityManageru do proměnné
    }

    // Route pro přidání článku
    #[Route('/add-article', name: 'apadd_article')]
    public function addArticle(Request $request): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Zpracování obrázku
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
                $article->setImage($newFilename);
            }

            // Nastavení autora jako aktuálně přihlášeného uživatele
            $article->setAuthor($this->getUser()->getUsername());

            // Použití injektovaného EntityManageru pro uložení článku
            $this->entityManager->persist($article);
            $this->entityManager->flush();

            // Po úspěšném přidání přesměruj na seznam článků
            return $this->redirectToRoute('article_list');
        }

        return $this->render('article/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Route pro zobrazení seznamu článků
    #[Route('/article-list', name: 'article_list')]
    public function articleList(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAll();
        return $this->render('article/list.html.twig', [
            'articles' => $articles,
        ]);
    }

    // Route pro zobrazení detailu článku
    #[Route('/article/{id}', name: 'article_detail')]
    public function articleDetail(int $id, ArticleRepository $articleRepository): Response
    {
        $article = $articleRepository->find($id);

        if (!$article) {
            throw $this->createNotFoundException('Článek nenalezen.');
        }

        return $this->render('article/detail.html.twig', [
            'article' => $article,
        ]);
    }
}
