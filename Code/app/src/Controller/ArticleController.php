<?php
// src/Controller/ArticleController.php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\UserAction;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Repository\UserActionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse; 


    /*
    * Čtenář Může koukat pouze članky
    * ADMIN Má maximální práva
    * Redaktor Může schvalovat články, vrácet články přo předělaní
    * Recenzent Může vytvářet posudky a hodnocení pro daný článek?
    * Autor může přidátvat članky
    */



class ArticleController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function archive(ArticleRepository $articleRepository, int $id): RedirectResponse
        {
            $article = $articleRepository->archiveArticle($id);

            if ($article) {
                $this->addFlash('success', 'Článek byl archivován.');
            } else {
                $this->addFlash('error', 'Článek se nepodařilo archivovat.');
            }

            return $this->redirectToRoute('article_list');
        }

    /*
    * ADMIN or AUTHOR can add articles.
    */
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
            $article->setStatus('offered');
    
            $userAction = new UserAction($this->getUser()->getUsername(), 'Přidal článek: ' . $article->getTitle());
            $this->entityManager->persist($userAction);
    
            $this->entityManager->persist($article);
            $this->entityManager->flush();
    
            return $this->redirectToRoute('author_articles');
        }
    
        return $this->render('article/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /*
    * AUTHOR can view their own articles.
    */
    #[Route('/author-articles', name: 'author_articles')]
    public function authorArticles(ArticleRepository $articleRepository): Response
    {
        if (!$this->isGranted('ROLE_AUTHOR') && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Nemáte oprávnění zobrazovat články.');
        }

        $articles = $articleRepository->findBy(['author' => $this->getUser()->getUsername()]);

        return $this->render('article/author_articles.html.twig', [
            'articles' => $articles,
        ]);
    }

    /*
    * AUTHOR can edit their own articles.
    */

    #[Route('/article-edit/{id}', name: 'article_edit')]
    public function edit(Request $request, Article $article): Response
    {
        $user = $this->getUser(); 
    
        if ($user->getUsername() !== $article->getAuthor()) {
            throw new AccessDeniedException('Nemáte oprávnění upravovat tento článek.');
        }
    
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $article->setStatus('Article was rewrited and offered');
            $this->entityManager->flush();
    
            $userAction = new UserAction($this->getUser()->getUsername(), 'Upravil článek: ' . $article->getTitle());
            $this->entityManager->persist($userAction);
            $this->entityManager->flush();
    
            return $this->redirectToRoute('article_list');
        }
    
        return $this->render('article/edit.html.twig', [
            'form' => $form->createView(),
            'article' => $article,
        ]);
    }
    

    /*
    * REDAKTOR and ADMIN can see the list of offered articles for review.
    */
    #[Route('/offered-articles', name: 'offered_articles')]
    public function offeredArticles(ArticleRepository $articleRepository): Response
    {
        if (!$this->isGranted('ROLE_REDAKTOR') && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Nemáte oprávnění zobrazit nabídnuté články.');
        }

        $articles = $articleRepository->findBy(
            ['status' => ['offered', 'Article was rewrited and offered']], 
            ['createdAt' => 'DESC']
        );

        return $this->render('article/offered_articles.html.twig', [
            'articles' => $articles,
        ]);
    }

    /*
    * REDAKTOR or ADMIN can approve or reject articles.
    */
    #[Route('/article/{id}/review', name: 'article_review')]
    public function reviewArticle(int $id, ArticleRepository $articleRepository, Request $request): Response
    {
        if (!$this->isGranted('ROLE_REDAKTOR') && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Nemáte oprávnění hodnotit články.');
        }
    
        $article = $articleRepository->find($id);
    
        if (!$article) {
            throw $this->createNotFoundException('Článek nenalezen.');
        }
    
        $action = $request->get('action');
        
        if ($action === 'approve') {
            $article->setStatus('approved');
            $logMessage = 'Schválil článek: ' . $article->getTitle();
        } elseif ($action === 'reject') {
            $article->setStatus('rejected');
            $logMessage = 'Zamítl článek: ' . $article->getTitle();
        } else {
            return $this->redirectToRoute('offered_articles');
        }
    
        $userAction = new UserAction($this->getUser()->getUsername(), $logMessage);
        $this->entityManager->persist($userAction);
    
        $this->entityManager->flush();
    
        return $this->redirectToRoute('offered_articles');
    }
    

    /*
    * AUTHOR can view the article details (including the status of the article).
    */
    #[Route('/article/{id}', name: 'article_detail')]
    public function articleDetail(int $id, ArticleRepository $articleRepository): Response
    {
        $article = $articleRepository->find($id);

        if (!$article) {
            throw $this->createNotFoundException('Článek nenalezen.');
        }

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

    /*
    * Any user can view all articles.
    */
    #[Route('/article-list', name: 'article_list')]
    public function articleList(ArticleRepository $articleRepository): Response
    {
        $approvedArticles = $articleRepository->findBy(['status' => 'approved']);
    
        return $this->render('article/list.html.twig', [
            'articles' => $approvedArticles,
        ]);
    }
    
    /*
    * REDAKTOR or ADMIN can approve an article.
    */
    #[Route('/article/{id}/approve', name: 'approve_article', methods: ['GET'])]
    public function approveArticle(int $id, ArticleRepository $articleRepository): Response
    {
        if (!$this->isGranted('ROLE_REDAKTOR') && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Nemáte oprávnění schválit článek.');
        }
    
        $article = $articleRepository->find($id);
    
        if (!$article) {
            throw $this->createNotFoundException('Článek nenalezen.');
        }
    
        $article->setStatus('approved');
        $this->entityManager->flush();
    
        $userAction = new UserAction($this->getUser()->getUsername(), 'Schválil článek: ' . $article->getTitle());
        $this->entityManager->persist($userAction);
        $this->entityManager->flush();
    
        return $this->redirectToRoute('offered_articles');
    }
    

    /*
    * REDAKTOR or ADMIN can reject an article with a reason.
    */
    #[Route('/article/{id}/reject', name: 'reject_article', methods: ['POST'])]
    public function rejectArticle(int $id, ArticleRepository $articleRepository, Request $request): Response
    {
        if (!$this->isGranted('ROLE_REDAKTOR') && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Nemáte oprávnění odmítnout článek.');
        }
    
        $article = $articleRepository->find($id);
    
        if (!$article) {
            throw $this->createNotFoundException('Článek nenalezen.');
        }
    
        $reason = $request->request->get('reason');
        $article->setRejectionReason($reason);
    
        $article->setStatus('rejected');
        $this->entityManager->flush();
    
        $userAction = new UserAction($this->getUser()->getUsername(), 'Zamítl článek: ' . $article->getTitle() . ' s důvodem: ' . $reason);
        $this->entityManager->persist($userAction);
        $this->entityManager->flush();
    
        return $this->redirectToRoute('offered_articles');
    }
    

    /*
    * Only ADMIN can delete an article.
    */
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
    
        $userAction = new UserAction($this->getUser()->getUsername(), 'Smazal článek: ' . $article->getTitle());
        $this->entityManager->persist($userAction);
    
        $this->entityManager->remove($article);
        $this->entityManager->flush();
    
        return $this->redirectToRoute('article_list');
    }
    

    /*
    * REDAKTOR can view article details for review purposes.
    */
    #[Route('/redaktor-article/{id}', name: 'redaktor_article')]
    public function showArticle(int $id, ArticleRepository $articleRepository): Response
    {
        if (!$this->isGranted('ROLE_REDAKTOR') && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Nemáte oprávnění zobrazit tento článek pro recenzi.');
        }

        $article = $articleRepository->find($id);

        if (!$article) {
            throw $this->createNotFoundException('Článek nenalezen.');
        }
        return $this->render('article/redaktor_detail.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/article/{id}/archive', name: 'article_archive', methods: ['POST'])]
    public function archiveArticle(int $id, ArticleRepository $articleRepository): Response
    {
        if (!$this->isGranted('ROLE_REDAKTOR') && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Nemáte oprávnění archivovat článek.');
        }
    
        $article = $articleRepository->find($id);
    
        if (!$article) {
            throw $this->createNotFoundException('Článek nenalezen.');
        }
    
        $article->setStatus('archived');
        $this->entityManager->flush();
    
        $userAction = new UserAction($this->getUser()->getUsername(), 'Archivoval článek: ' . $article->getTitle());
        $this->entityManager->persist($userAction);
        $this->entityManager->flush();
    
        return $this->redirectToRoute('article_list'); 
    }

    #[Route('/archived-articles', name: 'archived_articles')]
    public function archivedArticles(ArticleRepository $articleRepository): Response
    {
        if (!$this->isGranted('ROLE_REDAKTOR') && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Nemáte oprávnění zobrazit archivované články.');
        }

        $articles = $articleRepository->findBy(['status' => 'archived'], ['createdAt' => 'DESC']);

        return $this->render('article/archived_articles.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/article/{id}/restore', name: 'article_restore', methods: ['POST'])]
    public function restore(Article $article, ArticleRepository $articleRepository): RedirectResponse
    {
        if (!$this->isGranted('ROLE_REDAKTOR') && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Nemáte oprávnění restarovat archivované články.');
        }
    
        $article->setStatus('approved'); 
        $this->entityManager->persist($article); 
        $this->entityManager->flush(); 
    
        return $this->redirectToRoute('archived_articles');
    }
}
