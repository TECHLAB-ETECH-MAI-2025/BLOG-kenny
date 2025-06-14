<?php

namespace App\Controller\Api;

use App\Service\ApiResponseService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthApiController extends AbstractController
{
    #[Route('/api/me', methods: ['GET'])]
    public function me(ApiResponseService $apiResponseService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $apiResponseService->error(
                'Utilisateur non connecté',
                Response::HTTP_UNAUTHORIZED,
                ['error' => 'Non connecté']
            );
        }

        return $apiResponseService->success(
            [
                'email' => $user->getUserIdentifier(),
                'roles' => $user->getRoles(),
            ],
            'Utilisateur connecté'
        );
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Ce point ne sera jamais atteint si l'authenticator fonctionne.
        return new JsonResponse(['error' => 'Should be intercepted by authenticator'], 500);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request, TokenStorageInterface $tokenStorage): JsonResponse
    {
        if ($request->hasSession()) {
            $request->getSession()->invalidate();
        }

        $tokenStorage->setToken(null); // ❗ Supprime l'utilisateur de la sécurité

        return new JsonResponse([
            'message' => 'Déconnexion réussie'
        ]);
    }

}