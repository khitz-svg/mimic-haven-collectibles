<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/admin', name: 'app_admin_dashboard', methods: ['GET'])]
#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_STAFF")'))]
class AdminDashboardController extends AbstractController
{
    public function __invoke(Connection $connection, Security $security): Response
    {
        $productCount = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM products'
        );

        $customerCount = (int) $connection->fetchOne(
            "SELECT COUNT(*) FROM users WHERE role = 'customer'"
        );

        $orderCount = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM orders'
        );

        $preorderCount = (int) $connection->fetchOne(
            "SELECT COUNT(*) FROM preorders WHERE status != 'Cancelled'"
        );

        $recentOrders = $connection->fetchAllAssociative(
            'SELECT
                o.id,
                o.total_amount,
                o.status,
                o.created_at,
                u.first_name,
                u.last_name
             FROM orders o
             INNER JOIN users u ON o.user_id = u.id
             ORDER BY o.created_at DESC
             LIMIT 5'
        );

        $currentUser = $security->getUser();

        $firstName = $currentUser instanceof User
            ? $currentUser->getFirstName()
            : 'Admin';

        return $this->render('admin/dashboard.html.twig', [
            'firstName' => $firstName,
            'productCount' => $productCount,
            'customerCount' => $customerCount,
            'orderCount' => $orderCount,
            'preorderCount' => $preorderCount,
            'recentOrders' => $recentOrders,
        ]);
    }
}
