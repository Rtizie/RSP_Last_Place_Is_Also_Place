<?php

// src/Controller/LogsController.php

namespace App\Controller;

use App\Repository\UserActionRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class LogsController extends AbstractController
{
    #[Route('/admin/logs', name: 'admin_logs')]
    public function userActions(UserActionRepository $userActionRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Nemáte dostatečná oprávnění k zobrazení této stránky.');
        }

        $userActions = $userActionRepository->findBy([], ['timestamp' => 'DESC']); 

        return $this->render('admin/logs.html.twig', [
            'userActions' => $userActions,
        ]);
    }
}
