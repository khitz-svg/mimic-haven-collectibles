<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminOrdersController extends AbstractController
{
    private const ALLOWED_STATUSES = [
        'Pending',
        'Confirmed',
        'Processing',
        'Shipped',
        'Completed',
        'Cancelled',
    ];

    #[Route('/admin/orders', name: 'app_admin_orders', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, Connection $connection): Response
    {
        if ($request->isMethod('POST')) {
            $orderId = $request->request->getInt('order_id');
            $newStatus = $request->request->getString('status');
            $token = $request->request->getString('_token');

            if ($orderId < 1) {
                $this->addFlash('error', 'Invalid order.');
            } elseif (!$this->isCsrfTokenValid('admin_order_status_' . $orderId, $token)) {
                $this->addFlash('error', 'Invalid security token. Please try again.');
            } elseif (!in_array($newStatus, self::ALLOWED_STATUSES, true)) {
                $this->addFlash('error', 'Invalid order status.');
            } else {
                $orderExists = $connection->fetchOne(
                    'SELECT id FROM orders WHERE id = ?',
                    [$orderId]
                );

                if (!$orderExists) {
                    $this->addFlash('error', 'Order not found.');
                } else {
                    $connection->update(
                        'orders',
                        ['status' => $newStatus],
                        ['id' => $orderId]
                    );
                    $this->addFlash('success', sprintf('Order #%d updated to %s.', $orderId, $newStatus));
                }
            }

            return $this->redirectToRoute('app_admin_orders');
        }

        $orders = $connection->fetchAllAssociative(
            'SELECT
                o.id,
                o.total_amount,
                o.payment_method,
                o.status,
                o.delivery_address,
                o.created_at,
                u.first_name,
                u.last_name,
                u.email
             FROM orders o
             INNER JOIN users u ON o.user_id = u.id
             ORDER BY o.created_at DESC'
        );

        return $this->render('admin/orders.html.twig', [
            'orders' => $orders,
            'allowedStatuses' => self::ALLOWED_STATUSES,
        ]);
    }
}
