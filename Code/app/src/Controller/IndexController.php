<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\UserRepository; // Přidání UserRepository
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(ArticleRepository $articleRepository, UserRepository $userRepository, Security $security): Response
    {
        $user = $security->getUser();

        $latestArticle = $articleRepository->findOneBy(['status' => 'approved'], ['createdAt' => 'DESC']);

        $recentArticles = $articleRepository->findBy(['status' => 'approved'], ['createdAt' => 'DESC'], 3, 1);

        $users = $userRepository->findBy([], ['registeredAt' => 'DESC']);

        return $this->render('index/index.html.twig', [
            'user' => $user,
            'latestArticle' => $latestArticle,
            'recentArticles' => $recentArticles,
            'users' => $users,
        ]);
    }

    #[Route('/about-us', name: 'about_us')]
    public function about(): Response
    {
        return $this->render('about_us.html.twig');
    }
}
