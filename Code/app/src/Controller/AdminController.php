<?php
// src/Controller/AdminController.php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\UserAction; 

class AdminController extends AbstractController
{
    #[Route('/admin/users', name: 'admin_users')]
    #[IsGranted('ROLE_ADMIN')]
    public function listUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        
        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/{id}/update-roles', name: 'admin_update_roles', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateRoles(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $roles = $request->request->all('roles');
    
        if (!is_array($roles)) {
            $roles = [];
        }
    
        $user->setRoles($roles);
        $entityManager->flush();

        $userAction = new UserAction($user->getUsername(), 'Aktualizace rolí'); 
        $entityManager->persist($userAction);
        $entityManager->flush();

        $this->addFlash('success', 'Role uživatele byly úspěšně aktualizovány.');
    
        return $this->redirectToRoute('admin_users');
    }
}
