<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token', '');

        if (is_string($email) && $request->hasSession()) {
            $request->getSession()->set(
                SecurityRequestAttributes::LAST_USERNAME,
                trim($email)
            );
        }

        return new Passport(
            new UserBadge(is_string($email) ? trim($email) : ''),
            new PasswordCredentials(is_string($password) ? $password : ''),
            [
                new CsrfTokenBadge(
                    'authenticate',
                    is_string($csrfToken) ? $csrfToken : ''
                )
            ]
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        $user = $token->getUser();

        if (
            $user instanceof User &&
            in_array($user->getRole(), ['admin', 'staff'], true)
        ) {
            return new RedirectResponse('/admin');
        }

        return new RedirectResponse(
            $this->urlGenerator->generate('app_home')
        );
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login');
    }
}
