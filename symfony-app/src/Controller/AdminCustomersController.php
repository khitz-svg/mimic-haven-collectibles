<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminCustomersController extends AbstractController
{
    #[Route('/admin/customers', name: 'app_admin_customers', methods: ['GET'])]
    public function __invoke(Connection $connection): Response
    {
        // Keep the original page behavior: refresh customer activity counts
        // and reliability scores from the current pre-order statuses.
        $connection->executeStatement(
            "UPDATE users u
             LEFT JOIN (
                 SELECT
                     user_id,
                     COUNT(CASE WHEN status = 'Completed' THEN 1 END) AS completed_count,
                     COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) AS cancelled_count
                 FROM preorders
                 GROUP BY user_id
             ) activity ON activity.user_id = u.id
             SET
                 u.completed_preorders = COALESCE(activity.completed_count, 0),
                 u.cancelled_preorders = COALESCE(activity.cancelled_count, 0),
                 u.reliability_score = GREATEST(0, 100 - (COALESCE(activity.cancelled_count, 0) * 20))
             WHERE u.role = 'customer'"
        );

        $customers = $connection->fetchAllAssociative(
            "SELECT
                id,
                first_name,
                last_name,
                email,
                reliability_score,
                completed_preorders,
                cancelled_preorders,
                created_at
             FROM users
             WHERE role = 'customer'
             ORDER BY reliability_score DESC, created_at DESC"
        );

        foreach ($customers as &$customer) {
            $score = (float) $customer['reliability_score'];

            if ($score >= 90) {
                $customer['reliability_label'] = 'Excellent';
                $customer['reliability_class'] = 'excellent';
            } elseif ($score >= 70) {
                $customer['reliability_label'] = 'Good';
                $customer['reliability_class'] = 'good';
            } elseif ($score >= 50) {
                $customer['reliability_label'] = 'Fair';
                $customer['reliability_class'] = 'fair';
            } else {
                $customer['reliability_label'] = 'Low';
                $customer['reliability_class'] = 'low';
            }
        }
        unset($customer);

        return $this->render('admin/customers.html.twig', [
            'customers' => $customers,
        ]);
    }
}
