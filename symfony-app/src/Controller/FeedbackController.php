<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FeedbackController extends AbstractController
{
    #[Route('/feedback', name: 'app_feedback', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, Connection $connection, KernelInterface $kernel): Response
    {
        $user = $this->getUser();
        if (!is_object($user) || !method_exists($user, 'getId')) {
            return $this->redirectToRoute('app_login');
        }
        $userId = $user->getId();

        if ($request->isMethod('POST')) {
            $orderId = filter_var($request->request->get('order_id'), FILTER_VALIDATE_INT);
            $productId = filter_var($request->request->get('product_id'), FILTER_VALIDATE_INT);
            $rating = filter_var($request->request->get('rating'), FILTER_VALIDATE_INT);
            $reviewText = trim((string) $request->request->get('review_text', ''));

            if (!$this->isCsrfTokenValid('submit_feedback', (string) $request->request->get('_token', ''))) {
                $this->addFlash('error', 'Security validation failed. Please try again.');
            } elseif (!$orderId || !$productId) {
                $this->addFlash('error', 'Please select the order and figure you want to review.');
            } elseif (!$rating || $rating < 1 || $rating > 5) {
                $this->addFlash('error', 'Please select a rating from 1 to 5 stars.');
            } elseif ($reviewText === '') {
                $this->addFlash('error', 'Please write your feedback.');
            } elseif (strlen($reviewText) > 2000) {
                $this->addFlash('error', 'Your feedback must not exceed 2000 characters.');
            } else {
                // Confirm the order belongs to this customer, is completed, and contains the product.
                $purchaseExists = (bool) $connection->fetchOne(
                    "SELECT 1
                     FROM orders o
                     INNER JOIN order_items oi ON oi.order_id = o.id
                     WHERE o.id = ?
                       AND o.user_id = ?
                       AND o.status = 'Completed'
                       AND oi.product_id = ?
                     LIMIT 1",
                    [$orderId, $userId, $productId]
                );

                if (!$purchaseExists) {
                    $this->addFlash('error', 'You can only review figures from your completed purchases.');
                } else {
                    $alreadyReviewed = (bool) $connection->fetchOne(
                        'SELECT 1 FROM reviews WHERE user_id = ? AND order_id = ? AND product_id = ? LIMIT 1',
                        [$userId, $orderId, $productId]
                    );

                    if ($alreadyReviewed) {
                        $this->addFlash('error', 'You have already submitted feedback for this purchase.');
                    } else {
                        $connection->insert('reviews', [
                            'user_id' => $userId,
                            'order_id' => $orderId,
                            'product_id' => $productId,
                            'rating' => $rating,
                            'review_text' => $reviewText,
                            'status' => 'Pending',
                        ]);
                        $this->addFlash('success', 'Thank you! Your feedback has been submitted and is waiting for approval.');
                    }
                }
            }

            return $this->redirectToRoute('app_feedback');
        }

        $completedItems = $connection->fetchAllAssociative(
            "SELECT o.id AS order_id, o.created_at,
                    p.id AS product_id, p.name AS product_name,
                    p.series, p.image
             FROM orders o
             INNER JOIN order_items oi ON oi.order_id = o.id
             INNER JOIN products p ON p.id = oi.product_id
             WHERE o.user_id = ? AND o.status = 'Completed'
             ORDER BY o.created_at DESC, oi.id ASC",
            [$userId]
        );

        $reviews = $connection->fetchAllAssociative(
            'SELECT order_id, product_id, rating, review_text, status
             FROM reviews WHERE user_id = ?',
            [$userId]
        );
        $reviewedItems = [];
        foreach ($reviews as $review) {
            $reviewedItems[$review['order_id'] . '-' . $review['product_id']] = $review;
        }

        foreach ($completedItems as &$item) {
            $item['image_path'] = $this->resolveProductImage($item['image'] ?? null, $kernel->getProjectDir());
            $key = $item['order_id'] . '-' . $item['product_id'];
            $item['existing_review'] = $reviewedItems[$key] ?? null;
        }
        unset($item);

        return $this->render('feedback/index.html.twig', [
            'completedItems' => $completedItems,
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
            if (is_file($publicDir . '/assets/collections/' . $baseName . '.' . $extension)) {
                return '/assets/collections/' . rawurlencode($baseName) . '.' . $extension;
            }
        }

        return null;
    }
}
