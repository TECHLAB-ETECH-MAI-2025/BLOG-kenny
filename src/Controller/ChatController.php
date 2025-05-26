<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageType;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chat')]
final class ChatController extends AbstractController
{
    #[Route('/', name: 'chat_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('chat/index.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/conversation/{receiverId}', name: 'chat_conversation')]
    public function conversation(
        int                    $receiverId,
        MessageRepository      $messageRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        $receiver = $entityManager->getRepository(User::class)->find($receiverId);
        if (!$receiver) {
            throw $this->createNotFoundException('Receiver not found');
        }

        $messages = $messageRepository->findConversation($currentUser->getId(), $receiverId);

        return $this->render('chat/messages.html.twig', [
            'messages' => $messages,
            'receiver' => $receiver
        ]);
    }

    #[Route('/send', name: 'chat_send', methods: ['POST'])]
    public function send(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $content = $request->request->get('content');
        $receiverId = $request->request->get('receiver');

        if (!$content || !$receiverId) {
            return $this->json(['error' => 'Missing data'], Response::HTTP_BAD_REQUEST);
        }

        $receiver = $entityManager->getRepository(User::class)->find($receiverId);
        if (!$receiver) {
            return $this->json(['error' => 'Receiver not found'], Response::HTTP_NOT_FOUND);
        }

        $message = new Message();
        $message->setSender($currentUser);
        $message->setReceiver($receiver);
        $message->setContent($content);
        $message->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($message);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }
}
