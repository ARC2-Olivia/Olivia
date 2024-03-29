<?php

namespace App\Security;

use App\Exception\UserNotActivatedException;
use App\Exception\UserNotFoundException;
use App\Form\Security\LoginType;
use App\Repository\UserRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    const LOGIN_ROUTE = 'security_login';

    private ?UserRepository $userRepository = null;
    private ?RouterInterface $router = null;
    private ?CsrfTokenManagerInterface $csrfTokenManager = null;
    private ?UserPasswordHasherInterface $passwordHasher = null;
    private ?FormFactoryInterface $formFactory = null;

    public function __construct(?UserRepository $userRepository,
                                ?RouterInterface $router,
                                ?CsrfTokenManagerInterface $csrfTokenManager,
                                ?UserPasswordHasherInterface $passwordHasher,
                                ?FormFactoryInterface $formFactory
    )
    {
        $this->userRepository = $userRepository;
        $this->router = $router;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordHasher = $passwordHasher;
        $this->formFactory = $formFactory;
    }


    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate(self::LOGIN_ROUTE);
    }

    public function authenticate(Request $request): Passport
    {
        $loginData = new LoginData();
        $form = $this->formFactory->create(LoginType::class, $loginData);
        $form->handleRequest($request);

        $userBadge = new UserBadge($loginData->getEmail() ?? '', function ($identifier) {
            $user = $this->userRepository->findOneBy(['email' => $identifier]);
            if (null === $user) throw new UserNotFoundException();
            if (!$user->isActivated()) throw new UserNotActivatedException();
            return $user;
        });
        $credentials = new PasswordCredentials($loginData->getPlainPassword() ?? '');
        $csrfTokenBadge = new CsrfTokenBadge(LoginType::CSRF_TOKEN_ID, $form->get('_csrf_token')->getData());
        return new Passport($userBadge, $credentials, [$csrfTokenBadge]);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $target = $this->getTargetPath($request->getSession(), $firewallName);
        if ($target) return new RedirectResponse($target);
        return new RedirectResponse($this->router->generate('index'));
    }
}