<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CollectionController extends AbstractController
{
    #[Route('/collection', name: 'app_collection', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $selectedSeries = trim((string) $request->query->get('series', ''));
        $searchQuery = trim((string) $request->query->get('search', ''));

        // Keep the same availability rules as the original collection.php page.
        $products = $entityManager->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->where('p.availability = :preorder OR (p.availability = :inStock AND p.stock > 0)')
            ->setParameter('preorder', 'Pre-Order')
            ->setParameter('inStock', 'In Stock')
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();

        $collectionProducts = [];

        foreach ($products as $product) {
            if (!$product instanceof Product) {
                continue;
            }

            $image = $product->getImage();

            $collectionProducts[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'series' => $product->getSeries(),
                'category' => $product->getCategory(),
                'price' => $product->getPrice(),
                'priceDisplay' => number_format((float) $product->getPrice(), 0, '.', ','),
                'condition' => $product->getConditionStatus(),
                'availability' => $product->getAvailability(),
                'imagePath' => $image !== null && $image !== ''
                    ? 'assets/collections/' . rawurlencode($image)
                    : 'assets/collections/images.jpg',
                'stock' => max(0, (int) ($product->getStock() ?? 0)),
            ];
        }

        return $this->render('collection/index.html.twig', [
            'products' => $collectionProducts,
            'selectedSeries' => $selectedSeries,
            'searchQuery' => $searchQuery,
            'categories' => [
                'Action Figures',
                'Scale Figures',
                'Nendoroid',
                'Prize Figures',
                'Statues',
                'Others',
            ],
            'franchises' => [
                "Frieren: Beyond Journey's End",
                'The Apothecary Diaries',
                '86 - EIGHTY SIX',
                'Violet Evergarden',
                'Jujutsu Kaisen',
                'Demon Slayer',
                'One Piece',
                'Chainsaw Man',
                'Overlord',
            ],
            'conditions' => ['MISB', 'MIB', 'BIB', 'LOOSE'],
        ]);
    }
}
