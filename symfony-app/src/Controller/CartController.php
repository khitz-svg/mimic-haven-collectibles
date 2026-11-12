<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart', methods: ['GET'])]
    public function index(): Response
    {
        // Get the currently authenticated Symfony user.
        $user = $this->getUser();

        // Assign the cart to the correct account.
        if ($user instanceof User && $user->getId() !== null) {
            $cartStorageKey = 'mimicHavenCart_' . (int) $user->getId();
        } else {
            $cartStorageKey = 'mimicHavenGuestCart';
        }

        return $this->render('cart/index.html.twig', [
            'cartStorageKey' => $cartStorageKey,
        ]);
    }
}
