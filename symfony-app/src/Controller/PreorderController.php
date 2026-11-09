<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class PreorderController extends AbstractController
{
    #[Route('/preorder', name: 'app_preorder', methods: ['GET', 'POST'])]
    public function index(Request $request, Connection $connection): Response
    {
        if ($request->isMethod('POST')) {
            $user = $this->getUser();

            if (!($user instanceof User)) {
                $this->addFlash('error', 'Please log in to place a pre-order.');

                return $this->redirectToRoute('app_login');
            }

            if (!$this->isCsrfTokenValid('preorder_reserve', (string) $request->request->get('_token', ''))) {
                $this->addFlash('error', 'Security validation failed. Please try again.');

                return $this->redirectToRoute('app_preorder', ['_fragment' => 'preorder-products']);
            }

            $productId = filter_var($request->request->get('product_id'), FILTER_VALIDATE_INT);
            $quantity = filter_var($request->request->get('quantity'), FILTER_VALIDATE_INT);

            if (!$productId || $productId < 1) {
                $this->addFlash('error', 'Invalid pre-order product.');

                return $this->redirectToRoute('app_preorder', ['_fragment' => 'preorder-products']);
            }

            if (!$quantity || $quantity < 1 || $quantity > 10) {
                $this->addFlash('error', 'Quantity must be between 1 and 10.');

                return $this->redirectToRoute('app_preorder', ['_fragment' => 'preorder-products']);
            }

            try {
                $connection->beginTransaction();

                // Lock the product row while checking and creating the reservation.
                $product = $connection->fetchAssociative(
                    "SELECT id, price
                     FROM products
                     WHERE id = :productId AND availability = 'Pre-Order'
                     FOR UPDATE",
                    ['productId' => $productId]
                );

                if (!$product) {
                    $connection->rollBack();
                    $this->addFlash('error', 'This figure is not currently available for pre-order.');

                    return $this->redirectToRoute('app_preorder', ['_fragment' => 'preorder-products']);
                }

                $existingPreorder = $connection->fetchOne(
                    "SELECT id
                     FROM preorders
                     WHERE user_id = :userId
                       AND product_id = :productId
                       AND status NOT IN ('Completed', 'Cancelled')
                     LIMIT 1",
                    [
                        'userId' => $user->getId(),
                        'productId' => $productId,
                    ]
                );

                if ($existingPreorder !== false) {
                    $connection->rollBack();
                    $this->addFlash('error', 'You already have an active pre-order for this figure.');

                    return $this->redirectToRoute('app_preorder', ['_fragment' => 'preorder-products']);
                }

                $unitPrice = (float) $product['price'];
                $depositPerUnit = max(50.00, round(($unitPrice * 0.30) / 50) * 50);
                $totalAmount = $unitPrice * $quantity;
                $depositAmount = $depositPerUnit * $quantity;
                $remainingBalance = $totalAmount - $depositAmount;

                if ($depositAmount <= 0 || round($remainingBalance, 2) < 0) {
                    $connection->rollBack();
                    $this->addFlash('error', 'Invalid pre-order payment values. Please contact us for assistance.');

                    return $this->redirectToRoute('app_preorder', ['_fragment' => 'preorder-products']);
                }

                $connection->executeStatement(
                    "INSERT INTO preorders
                        (user_id, product_id, quantity, unit_price, total_amount,
                         deposit_amount, remaining_balance, expected_release_date,
                         status, release_status, notes)
                     VALUES
                        (:userId, :productId, :quantity, :unitPrice, :totalAmount,
                         :depositAmount, :remainingBalance, NULL,
                         'Pending', 'Waiting for Manufacturer', NULL)",
                    [
                        'userId' => $user->getId(),
                        'productId' => $productId,
                        'quantity' => $quantity,
                        'unitPrice' => number_format($unitPrice, 2, '.', ''),
                        'totalAmount' => number_format($totalAmount, 2, '.', ''),
                        'depositAmount' => number_format($depositAmount, 2, '.', ''),
                        'remainingBalance' => number_format($remainingBalance, 2, '.', ''),
                    ]
                );

                $connection->commit();
                $this->addFlash('success', 'Your pre-order has been reserved successfully!');
            } catch (Throwable $exception) {
                if ($connection->isTransactionActive()) {
                    $connection->rollBack();
                }

                $this->addFlash('error', 'Unable to save your pre-order. Please try again.');
            }

            return $this->redirectToRoute('app_preorder', ['_fragment' => 'preorder-products']);
        }

        $rows = $connection->fetchAllAssociative(
            "SELECT id, name, series, category, price, image
             FROM products
             WHERE availability = 'Pre-Order'
             ORDER BY id ASC"
        );

        $products = [];

        foreach ($rows as $row) {
            $price = (float) $row['price'];
            $deposit = max(50.00, round(($price * 0.30) / 50) * 50);

            $products[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'series' => $row['series'],
                'category' => $row['category'],
                'price' => $price,
                'deposit' => $deposit,
                'release' => 'Release Date TBA',
                'image' => $row['image'],
            ];
        }

        return $this->render('preorder/index.html.twig', [
            'products' => $products,
        ]);
    }
}
