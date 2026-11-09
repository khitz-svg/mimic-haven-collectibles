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
    public function __construct(private Security $security)
    {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        $cartKey = $user instanceof User
            ? 'mimicHavenCart_' . $user->getId()
            : 'mimicHavenGuestCart';

        $cookie = Cookie::create('mimicHavenCartStorageKey')
            ->withValue($cartKey)
            ->withExpires(new \DateTimeImmutable('+30 days'))
            ->withPath('/')
            ->withSecure($event->getRequest()->isSecure())
            ->withHttpOnly(false)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $event->getResponse()->headers->setCookie($cookie);
    }
}
