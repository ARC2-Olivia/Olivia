<?php

namespace App\Security;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    private ?UserRepository $userRepository = null;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authToken = $request->headers->get('Authorization');
        if (!str_starts_with($authToken, 'Bearer')) {
            throw new AuthenticationException('Invalid authorization scheme. Only "Bearer" is allowed.');
        }

        $apiKey = trim(str_replace('Bearer', '', $authToken));
        if ('' === $apiKey) {
            throw new AuthenticationException('Missing API token.');
        }

        $userBadge = new UserBadge($apiKey, function ($providedApiKey) {
            $user = $this->userRepository->findOneBy(['apiKey' => $providedApiKey]);
            if (null === $user) {
                throw new AuthenticationException('Invalid API key.');
            }
            return $user;
        });
        return new SelfValidatingPassport($userBadge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_FORBIDDEN);
    }
}