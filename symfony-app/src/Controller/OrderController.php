<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OrderController extends AbstractController
{
    #[Route('/order', name: 'app_order', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(Request $request, Connection $connection, KernelInterface $kernel): Response
    {
        $orderId = $request->query->getInt('id');
        $user = $this->getUser();

        if ($orderId <= 0 || !is_object($user) || !method_exists($user, 'getId')) {
            return $this->redirectToRoute('app_account');
        }

        // Only the owner of an order may view its details.
        $order = $connection->fetchAssociative(
            'SELECT id, total_amount, payment_method, status, delivery_address,
                    order_notes, created_at
             FROM orders
             WHERE id = ? AND user_id = ?
             LIMIT 1',
            [$orderId, $user->getId()]
        );

        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }

        $items = $connection->fetchAllAssociative(
            'SELECT oi.product_id, oi.quantity, oi.price,
                    p.name, p.series, p.image
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC',
            [$orderId]
        );

        foreach ($items as &$item) {
            $item['image_path'] = $this->resolveProductImage(
                $item['image'] ?? null,
                $kernel->getProjectDir()
            );
        }
        unset($item);

        return $this->render('order/show.html.twig', [
            'order' => $order,
            'items' => $items,
        ]);
    }

    private function resolveProductImage(?string $image, string $projectDir): ?string
    {
        if ($image === null || trim($image) === '') {
            return null;
        }

        $relative = ltrim(str_replace('\\', '/', trim($image)), '/');
        $publicDir = $projectDir . '/public';

        if (str_starts_with($relative, 'assets/') && is_file($publicDir . '/' . $relative)) {
            return '/' . $relative;
        }

        $baseName = pathinfo(basename($relative), PATHINFO_FILENAME);
        foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $extension) {
            $assetPath = $publicDir . '/assets/collections/' . $baseName . '.' . $extension;
            if (is_file($assetPath)) {
                return '/assets/collections/' . rawurlencode($baseName) . '.' . $extension;
            }
        }

        return null;
    }
}
