<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/product', name: 'app_product', methods: ['GET'])]
    public function show(Request $request, EntityManagerInterface $entityManager): Response
    {
        $productId = $request->query->getInt('id');

        if ($productId < 1) {
            throw $this->createNotFoundException('Product not found.');
        }

        $product = $entityManager->getRepository(Product::class)->find($productId);

        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found.');
        }

        $stock = max(0, (int) ($product->getStock() ?? 0));
        $isInStock = $product->getAvailability() === 'In Stock' && $stock > 0;
        $isOutOfStock = $product->getAvailability() === 'In Stock' && $stock <= 0;
        $isPreOrder = $product->getAvailability() === 'Pre-Order';

        // Keep the original Mimic Haven account-specific cart storage key.
        $userId = $request->getSession()->get('user_id');
        $cartStorageKey = is_numeric($userId) && (int) $userId > 0
            ? 'mimicHavenCart_' . (int) $userId
            : 'mimicHavenGuestCart';

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'stock' => $stock,
            'isInStock' => $isInStock,
            'isOutOfStock' => $isOutOfStock,
            'isPreOrder' => $isPreOrder,
            'maxQuantity' => $isInStock ? min(20, $stock) : 1,
            'cartStorageKey' => $cartStorageKey,
        ]);
    }
}
