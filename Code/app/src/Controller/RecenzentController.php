<?php
// src/Controller/RecenzentController.php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ReviewComment;
use App\Entity\UserAction;
use App\Repository\ArticleRepository;
use App\Repository\ReviewCommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RecenzentController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Uživatelé s rolemi "ROLE_RECENZENT" nebo "ROLE_REDAKTOR" mohou zobrazit články.
     */
    #[Route('/recenzent-articles', name: 'recenzent_articles')]
    public function articlesForReview(ArticleRepository $articleRepository): Response
    {
        if (!$this->isGranted('ROLE_RECENZENT') && !$this->isGranted('ROLE_REDAKTOR') && !$this->isGranted('ROLE_ADMIN')) {
            $logMessage = sprintf(
                'Neúspěšný pokus o přístup k recenzentským článkům uživatelem: %s',
                $this->getUser()->getUsername()
            );
            $userAction = new UserAction($this->getUser()->getUsername(), $logMessage);
            $this->entityManager->persist($userAction);
            $this->entityManager->flush();

            throw new AccessDeniedException('Nemáte oprávnění zobrazit články.');
        }

        $articles = $articleRepository->findBy(['status' => 'approved'], ['createdAt' => 'DESC']);

        return $this->render('article/articles.html.twig', [
            'articles' => $articles,
        ]);
    }

    /**
     * Uživatelé s rolemi "ROLE_RECENZENT" nebo "ROLE_REDAKTOR" mohou zobrazit podrobnosti článku.
     */
    #[Route('/recenzent-article/{id}', name: 'recenzent_article')]
    public function reviewArticle(
        int $id,
        ArticleRepository $articleRepository,
        ReviewCommentRepository $reviewCommentRepository,
        Request $request
    ): Response {
        if (!$this->isGranted('ROLE_RECENZENT') && !$this->isGranted('ROLE_REDAKTOR') && !$this->isGranted('ROLE_ADMIN')) {
            $logMessage = sprintf(
                'Neúspěšný pokus o přístup k detailu článku ID %d uživatelem: %s',
                $id,
                $this->getUser()->getUsername()
            );
            $userAction = new UserAction($this->getUser()->getUsername(), $logMessage);
            $this->entityManager->persist($userAction);
            $this->entityManager->flush();

            throw new AccessDeniedException('Nemáte oprávnění zobrazit podrobnosti článků.');
        }

        $article = $articleRepository->find($id);

        if (!$article) {
            $logMessage = sprintf(
                'Článek ID %d nebyl nalezen uživatelem: %s',
                $id,
                $this->getUser()->getUsername()
            );
            $userAction = new UserAction($this->getUser()->getUsername(), $logMessage);
            $this->entityManager->persist($userAction);
            $this->entityManager->flush();

            throw $this->createNotFoundException('Článek nenalezen.');
        }

        $existingComment = null;
        if ($this->isGranted('ROLE_RECENZENT')) {
            $existingComment = $reviewCommentRepository->findOneBy([
                'article' => $article,
                'author' => $this->getUser(),
            ]);
        }

        if ($request->isMethod('POST') && $this->isGranted('ROLE_RECENZENT')) {
            if ($existingComment) {
                $logMessage = sprintf(
                    'Uživatel %s se pokusil přidat duplicitní komentář k článku ID %d',
                    $this->getUser()->getUsername(),
                    $id
                );
                $userAction = new UserAction($this->getUser()->getUsername(), $logMessage);
                $this->entityManager->persist($userAction);
                $this->entityManager->flush();

                $this->addFlash('error', 'K tomuto článku již máte vytvořený komentář.');
            } else {
                $commentContent = $request->request->get('comment');

                if (empty($commentContent)) {
                    $logMessage = sprintf(
                        'Uživatel %s odeslal prázdný komentář k článku ID %d',
                        $this->getUser()->getUsername(),
                        $id
                    );
                    $userAction = new UserAction($this->getUser()->getUsername(), $logMessage);
                    $this->entityManager->persist($userAction);
                    $this->entityManager->flush();

                    $this->addFlash('error', 'Komentář nemůže být prázdný.');
                } else {
                    $comment = new ReviewComment();
                    $comment->setArticle($article);
                    $comment->setAuthor($this->getUser());
                    $comment->setContent($commentContent);
                    $comment->setCreatedAt(new \DateTime());

                    $this->entityManager->persist($comment);

                    $logMessage = sprintf(
                        'Uživatel %s přidal komentář k článku ID %d: "%s"',
                        $this->getUser()->getUsername(),
                        $id,
                        $commentContent
                    );
                    $userAction = new UserAction($this->getUser()->getUsername(), $logMessage);
                    $this->entityManager->persist($userAction);

                    $this->entityManager->flush();

                    $this->addFlash('success', 'Komentář byl úspěšně přidán.');

                    return $this->redirectToRoute('recenzent_articles');
                }
            }
        }

        return $this->render('article/article_detail.html.twig', [
            'article' => $article,
            'existingComment' => $existingComment,
        ]);
    }
}
