<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminPreordersController extends AbstractController
{
    private const PREORDER_STATUSES = [
        'Pending',
        'Confirmed',
        'Processing',
        'Completed',
        'Cancelled',
    ];

    private const RELEASE_STATUSES = [
        'Waiting for Manufacturer',
        'Manufacturing',
        'Shipped to Store',
        'Arrived',
        'Ready for Customer',
        'Released',
    ];

    #[Route('/admin/preorders', name: 'app_admin_preorders', methods: ['GET', 'POST'])]
    public function index(Request $request, Connection $connection): Response
    {
    if (
        !$this->isGranted('ROLE_ADMIN') &&
        !$this->isGranted('ROLE_STAFF')
    ) {
        throw $this->createAccessDeniedException();
    }

        if ($request->isMethod('POST')) {
            $this->handlePost($request, $connection);

            return $this->redirectToRoute('app_admin_preorders');
        }

        // Keep the original page behavior: fully paid, non-cancelled pre-orders
        // are synchronized to Completed whenever this admin page is opened.
        $connection->executeStatement(
            "UPDATE preorders
             SET status = 'Completed'
             WHERE deposit_status = 'Paid'
               AND balance_status = 'Paid'
               AND status <> 'Cancelled'"
        );

        $this->updateReliabilityScores($connection);

        $preorders = $connection->fetchAllAssociative(
            "SELECT
                po.id,
                po.quantity,
                po.unit_price,
                po.total_amount,
                po.deposit_amount,
                po.deposit_paid_amount,
                po.deposit_status,
                po.deposit_paid_at,
                po.remaining_balance,
                po.balance_paid_amount,
                po.balance_status,
                po.balance_paid_at,
                po.deposit_due_date,
                po.balance_due_date,
                po.expected_release_date,
                po.status,
                po.release_status,
                po.notes,
                po.created_at,
                u.first_name,
                u.last_name,
                u.email,
                p.name AS product_name,
                p.series,
                p.image
             FROM preorders po
             INNER JOIN users u ON po.user_id = u.id
             INNER JOIN products p ON po.product_id = p.id
             ORDER BY po.created_at DESC"
        );

        foreach ($preorders as &$preorder) {
            $preorder['image_url'] = $this->productImageUrl($preorder['image'] ?? null);
        }
        unset($preorder);

        return $this->render('admin/preorders.html.twig', [
            'preorders' => $preorders,
            'preorder_statuses' => self::PREORDER_STATUSES,
            'release_statuses' => self::RELEASE_STATUSES,
        ]);
    }

    private function handlePost(Request $request, Connection $connection): void
    {
        $action = (string) $request->request->get('action', '');
        $preorderId = filter_var(
            $request->request->get('preorder_id'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if (!$preorderId) {
            $this->addFlash('error', 'Invalid pre-order.');
            return;
        }

        $tokenId = match ($action) {
            'update_status' => 'admin_preorder_status_' . $preorderId,
            'update_release' => 'admin_preorder_release_' . $preorderId,
            'payment' => 'admin_preorder_payment_' . $preorderId,
            default => '',
        };

        if ($tokenId === '') {
            $this->addFlash('error', 'Invalid request.');
            return;
        }

        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token', ''))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');
            return;
        }

        if ($action === 'update_status') {
            $this->updatePreorderStatus($connection, $preorderId, (string) $request->request->get('status', ''));
            return;
        }

        if ($action === 'update_release') {
            $this->updateReleaseStatus(
                $connection,
                $preorderId,
                (string) $request->request->get('release_status', ''),
                trim((string) $request->request->get('expected_release_date', ''))
            );
            return;
        }

        $this->recordPayment(
            $connection,
            $preorderId,
            (string) $request->request->get('payment_type', ''),
            trim((string) $request->request->get('paid_amount', ''))
        );
    }

    private function updatePreorderStatus(Connection $connection, int $preorderId, string $newStatus): void
    {
        if (!in_array($newStatus, self::PREORDER_STATUSES, true)) {
            $this->addFlash('error', 'Invalid pre-order status.');
            return;
        }

        $preorder = $connection->fetchAssociative(
            'SELECT status, deposit_status, balance_status FROM preorders WHERE id = ?',
            [$preorderId]
        );

        if (!$preorder) {
            $this->addFlash('error', 'Pre-order not found.');
            return;
        }

        $fullyPaid = $preorder['deposit_status'] === 'Paid'
            && $preorder['balance_status'] === 'Paid';

        if ($fullyPaid && !in_array($newStatus, ['Completed', 'Cancelled'], true)) {
            $this->addFlash('error', 'This pre-order is fully paid and must remain Completed unless it is Cancelled.');
            return;
        }

        $connection->update('preorders', ['status' => $newStatus], ['id' => $preorderId]);
        $this->updateReliabilityScores($connection);

        $oldStatus = (string) $preorder['status'];
        if ($newStatus === 'Cancelled' && $oldStatus !== 'Cancelled') {
            $message = 'Pre-order cancelled. Customer reliability score updated.';
        } elseif ($oldStatus === 'Cancelled' && $newStatus !== 'Cancelled') {
            $message = 'Pre-order status restored. Customer reliability score updated.';
        } else {
            $message = 'Pre-order status updated successfully.';
        }

        $this->addFlash('success', $message);
    }

    private function updateReleaseStatus(
        Connection $connection,
        int $preorderId,
        string $newReleaseStatus,
        string $expectedReleaseDate
    ): void {
        if (!in_array($newReleaseStatus, self::RELEASE_STATUSES, true)) {
            $this->addFlash('error', 'Invalid release status.');
            return;
        }

        $dateValue = null;
        if ($expectedReleaseDate !== '') {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $expectedReleaseDate);
            $dateErrors = \DateTimeImmutable::getLastErrors();
            $hasDateErrors = $dateErrors !== false
                && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0);

            if (!$date || $hasDateErrors || $date->format('Y-m-d') !== $expectedReleaseDate) {
                $this->addFlash('error', 'Invalid expected release date.');
                return;
            }

            $dateValue = $date->format('Y-m-d');
        }

        $exists = $connection->fetchOne('SELECT id FROM preorders WHERE id = ?', [$preorderId]);
        if (!$exists) {
            $this->addFlash('error', 'Pre-order not found.');
            return;
        }

        $connection->update('preorders', [
            'release_status' => $newReleaseStatus,
            'expected_release_date' => $dateValue,
        ], ['id' => $preorderId]);

        $this->addFlash('success', 'Release information updated successfully.');
    }

    private function recordPayment(
        Connection $connection,
        int $preorderId,
        string $paymentType,
        string $paidAmountInput
    ): void {
        if (!in_array($paymentType, ['deposit', 'balance'], true)) {
            $this->addFlash('error', 'Invalid payment type.');
            return;
        }

        $paidCents = $this->moneyToCents($paidAmountInput);
        if ($paidCents === null) {
            $this->addFlash('error', 'Enter a valid payment amount.');
            return;
        }

        $preorder = $connection->fetchAssociative(
            "SELECT
                deposit_amount,
                deposit_paid_amount,
                deposit_status,
                remaining_balance,
                balance_paid_amount,
                balance_status,
                balance_paid_at
             FROM preorders
             WHERE id = ?
             LIMIT 1",
            [$preorderId]
        );

        if (!$preorder) {
            $this->addFlash('error', 'Pre-order not found.');
            return;
        }

        if ($paymentType === 'deposit') {
            $requiredDeposit = $this->moneyToCents((string) $preorder['deposit_amount']);
            if ($requiredDeposit === null || $paidCents > $requiredDeposit) {
                $this->addFlash('error', 'Deposit payment cannot be greater than the required deposit.');
                return;
            }

            $depositStatus = $paidCents >= $requiredDeposit ? 'Paid' : 'Unpaid';
            $depositPaidAt = $depositStatus === 'Paid' ? date('Y-m-d H:i:s') : null;

            $connection->update('preorders', [
                'deposit_paid_amount' => $this->centsToMoney($paidCents),
                'deposit_status' => $depositStatus,
                'deposit_paid_at' => $depositPaidAt,
            ], ['id' => $preorderId]);

            $message = 'Deposit payment updated successfully.';
        } else {
            $remainingCents = $this->moneyToCents((string) $preorder['remaining_balance']);
            $currentPaidCents = $this->moneyToCents((string) $preorder['balance_paid_amount']);

            if ($paidCents <= 0) {
                $this->addFlash('error', 'Balance payment must be greater than zero.');
                return;
            }
            if ($remainingCents === null || $paidCents > $remainingCents) {
                $this->addFlash('error', 'Balance payment cannot be greater than the remaining balance.');
                return;
            }

            $newBalancePaid = ($currentPaidCents ?? 0) + $paidCents;
            $newRemaining = max(0, $remainingCents - $paidCents);
            $balanceStatus = $newRemaining === 0 ? 'Paid' : 'Unpaid';
            $balancePaidAt = $balanceStatus === 'Paid'
                ? date('Y-m-d H:i:s')
                : $preorder['balance_paid_at'];

            $connection->update('preorders', [
                'balance_paid_amount' => $this->centsToMoney($newBalancePaid),
                'remaining_balance' => $this->centsToMoney($newRemaining),
                'balance_status' => $balanceStatus,
                'balance_paid_at' => $balancePaidAt,
            ], ['id' => $preorderId]);

            $message = 'Balance payment updated successfully.';
        }

        $paymentState = $connection->fetchAssociative(
            'SELECT deposit_status, balance_status FROM preorders WHERE id = ?',
            [$preorderId]
        );

        if ($paymentState && $paymentState['deposit_status'] === 'Paid' && $paymentState['balance_status'] === 'Paid') {
            $connection->update('preorders', ['status' => 'Completed'], ['id' => $preorderId]);
            $this->updateReliabilityScores($connection);
            $this->addFlash('success', 'Payment completed. Pre-order automatically marked as Completed.');
            return;
        }

        $this->addFlash('success', $message);
    }

    private function updateReliabilityScores(Connection $connection): void
    {
        $customers = $connection->fetchAllAssociative(
            "SELECT
                u.id,
                COALESCE(SUM(CASE WHEN po.status = 'Completed' THEN 1 ELSE 0 END), 0) AS completed_count,
                COALESCE(SUM(CASE WHEN po.status = 'Cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_count
             FROM users u
             LEFT JOIN preorders po ON u.id = po.user_id
             WHERE u.role = 'customer'
             GROUP BY u.id"
        );

        foreach ($customers as $customer) {
            $completed = (int) $customer['completed_count'];
            $cancelled = (int) $customer['cancelled_count'];
            $score = max(0, 100 - ($cancelled * 20));

            $connection->update('users', [
                'completed_preorders' => $completed,
                'cancelled_preorders' => $cancelled,
                'reliability_score' => number_format($score, 2, '.', ''),
            ], ['id' => (int) $customer['id']]);
        }
    }

    private function moneyToCents(string $value): ?int
    {
        $value = trim($value);
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function centsToMoney(int $cents): string
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function productImageUrl(?string $image): ?string
    {
        if ($image === null || trim($image) === '') {
            return null;
        }

        $file = trim($image);
        $file = preg_replace('#^.*?assets[\\\\/]collections[\\\\/]#i', '', $file) ?? $file;
        $segments = array_map('rawurlencode', explode('/', str_replace('\\\\', '/', $file)));

        return '/assets/collections/' . implode('/', $segments);
    }
}
