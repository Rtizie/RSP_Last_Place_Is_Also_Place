<?php
// src/Controller/ChatController.php
namespace App\Controller;

use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ChatController extends AbstractController
{
    #[Route('/chat', name: 'chat', methods: ['GET'])]
    public function index(MessageRepository $messageRepository): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_REDAKTOR') && !$this->isGranted('ROLE_REVIEWER')) {
            throw new AccessDeniedException('Nemáte oprávnění zobrazit chat.');
        }

        $messages = $messageRepository->findAll();

        $messagesData = array_map(function ($message) {
            return [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
                'author' => [
                    'username' => $message->getAuthor()->getUsername(),
                ],
            ];
        }, $messages);

        return $this->json($messagesData);
    }

    #[Route('/chat', name: 'chat_send', methods: ['POST'])]
    public function send(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_REDAKTOR') && !$this->isGranted('ROLE_REVIEWER')) {
            throw new AccessDeniedException('Nemáte oprávnění odeslat zprávu.');
        }

        $data = json_decode($request->getContent(), true);

        $message = new Message();
        $message->setAuthor($this->getUser());
        $message->setContent($data['content']);
        $message->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($message);
        $entityManager->flush();

        return $this->json(['status' => 'Message sent']);
    }
}
