<?php

namespace App\Security;

use App\Service\ApiResponseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class ApiLoginAuthenticator extends AbstractAuthenticator
{

    private ApiResponseService $apiResponseService;

    public function __construct(ApiResponseService $apiResponseService)
    {
        $this->apiResponseService = $apiResponseService;
    }

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/api/login' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?JsonResponse
    {
        $user = $token->getUser();

        return $this->apiResponseService->success([
            'email' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ], 'Connexion réussie');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {

        return $this->apiResponseService->error('Échec de la connexion', Response::HTTP_UNAUTHORIZED, [
            'error' => $exception->getMessage(),
        ]);
    }
}