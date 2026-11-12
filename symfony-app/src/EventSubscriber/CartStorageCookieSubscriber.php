<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEventListener(event: KernelEvents::RESPONSE, method: 'onKernelResponse')]
class CartStorageCookieSubscriber
{
    public function __construct(
        private Security $security
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Detect logout.
        $isLogout =
            $request->attributes->get('_route') === 'app_logout'
            || $request->getPathInfo() === '/logout';

        // Get the currently authenticated Symfony user.
        $user = $isLogout ? null : $this->security->getUser();

        $isAuthenticated = (
            $user instanceof User &&
            $user->getId() !== null
        );

        // Select the correct cart.
        if ($isAuthenticated) {
            $cartKey = 'mimicHavenCart_' . (int) $user->getId();
        } else {
            $cartKey = 'mimicHavenGuestCart';
        }

        // Set the cart storage cookie.
        $cookie = Cookie::create('mimicHavenCartStorageKey')
            ->withValue($cartKey)
            ->withExpires(new \DateTimeImmutable('+30 days'))
            ->withPath('/')
            ->withSecure($request->isSecure())
            ->withHttpOnly(false)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($cookie);

        // Request the JavaScript cart to reset after logout.
        if ($isLogout) {
            $resetCookie = Cookie::create('mimicHavenResetGuestCart')
                ->withValue('1')
                ->withExpires(new \DateTimeImmutable('+2 minutes'))
                ->withPath('/')
                ->withSecure($request->isSecure())
                ->withHttpOnly(false)
                ->withSameSite(Cookie::SAMESITE_LAX);

            $response->headers->setCookie($resetCookie);
        }

        // Temporary diagnostic headers.
        $response->headers->set(
            'X-Mimic-Cart-Key',
            $cartKey
        );

        $response->headers->set(
            'X-Mimic-Authenticated',
            $isAuthenticated ? 'yes' : 'no'
        );
    }
}
