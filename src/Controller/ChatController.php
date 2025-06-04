<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageType;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use App\Service\MercureService;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chat')]
final class ChatController extends AbstractController
{
    private MercureService $mercureService;

    public function __construct(MercureService $mercureService)
    {
        $this->mercureService = $mercureService;
    }

    #[Route('/', name: 'chat_index')]
    public function index(ChatRepository $chatRepository, EntityManagerInterface $entityManager): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        $users = $entityManager->getRepository(User::class)->findAll();
        foreach ($users as $user) {

            if ($currentUser->getId() !== $user->getId()) {
                $chat = $chatRepository->findChat($currentUser->getId(), $user->getId());
                if (!$chat) {
                    $chat = new Chat();
                    $chat->setUserOne($currentUser);
                    $chat->setUserTwo($user);
                    $chat->setCreatedAt(new \DateTimeImmutable());

                    $entityManager->persist($chat);
                    $entityManager->flush();
                }
                $user->addChat($chat);
            }
        }

        return $this->render('chat/index.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/conversation/{receiverId}', name: 'chat_conversation')]
    public function conversation(
        int                    $receiverId,
        ChatRepository         $chatRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        $receiver = $entityManager->getRepository(User::class)->find($receiverId);
        if (!$receiver) {
            throw $this->createNotFoundException('Receiver not found');
        }

        $chat = $chatRepository->findChat($currentUser->getId(), $receiverId);

        if (!$chat) {
            $chat = new Chat();
            $chat->setUserOne($currentUser);
            $chat->setUserTwo($receiver);
            $chat->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($chat);
            $entityManager->flush();
        }

        return $this->render('chat/messages.html.twig', [
            'chatId' => $chat->getId(),
            'messages' => $chat->getMessages(),
            'receiver' => $receiver
        ]);
    }

    #[Route('/send', name: 'chat_send', methods: ['POST'])]
    public function send(
        Request                $request,
        EntityManagerInterface $entityManager,
        MercureService         $mercureService): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $content = $request->request->get('content');
        $receiverId = $request->request->get('receiver');
        $chatId = $request->request->get('chat');

        if (!$content || !$receiverId) {
            return $this->json(['error' => 'Missing data'], Response::HTTP_BAD_REQUEST);
        }

        $receiver = $entityManager->getRepository(User::class)->find($receiverId);
        $chat = $entityManager->getRepository(Chat::class)->find($chatId);
        if (!$receiver) {
            return $this->json(['error' => 'Chat not found or Receiver not found'], Response::HTTP_NOT_FOUND);
        }

        $message = new Message();
        $message->setSender($currentUser);
        $message->setChat($chat);
        $message->setContent($content);
        $message->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($message);
        $entityManager->flush();

//        $httpClient = HttpClient::create();
//
//        $mercureUrl = $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost:3001/.well-known/mercure';
//        $jwt = $_ENV['MERCURE_JWT_SECRET'] ?? '';
//        $data = [
//            'topic' => "chat/{$chat->getId()}",
//            'data' => json_encode([
//                'id' => $message->getId(),
//                'content' => $message->getContent(),
//                'userId' => $message->getSender()->getId(),
//                'username' => $message->getSender()->getEmail(),
//                'createdAt' => $message->getCreatedAt()->format('c'),
//            ]),
//        ];
//
//        $responseMercure = $httpClient->request('POST', $mercureUrl, [
//            'headers' => [
//                'Authorization' => 'Bearer ' . $jwt,
//                'Content-Type' => 'application/x-www-form-urlencoded',
//            ],
//            'body' => http_build_query($data),
//        ]);
//
//        if ($responseMercure->getStatusCode() !== 200) {
//            return $this->json([
//                'error' => 'Mercure publish failed',
//                'details' => $responseMercure->getContent(false),
//            ], Response::HTTP_INTERNAL_SERVER_ERROR);
//        }
        $mercureService->publishChatMessage($chat->getId(),
            [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'userId' => $message->getSender()->getId(),
                'username' => $message->getSender()->getEmail(),
                'createdAt' => $message->getCreatedAt()->format('c'),
            ]);
        
        return $this->json(['success' => true]);
    }

    private function generateMercureToken(array $subscribe = [], array $publish = []): string
    {
        $payload = [
            'mercure' => [
                'subscribe' => $subscribe,
                'publish' => $publish
            ]
        ];

        $secret = $_ENV['MERCURE_JWT_SECRET'];
        if (!is_string($secret) || empty($secret)) {
            throw new \RuntimeException('MERCURE_JWT_SECRET environment variable must be set and be a non-empty string.');
        }
        return JWT::encode(
            $payload,
            $secret,
            'HS256'
        );
    }

    #[Route('/mercure-token', methods: ['GET'])]
    public function getMercureToken(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $token = $this->generateMercureToken([
            "user/{$user->getId()}",
            "chat/*"
        ]);

        return new JsonResponse(['token' => $token]);
    }
}
