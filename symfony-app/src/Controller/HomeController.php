<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $featuredSeries = [
            [
                'name' => 'Frieren Collection',
                'series' => "Frieren: Beyond Journey's End",
                'image' => 'frieren',
            ],
            [
                'name' => 'Mao Collection',
                'series' => 'The Apothecary Diaries',
                'image' => 'mao',
            ],
            [
                'name' => 'Lena Collection',
                'series' => '86 - EIGHTY SIX',
                'image' => 'lena',
            ],
            [
                'name' => 'Violet Collection',
                'series' => 'Violet Evergarden',
                'image' => 'violet',
            ],
        ];

        $collections = [];

        foreach ($featuredSeries as $collection) {
            $stock = $entityManager->createQueryBuilder()
                ->select('COALESCE(SUM(p.stock), 0)')
                ->from(Product::class, 'p')
                ->where('p.series = :series')
                ->andWhere('p.availability = :availability')
                ->andWhere('p.stock > 0')
                ->setParameter('series', $collection['series'])
                ->setParameter('availability', 'In Stock')
                ->getQuery()
                ->getSingleScalarResult();

            $collection['items'] = (int) $stock . ' ITEMS IN STOCK';
            $collection['link'] = '/collection?series=' . urlencode($collection['series']);
            $collection['isMore'] = false;
            $collections[] = $collection;
        }

        $collections[] = [
            'name' => 'More Collections',
            'series' => 'Explore more series',
            'items' => 'SEE ALL',
            'image' => 'more-collections',
            'link' => '/collection',
            'isMore' => true,
        ];

        $reviewEntities = $entityManager->getRepository(Review::class)
            ->createQueryBuilder('r')
            ->innerJoin('r.user', 'u')
            ->where('r.status = :status')
            ->setParameter('status', 'Approved')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        $reviews = [];

        foreach ($reviewEntities as $review) {
            $user = $review->getUser();
            $fullName = trim(($user?->getFirstName() ?? '') . ' ' . ($user?->getLastName() ?? ''));
            $nameParts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
            $initials = '';

            if (!empty($nameParts[0])) {
                $initials .= strtoupper(substr($nameParts[0], 0, 1));
            }

            if (count($nameParts) > 1) {
                $initials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
            }

            $reviews[] = [
                'rating' => $review->getRating(),
                'text' => $review->getReviewText(),
                'name' => $fullName,
                'initials' => $initials,
            ];
        }

        $conditions = [
            [
                'code' => 'MISB',
                'title' => 'Mint in Sealed Box',
                'desc' => 'Brand new and factory sealed in its original packaging.',
                'state' => 'NEW',
                'image' => 'misb',
            ],
            [
                'code' => 'LOOSE',
                'title' => 'No Original Packaging',
                'desc' => 'Figure is sold without its original box or packaging.',
                'state' => 'GOOD',
                'image' => 'loose',
            ],
            [
                'code' => 'MIB',
                'title' => 'Mint in Box',
                'desc' => 'Opened, but the figure remains in excellent condition with its original box.',
                'state' => 'EXCELLENT',
                'image' => 'mib',
            ],
            [
                'code' => 'BIB',
                'title' => 'Box Opened',
                'desc' => 'Opened or displayed, but the figure remains complete and well maintained.',
                'state' => 'VERY GOOD',
                'image' => 'bib',
            ],
        ];

        $faqs = [
            [
                'q' => 'How can I become eligible as a returning customer and what requirements do I need to meet?',
                'a' => 'Returning-customer benefits are based on completed purchases and account history. Contact us for your eligibility status.',
            ],
            [
                'q' => 'I missed the pre-order deadline for an item I really want. Can I still place a pre-order for it?',
                'a' => 'Possibly. Availability depends on supplier allocations and whether extra slots remain. Contact us with the item name.',
            ],
            [
                'q' => 'I made a mistake with my order details after checking out. Can I still change my delivery address or pickup method?',
                'a' => 'Contact us as soon as possible. Changes depend on whether the order has already been processed or shipped.',
            ],
            [
                'q' => 'My pre-ordered item has not arrived even though the estimated arrival date has already passed. What should I do?',
                'a' => 'Estimated dates can move. Send us your order details and we will check the latest supplier or shipping update.',
            ],
            [
                'q' => 'What happens to my deposit if my pre-ordered item is cancelled by the supplier or manufacturer?',
                'a' => 'Deposit handling depends on the reason and terms of the cancellation. We will communicate the available refund or replacement options.',
            ],
        ];

        return $this->render('home/index.html.twig', [
            'collections' => $collections,
            'reviews' => $reviews,
            'conditions' => $conditions,
            'faqs' => $faqs,
        ]);
    }
}
