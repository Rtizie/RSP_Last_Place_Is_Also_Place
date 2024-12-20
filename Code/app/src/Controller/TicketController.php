<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\TicketResponse;
use App\Form\TicketType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TicketController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function createTicket(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ticket = new Ticket();
        $ticket->setUser($this->getUser());

        if (!$ticket->getStatus()) {
            $ticket->setStatus('new');
        }

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$ticket->getStatus()) {
                $ticket->setStatus('new');
            }

            $entityManager->persist($ticket);
            $entityManager->flush();

            return $this->redirectToRoute('ticket_success');
        }

        return $this->render('ticket/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/ticket/success', name: 'ticket_success')]
    public function success(): Response
    {
        return $this->render('ticket/success.html.twig', [
            'message' => 'Váš ticket byl úspěšně vytvořen.',
        ]);
    }

    #[Route('/admin/tickets', name: 'admin_tickets')]
    public function listTickets(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $tickets = $entityManager->getRepository(Ticket::class)->findAll();

        foreach ($tickets as $ticket) {
            $responses = $ticket->getResponses()->toArray();
            $userResponses = array_filter($responses, fn($response) => $response->getUser() && in_array('ROLE_USER', $response->getUser()->getRoles()));
            usort($userResponses, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
            $ticket->lastUserResponse = $userResponses ? $userResponses[0] : null;
        }

        return $this->render('admin/tickets.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/admin/ticket/{id}/reply', name: 'admin_ticket_reply', methods: ['POST'])]
    public function adminReplyToTicket(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $ticket = $entityManager->getRepository(Ticket::class)->find($id);
        if (!$ticket) {
            throw $this->createNotFoundException('Tiket nenalezen');
        }

        $replyMessage = $request->request->get('replyMessage');
        if ($replyMessage) {
            $response = new TicketResponse();
            $response->setTicket($ticket);
            $response->setMessage($replyMessage);
            $response->setCreatedAt(new \DateTime());
            $response->setUser($this->getUser());

            $entityManager->persist($response);
            $ticket->setStatus('waiting_for_reply');
            $entityManager->persist($ticket);
            $entityManager->flush();

            $this->addFlash('success', 'Odpověď byla úspěšně odeslána.');
            return $this->redirectToRoute('admin_tickets');
        }

        $this->addFlash('error', 'Odpověď se nezdařila. Zkuste to znovu.');
        return $this->redirectToRoute('admin_tickets');
    }

    #[Route('/admin/ticket/{id}/close', name: 'admin_ticket_close')]
    public function closeTicket(int $id, EntityManagerInterface $entityManager): Response
    {
        $ticket = $entityManager->getRepository(Ticket::class)->find($id);

        if (!$ticket) {
            throw $this->createNotFoundException('Tiket nenalezen');
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $ticket->setStatus(Ticket::STATUS_CLOSED);

        $entityManager->persist($ticket);
        $entityManager->flush();

        $this->addFlash('success', 'Tiket byl úspěšně uzavřen.');

        return $this->redirectToRoute('admin_tickets');
    }

    #[Route('/my-tickets', name: 'user_tickets')]
    public function listUserTickets(EntityManagerInterface $entityManager): Response
    {
        $tickets = $entityManager->getRepository(Ticket::class)->findBy(['user' => $this->getUser()]);

        return $this->render('ticket/ticket_list.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/my-ticket/{id}', name: 'user_ticket_view')]
    public function viewTicket(int $id, EntityManagerInterface $entityManager): Response
    {
        $ticket = $entityManager->getRepository(Ticket::class)->find($id);

        if (!$ticket || $ticket->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Tiket nenalezen nebo nemáte k němu přístup.');
        }

        return $this->render('ticket/ticket_view.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/ticket/{id}/reply', name: 'user_ticket_reply', methods: ['POST'])]
    public function userReplyToTicket(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $ticket = $entityManager->getRepository(Ticket::class)->find($id);

        if (!$ticket || $ticket->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Tiket nenalezen nebo nemáte k němu přístup.');
        }

        $replyMessage = $request->request->get('replyMessage');
        if ($replyMessage) {
            $ticket->setMessage($replyMessage);
            $ticket->setStatus('waiting_for_reply');
            $entityManager->persist($ticket);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_ticket_view', ['id' => $ticket->getId()]);
    }

    #[Route('/admin/ticket/{id}', name: 'admin_ticket_view')]
    public function viewAdminTicket(int $id, EntityManagerInterface $entityManager): Response
    {
        $ticket = $entityManager->getRepository(Ticket::class)->find($id);

        if (!$ticket) {
            throw $this->createNotFoundException('Tiket nenalezen');
        }

        $responses = $entityManager->getRepository(TicketResponse::class)->findBy(['ticket' => $ticket]);
        $adminResponses = array_filter($responses, fn($response) => in_array('ROLE_ADMIN', $response->getUser()->getRoles()));

        return $this->render('admin/ticket_view.html.twig', [
            'ticket' => $ticket,
            'responses' => $adminResponses,
        ]);
    }
}
