<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Keep the same account-specific cart key used by the product page.
        $userId = $request->getSession()->get('user_id');
        $cartStorageKey = is_numeric($userId) && (int) $userId > 0
            ? 'mimicHavenCart_' . (int) $userId
            : 'mimicHavenGuestCart';

        return $this->render('cart/index.html.twig', [
            'cartStorageKey' => $cartStorageKey,
        ]);
    }
}
