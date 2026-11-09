<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(Connection $connection, KernelInterface $kernel): Response
    {
        $loggedInUser = $this->getUser();

        if (!$loggedInUser instanceof User || $loggedInUser->getId() === null) {
            return $this->redirectToRoute('app_login');
        }

        $userId = $loggedInUser->getId();

        $user = $connection->fetchAssociative(
            'SELECT id, first_name, last_name, email, reliability_score,
                    completed_preorders, cancelled_preorders, created_at
             FROM users
             WHERE id = ?',
            [$userId]
        );

        if (!$user) {
            return $this->redirectToRoute('app_logout');
        }

        $orders = $connection->fetchAllAssociative(
            'SELECT id, total_amount, payment_method, status, delivery_address,
                    order_notes, created_at
             FROM orders
             WHERE user_id = ?
             ORDER BY created_at DESC',
            [$userId]
        );

        $reviewedOrders = [];
        $reviewRows = $connection->fetchAllAssociative(
            'SELECT order_id, product_id FROM reviews WHERE user_id = ?',
            [$userId]
        );

        foreach ($reviewRows as $review) {
            $reviewedOrders[$review['order_id'] . '-' . $review['product_id']] = true;
        }

        $orderIds = array_map(static fn (array $order): int => (int) $order['id'], $orders);
        $orderProducts = [];

        if ($orderIds !== []) {
            $items = $connection->executeQuery(
                'SELECT oi.order_id, oi.product_id, oi.quantity, oi.price,
                        p.name, p.series, p.image
                 FROM order_items oi
                 LEFT JOIN products p ON p.id = oi.product_id
                 WHERE oi.order_id IN (?)
                 ORDER BY oi.order_id ASC, oi.id ASC',
                [$orderIds],
                [ArrayParameterType::INTEGER]
            )->fetchAllAssociative();

            foreach ($items as $item) {
                $item['image_path'] = $this->resolveProductImage($item['image'] ?? null, $kernel->getProjectDir());
                $orderProducts[(int) $item['order_id']][] = $item;
            }
        }

        foreach ($orders as &$order) {
            $orderId = (int) $order['id'];
            $order['items'] = $orderProducts[$orderId] ?? [];
            $order['can_review'] = false;

            if ($order['status'] === 'Completed') {
                foreach ($order['items'] as $item) {
                    $reviewKey = $orderId . '-' . (int) $item['product_id'];
                    if (!isset($reviewedOrders[$reviewKey])) {
                        $order['can_review'] = true;
                        break;
                    }
                }
            }
        }
        unset($order);

        $preorders = $connection->fetchAllAssociative(
            'SELECT pr.id, pr.product_id, pr.quantity, pr.unit_price, pr.total_amount,
                    pr.deposit_amount, pr.deposit_paid_amount, pr.deposit_status,
                    pr.deposit_paid_at, pr.remaining_balance, pr.balance_paid_amount,
                    pr.balance_status, pr.balance_paid_at, pr.deposit_due_date,
                    pr.balance_due_date, pr.expected_release_date, pr.status,
                    pr.release_status, pr.notes, pr.created_at,
                    p.name AS product_name, p.series, p.image
             FROM preorders pr
             INNER JOIN products p ON p.id = pr.product_id
             WHERE pr.user_id = ?
             ORDER BY pr.created_at DESC',
            [$userId]
        );

        foreach ($preorders as &$preorder) {
            $preorder['image_path'] = $this->resolveProductImage($preorder['image'] ?? null, $kernel->getProjectDir());
        }
        unset($preorder);

        return $this->render('account/index.html.twig', [
            'user' => $user,
            'orders' => $orders,
            'preorders' => $preorders,
        ]);
    }

    private function resolveProductImage(?string $image, string $projectDir): ?string
    {
        if ($image === null || trim($image) === '') {
            return null;
        }

        $image = str_replace('\\', '/', trim($image));
        $relative = ltrim($image, '/');
        $publicDir = $projectDir . '/public';

        if (str_starts_with($relative, 'assets/') && is_file($publicDir . '/' . $relative)) {
            return '/' . $relative;
        }

        $baseName = pathinfo(basename($relative), PATHINFO_FILENAME);

        foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $extension) {
            $assetPath = $publicDir . '/assets/' . $baseName . '.' . $extension;
            if (is_file($assetPath)) {
                return '/assets/' . $baseName . '.' . $extension;
            }

            $rootPath = $publicDir . '/' . $baseName . '.' . $extension;
            if (is_file($rootPath)) {
                return '/' . $baseName . '.' . $extension;
            }
        }

        return null;
    }
}
