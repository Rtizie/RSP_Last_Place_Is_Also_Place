<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(ArticleRepository $articleRepository, Security $security): Response
    {
        $user = $security->getUser();

        // Získání nejnovějšího schváleného článku
        $latestArticle = $articleRepository->findOneBy(['status' => 'approved'], ['createdAt' => 'DESC']);

        // Získání dalších tří schválených článků
        $recentArticles = $articleRepository->findBy(['status' => 'approved'], ['createdAt' => 'DESC'], 3, 1);

        return $this->render('index/index.html.twig', [
            'user' => $user,
            'latestArticle' => $latestArticle,
            'recentArticles' => $recentArticles,
        ]);
    }
}
