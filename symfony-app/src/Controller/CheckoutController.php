<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout', methods: ['GET'])]
    public function index(
        Connection $connection,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        // Get the authenticated Symfony customer.
        $user = $this->getUser();

        if (!$user instanceof User || $user->getId() === null || (int) $user->getId() < 1) {
            return $this->redirectToRoute('app_login');
        }

        $userId = (int) $user->getId();

        $customer = $connection->fetchAssociative(
            'SELECT first_name, last_name, email FROM users WHERE id = ?',
            [$userId]
        );

        if (!$customer) {
            throw $this->createNotFoundException('Customer record not found.');
        }

        return $this->render('checkout/index.html.twig', [
            'customer' => $customer,
            'cartStorageKey' => 'mimicHavenCart_' . $userId,
            'checkoutCsrfToken' => $csrfTokenManager->getToken('checkout')->getValue(),
        ]);
    }

    #[Route('/checkout', name: 'app_checkout_submit', methods: ['POST'])]
    public function placeOrder(
        Request $request,
        Connection $connection,
        CsrfTokenManagerInterface $csrfTokenManager,
        LoggerInterface $logger
    ): JsonResponse {
        // Use the authenticated Symfony user instead of session user_id.
        $user = $this->getUser();

        if (!$user instanceof User || $user->getId() === null || (int) $user->getId() < 1) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You must be logged in to place an order.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userId = (int) $user->getId();

        $input = json_decode($request->getContent(), true);

        if (!is_array($input)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid request.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $csrfToken = $input['csrf_token'] ?? '';

        if (
            !is_string($csrfToken) ||
            !$csrfTokenManager->isTokenValid(new CsrfToken('checkout', $csrfToken))
        ) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Security validation failed.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $firstName = trim((string) ($input['firstName'] ?? ''));
            $lastName = trim((string) ($input['lastName'] ?? ''));
            $email = trim((string) ($input['email'] ?? ''));
            $phone = trim((string) ($input['phone'] ?? ''));

            if ($firstName === '' || $lastName === '' || $email === '' || $phone === '') {
                throw new \InvalidArgumentException('Please complete all customer information.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Please enter a valid email address.');
            }

            $address = trim((string) ($input['address'] ?? ''));
            $city = trim((string) ($input['city'] ?? ''));
            $province = trim((string) ($input['province'] ?? ''));
            $postalCode = trim((string) ($input['postalCode'] ?? ''));
            $country = trim((string) ($input['country'] ?? ''));

            if ($address === '' || $city === '' || $province === '' || $postalCode === '' || $country === '') {
                throw new \InvalidArgumentException('Please complete the delivery address.');
            }

            $deliveryAddress = $address . ', ' . $city . ', ' . $province . ' ' . $postalCode . ', ' . $country;

            $payment = strtolower(trim((string) ($input['payment'] ?? '')));

            $allowedPayments = [
                'gcash' => 'GCash',
                'bank' => 'Bank Transfer',
                'cod' => 'Cash on Delivery',
            ];

            if (!isset($allowedPayments[$payment])) {
                throw new \InvalidArgumentException('Invalid payment method.');
            }

            $paymentMethod = $allowedPayments[$payment];

            $notes = trim((string) ($input['notes'] ?? ''));

            if (strlen($notes) > 2000) {
                throw new \InvalidArgumentException('Order notes are too long.');
            }

            $cart = $input['cart'] ?? null;

            if (!is_array($cart) || $cart === []) {
                throw new \InvalidArgumentException('Your cart is empty.');
            }

            // Combine repeated product IDs before checking stock.
            $quantities = [];

            foreach ($cart as $item) {
                if (!is_array($item)) {
                    throw new \InvalidArgumentException('Invalid cart item.');
                }

                $productId = isset($item['id']) ? (int) $item['id'] : 0;
                $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 1;

                if ($productId < 1 || $quantity < 1 || $quantity > 20) {
                    throw new \InvalidArgumentException('Invalid cart item.');
                }

                $quantities[$productId] = ($quantities[$productId] ?? 0) + $quantity;

                if ($quantities[$productId] > 20) {
                    throw new \InvalidArgumentException('Invalid cart item.');
                }
            }

            $orderId = $connection->transactional(function (Connection $db) use (
                $userId,
                $quantities,
                $paymentMethod,
                $deliveryAddress,
                $notes
            ): int {
                $validatedItems = [];
                $totalCents = 0;

                foreach ($quantities as $productId => $quantity) {
                    $product = $db->fetchAssociative(
                        'SELECT id, name, price, availability, stock FROM products WHERE id = ? LIMIT 1 FOR UPDATE',
                        [(int) $productId]
                    );

                    if (!$product) {
                        throw new \InvalidArgumentException('One of the products in your cart no longer exists.');
                    }

                    if ($product['availability'] !== 'In Stock') {
                        throw new \InvalidArgumentException($product['name'] . ' is not currently available for normal checkout.');
                    }

                    $stock = (int) ($product['stock'] ?? 0);

                    if ($quantity > $stock) {
                        throw new \InvalidArgumentException(
                            'Not enough stock available for ' . $product['name'] . '. Only ' . $stock . ' item(s) remain.'
                        );
                    }

                    // Read the price from the database, not localStorage.
                    $price = number_format((float) $product['price'], 2, '.', '');
                    $priceCents = (int) round(((float) $price) * 100);

                    $totalCents += $priceCents * $quantity;

                    $validatedItems[] = [
                        'product_id' => (int) $product['id'],
                        'quantity' => $quantity,
                        'price' => $price,
                    ];
                }

                if ($totalCents <= 0) {
                    throw new \InvalidArgumentException('Invalid order total.');
                }

                $totalAmount = number_format($totalCents / 100, 2, '.', '');

                $db->executeStatement(
                    "INSERT INTO orders (user_id, total_amount, payment_method, status, delivery_address, order_notes)
                     VALUES (?, ?, ?, 'Pending', ?, ?)",
                    [$userId, $totalAmount, $paymentMethod, $deliveryAddress, $notes]
                );

                $newOrderId = (int) $db->lastInsertId();

                foreach ($validatedItems as $item) {
                    $db->executeStatement(
                        'INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)',
                        [$newOrderId, $item['product_id'], $item['quantity'], $item['price']]
                    );

                    $affected = $db->executeStatement(
                        'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?',
                        [$item['quantity'], $item['product_id'], $item['quantity']]
                    );

                    if ($affected !== 1) {
                        throw new \InvalidArgumentException(
                            'Unable to update product stock. Please review your cart and try again.'
                        );
                    }
                }

                return $newOrderId;
            });

            $csrfTokenManager->refreshToken('checkout');

            return new JsonResponse([
                'success' => true,
                'order_id' => $orderId,
                'message' => 'Your order has been placed successfully!',
            ]);

        } catch (\InvalidArgumentException $error) {
            return new JsonResponse([
                'success' => false,
                'message' => $error->getMessage(),
            ], Response::HTTP_BAD_REQUEST);

        } catch (\Throwable $error) {
            $logger->error('Checkout error', [
                'exception' => $error,
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Unable to place your order right now. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
