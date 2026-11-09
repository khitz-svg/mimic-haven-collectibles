<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminProductsController extends AbstractController
{
    private const CATEGORIES = [
        'Prize Figures',
        'Scale Figures',
        'Action Figures',
        'Statues',
        'Others',
    ];

    private const AVAILABILITIES = [
        'In Stock',
        'Pre-Order',
    ];

    private const CONDITIONS = [
        'MISB',
        'MIB',
        'New',
        'Like New',
        'Used',
    ];

    #[Route('/admin/products', name: 'app_admin_products', methods: ['GET', 'POST'])]
    public function index(Request $request, Connection $connection): Response
    {
        if ($request->isMethod('POST')) {
            $token = $request->request->getString('_token');

            if (!$this->isCsrfTokenValid('admin_products', $token)) {
                $this->addFlash('error', 'Invalid security token. Please try again.');
                return $this->redirectToRoute('app_admin_products');
            }

            $action = $request->request->getString('action');

            if ($action === 'delete') {
                $this->deleteProduct($request, $connection);
            } elseif ($action === 'add' || $action === 'update') {
                $data = $this->readAndValidateProduct($request);

                if (isset($data['error'])) {
                    $this->addFlash('error', $data['error']);
                } elseif ($action === 'add') {
                    $connection->insert('products', $data);
                    $this->addFlash('success', 'Product added successfully.');
                } else {
                    $productId = $request->request->getInt('product_id');

                    if ($productId < 1 || !$connection->fetchOne('SELECT id FROM products WHERE id = ?', [$productId])) {
                        $this->addFlash('error', 'Invalid product.');
                    } else {
                        $connection->update('products', $data, ['id' => $productId]);
                        $this->addFlash('success', 'Product updated successfully.');
                    }
                }
            } else {
                $this->addFlash('error', 'Invalid request.');
            }

            return $this->redirectToRoute('app_admin_products');
        }

        $products = $connection->fetchAllAssociative(
            'SELECT id, name, series, category, availability, condition_status,
                    price, stock, image, description, created_at
             FROM products
             ORDER BY id DESC'
        );

        foreach ($products as &$product) {
            $product['image_url'] = $this->productImageUrl($product['image'] ?? null);
        }
        unset($product);

        return $this->render('admin/products.html.twig', [
            'products' => $products,
            'categories' => self::CATEGORIES,
            'availabilities' => self::AVAILABILITIES,
            'conditions' => self::CONDITIONS,
        ]);
    }

    private function readAndValidateProduct(Request $request): array
    {
        $name = trim($request->request->getString('name'));
        $series = trim($request->request->getString('series'));
        $category = $request->request->getString('category');
        $availability = $request->request->getString('availability');
        $condition = $request->request->getString('condition_status');
        $priceInput = trim($request->request->getString('price'));
        $stockInput = trim($request->request->getString('stock'));
        $image = trim($request->request->getString('image'));
        $description = trim($request->request->getString('description'));

        if ($name === '') {
            return ['error' => 'Product name is required.'];
        }
        if (mb_strlen($name) > 150) {
            return ['error' => 'Product name must not exceed 150 characters.'];
        }
        if ($series === '') {
            return ['error' => 'Series is required.'];
        }
        if (mb_strlen($series) > 100) {
            return ['error' => 'Series must not exceed 100 characters.'];
        }
        if (!in_array($category, self::CATEGORIES, true)) {
            return ['error' => 'Invalid category.'];
        }
        if (!in_array($availability, self::AVAILABILITIES, true)) {
            return ['error' => 'Invalid availability.'];
        }
        if (!in_array($condition, self::CONDITIONS, true)) {
            return ['error' => 'Invalid condition.'];
        }
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $priceInput)) {
            return ['error' => 'Enter a valid product price.'];
        }
        if (!preg_match('/^\d+$/', $stockInput)) {
            return ['error' => 'Stock must be a whole number of zero or more.'];
        }
        if (mb_strlen($image) > 255) {
            return ['error' => 'Image path must not exceed 255 characters.'];
        }
        if (mb_strlen($description) > 5000) {
            return ['error' => 'Description must not exceed 5000 characters.'];
        }

        return [
            'name' => $name,
            'series' => $series,
            'category' => $category,
            'availability' => $availability,
            'condition_status' => $condition,
            'price' => number_format((float) $priceInput, 2, '.', ''),
            'stock' => (int) $stockInput,
            'image' => $image !== '' ? $image : null,
            'description' => $description !== '' ? $description : null,
        ];
    }

    private function deleteProduct(Request $request, Connection $connection): void
    {
        $productId = $request->request->getInt('product_id');

        if ($productId < 1 || !$connection->fetchOne('SELECT id FROM products WHERE id = ?', [$productId])) {
            $this->addFlash('error', 'Invalid product.');
            return;
        }

        $references = (int) $connection->fetchOne(
            'SELECT
                (SELECT COUNT(*) FROM order_items WHERE product_id = ?) +
                (SELECT COUNT(*) FROM preorders WHERE product_id = ?)',
            [$productId, $productId]
        );

        if ($references > 0) {
            $this->addFlash(
                'error',
                'This product cannot be deleted because it is already used in an order or pre-order. You can edit it instead.'
            );
            return;
        }

        $connection->delete('products', ['id' => $productId]);
        $this->addFlash('success', 'Product deleted successfully.');
    }

    private function productImageUrl(?string $image): ?string
    {
        if ($image === null || trim($image) === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', trim($image));
        $filename = basename($normalized);

        if ($filename === '' || $filename === '.' || $filename === '/') {
            return null;
        }

        return '/assets/collections/' . rawurlencode($filename);
    }
}
