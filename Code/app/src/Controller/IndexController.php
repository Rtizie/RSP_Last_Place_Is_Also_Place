<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security; // Správný namespace

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(Security $security): Response
    {
        // Získání přihlášeného uživatele
        $user = $security->getUser();

        return $this->render('index/index.html.twig', [
            'user' => $user,
        ]);
    }
}
