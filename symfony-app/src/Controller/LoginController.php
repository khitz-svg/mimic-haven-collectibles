<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'updateCartStorageCookie',
        ];
    }

    /*
     * Automatically update the cart storage cookie
     * based on the currently authenticated customer.
     */
    public function updateCartStorageCookie(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Guests use a separate cart.
        $cartStorageKey = 'mimicHavenGuestCart';

        // Get the currently logged-in Symfony user.
        $user = $this->tokenStorage->getToken()?->getUser();

        if ($user instanceof User && $user->getId() !== null) {
            $cartStorageKey = 'mimicHavenCart_' . (int) $user->getId();
        }

        // Update the cookie used by the main script.js.
        $cookie = Cookie::create(
            'mimicHavenCartStorageKey',
            $cartStorageKey
        )
            ->withExpires(new \DateTimeImmutable('+30 days'))
            ->withPath('/')
            ->withSecure($request->isSecure())
            ->withHttpOnly(false)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($cookie);
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        $user = $this->getUser();

        if ($user instanceof User) {
            return $user->getRole() === 'admin'
                ? $this->redirect('/admin')
                : $this->redirectToRoute('app_home');
        }

        return $this->render('auth/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): never
    {
        // This route is intercepted by Symfony's logout firewall listener.
        throw new \LogicException('The logout route should be handled by the firewall.');
    }
}
