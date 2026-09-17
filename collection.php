<?php

require_once 'auth.php';
require_once 'db.php';


/* =========================================================
   SELECTED SERIES FROM HOMEPAGE COLLECTION CARD
========================================================= */

$selectedSeries = isset($_GET['series'])
    ? trim((string)$_GET['series'])
    : '';


/* =========================================================
   SEARCH FROM HEADER
========================================================= */

$headerSearchQuery = isset($_GET['search'])
    ? trim((string)$_GET['search'])
    : '';


/* =========================================================
   MIMIC HAVEN - COLLECTION PAGE
========================================================= */

function asset(string $name): string
{
    foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {

        $assetFile = __DIR__ . "/assets/{$name}.{$ext}";

        if (file_exists($assetFile)) {
            return "assets/{$name}.{$ext}";
        }


        $rootFile = __DIR__ . "/{$name}.{$ext}";

        if (file_exists($rootFile)) {
            return "{$name}.{$ext}";
        }
    }

    return "";
}


function icon(string $name): string
{
    foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {

        $file = __DIR__ . "/assets/icons/{$name}.{$ext}";

        if (file_exists($file)) {
            return "assets/icons/{$name}.{$ext}";
        }
    }

    return "";
}


function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function peso(int $price): string
{
    return '₱' . number_format($price);
}


/* =========================================================
   FALLBACK COLLECTION PRODUCTS
   Used only if MySQL returns no products
========================================================= */

$products = [

    // FRIEREN

    [
        'name' => 'Frieren – AMP Figure',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Prize Figures',
        'price' => 1450,
        'condition_status' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'frieren amp.webp'
    ],

    [
        'name' => 'Frieren – ESPRESTO Figure',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Prize Figures',
        'price' => 1350,
        'condition_status' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'frieren espresto.webp'
    ],

    [
        'name' => 'Frieren – Maximatic Ver. 2',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Prize Figures',
        'price' => 1850,
        'condition_status' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'frieren maximatic v2.webp'
    ],

    [
        'name' => 'Frieren – Sofvimates',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Prize Figures',
        'price' => 950,
        'condition_status' => 'MIB',
        'availability' => 'In Stock',
        'image' => 'frieren sofvimates.webp'
    ],

    [
        'name' => 'Frieren – Ichibansho Art Scale Bust',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Statues',
        'price' => 4200,
        'condition_status' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Ichibansho Frieren Art Scale Bust.webp'
    ],

    [
        'name' => 'Frieren – Funko Pop! Deluxe #2358',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Others',
        'price' => 1250,
        'condition_status' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Pop! Deluxe #2358 Frieren.webp'
    ],

    [
        'name' => 'Fern – Yumemirize Nap Ver.',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Prize Figures',
        'price' => 1450,
        'condition_status' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Yumemirize Fern (Nap Ver.) Figure.webp'
    ],

    [
        'name' => 'Himmel – ESPRESTO Figure',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Prize Figures',
        'price' => 1550,
        'condition_status' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'himmel espresto.webp'
    ],


    // THE APOTHECARY DIARIES

    [
        'name' => 'Maomao – Break Time Collection Vol. 1',
        'series' => 'The Apothecary Diaries',
        'category' => 'Prize Figures',
        'price' => 1450,
        'condition_status' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Break Time Collection Vol.1 Maomao.webp'
    ],

    [
        'name' => 'Maomao – Moon Fairy Figure',
        'series' => 'The Apothecary Diaries',
        'category' => 'Scale Figures',
        'price' => 2800,
        'condition_status' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Maomao (Moon Fairy) Figure.webp'
    ],

    [
        'name' => 'Maomao – Oshi Works Mini Garden Party Ver.',
        'series' => 'The Apothecary Diaries',
        'category' => 'Prize Figures',
        'price' => 1600,
        'condition_status' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Oshi Works Mini Maomao (Garden Party Ver.).webp'
    ],

    [
        'name' => 'Maomao – Oshi Works Mini Figure',
        'series' => 'The Apothecary Diaries',
        'category' => 'Prize Figures',
        'price' => 1300,
        'condition_status' => 'MIB',
        'availability' => 'In Stock',
        'image' => 'Oshi Works Mini Maomao Figure.webp'
    ],


    // JUJUTSU KAISEN

    [
        'name' => 'Satoru Gojo – 1/8 Scale Figure',
        'series' => 'Jujutsu Kaisen',
        'category' => 'Scale Figures',
        'price' => 8500,
        'condition_status' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Gojo Satoru 1over8 Scale Figure.webp'
    ],

    [
        'name' => 'Choso – S.H.Figuarts',
        'series' => 'Jujutsu Kaisen',
        'category' => 'Action Figures',
        'price' => 3200,
        'condition_status' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'S.H.Figuarts Choso Action Figure.webp'
    ],

    [
        'name' => 'Kento Nanami – S.H.Figuarts',
        'series' => 'Jujutsu Kaisen',
        'category' => 'Action Figures',
        'price' => 3200,
        'condition_status' => 'MIB',
        'availability' => 'In Stock',
        'image' => 'S.H.Figuarts Kento Nanami Action Figure.webp'
    ],

    [
        'name' => 'Mahito – S.H.Figuarts',
        'series' => 'Jujutsu Kaisen',
        'category' => 'Action Figures',
        'price' => 3100,
        'condition_status' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'S.H.Figuarts Mahito Action Figure.webp'
    ],

    [
        'name' => 'Toji Fushiguro – S.H.Figuarts',
        'series' => 'Jujutsu Kaisen',
        'category' => 'Action Figures',
        'price' => 3400,
        'condition_status' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'S.H.Figuarts Toji Fushiguro Action Figure.webp'
    ],


    // DEMON SLAYER

    [
        'name' => 'Akaza II – Grandista',
        'series' => 'Demon Slayer',
        'category' => 'Prize Figures',
        'price' => 1900,
        'condition_status' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Grandista Akaza II Figure.webp'
    ],

    [
        'name' => 'Doma II – Grandista',
        'series' => 'Demon Slayer',
        'category' => 'Prize Figures',
        'price' => 1900,
        'condition_status' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Grandista Doma II Figure.webp'
    ],

    [
        'name' => 'Giyu Tomioka – Grandista',
        'series' => 'Demon Slayer',
        'category' => 'Prize Figures',
        'price' => 2200,
        'condition_status' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Grandista Giyu Tomioka.jpg'
    ],

    [
        'name' => 'Zenitsu Agatsuma – Grandista Another Color Ver. A',
        'series' => 'Demon Slayer',
        'category' => 'Prize Figures',
        'price' => 1800,
        'condition_status' => 'MIB',
        'availability' => 'In Stock',
        'image' => 'Grandista Zenitsu Agatsuma (Another Color Ver. A).jpg'
    ],

    [
        'name' => 'Zenitsu Agatsuma – Grandista Another Color Ver. B',
        'series' => 'Demon Slayer',
        'category' => 'Prize Figures',
        'price' => 1800,
        'condition_status' => 'MIB',
        'availability' => 'Pre-Order',
        'image' => 'Grandista Zenitsu Agatsuma (Another Color Ver. B).jpg'
    ],

    [
        'name' => 'Zenitsu – Deluxe 1/4 Scale Limited Edition Statue',
        'series' => 'Demon Slayer',
        'category' => 'Statues',
        'price' => 12500,
        'condition_status' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Zenitsu Deluxe 1over4 Scale Limited Edition Statue.jpg'
    ],


    // ONE PIECE

    [
        'name' => 'Monkey D. Luffy – Grandista Gear 5 II',
        'series' => 'One Piece',
        'category' => 'Prize Figures',
        'price' => 2200,
        'condition_status' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Grandista Monkey D. Luffy (Gear 5) II Figure.webp'
    ],

    [
        'name' => 'Monkey D. Luffy – Grandista Gear 5 Special Edition',
        'series' => 'One Piece',
        'category' => 'Prize Figures',
        'price' => 2400,
        'condition_status' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Grandista Monkey D. Luffy (Gear5 Special Edition) Figure.webp'
    ],

    [
        'name' => 'Monkey D. Luffy – Grandista Special Edition',
        'series' => 'One Piece',
        'category' => 'Prize Figures',
        'price' => 2100,
        'condition_status' => 'MIB',
        'availability' => 'In Stock',
        'image' => 'Grandista Monkey D. Luffy (Special Edition).webp'
    ],

    [
        'name' => 'Monkey D. Luffy – Gear 5 II Special Edition',
        'series' => 'One Piece',
        'category' => 'Prize Figures',
        'price' => 2500,
        'condition_status' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Grandista Monkey D. Luffy Gear 5 II (Special Edition) Figure.jpg'
    ],

    [
        'name' => 'Monkey D. Luffy – Gear 5 Ver. III',
        'series' => 'One Piece',
        'category' => 'Prize Figures',
        'price' => 2300,
        'condition_status' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'One Piece Grandista Monkey D. Luffy (Gear 5 Ver. III) Figure.webp'
    ],

    [
        'name' => 'Nami – Grandista',
        'series' => 'One Piece',
        'category' => 'Prize Figures',
        'price' => 1800,
        'condition_status' => 'MIB',
        'availability' => 'In Stock',
        'image' => 'Grandista nero Nami.webp'
    ],


    // CHAINSAW MAN

    [
        'name' => 'Makima – FNEX 1/7 Scale Figure',
        'series' => 'Chainsaw Man',
        'category' => 'Scale Figures',
        'price' => 11500,
        'condition_status' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Chainsaw Man FNex Makima 1over7 Scale Figure.webp'
    ],

    [
        'name' => 'Makima – Funko Pop! Animation #1679',
        'series' => 'Chainsaw Man',
        'category' => 'Others',
        'price' => 950,
        'condition_status' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Pop! Animation #1679 Makima.jpg'
    ],


    // OVERLORD

    [
        'name' => 'Albedo – Loungewear Pearl White Ver. Noodle Stopper',
        'series' => 'Overlord',
        'category' => 'Prize Figures',
        'price' => 1500,
        'condition_status' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Overlord Albedo (Loungewear Pearl White Ver.) Noodle Stopper Figure.webp'
    ],

    [
        'name' => 'Albedo – 1/7 Scale Figure',
        'series' => 'Overlord',
        'category' => 'Scale Figures',
        'price' => 9500,
        'condition_status' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Overlord Albedo 1over7 Scale Figure.webp'
    ],

    [
        'name' => 'Albedo – figma No. 693',
        'series' => 'Overlord',
        'category' => 'Action Figures',
        'price' => 5200,
        'condition_status' => 'MIB',
        'availability' => 'In Stock',
        'image' => 'Overlord figma No.693 Albedo Action Figure.webp'
    ],
];


/* =========================================================
   LOAD PRODUCTS FROM MYSQL
========================================================= */

$dbProducts = [];

$result = $conn->query(
    "SELECT
        id,
        name,
        series,
        category,
        price,
        condition_status,
        availability,
        image,
        stock
     FROM products
     WHERE
        availability = 'Pre-Order'
        OR (
            availability = 'In Stock'
            AND stock > 0
        )
     ORDER BY id ASC"
);


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $dbProducts[] = $row;

    }

}


/* =========================================================
   USE MYSQL PRODUCTS
========================================================= */

if (!empty($dbProducts)) {

    $products = $dbProducts;

}

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Collection | Mimic Haven Collectibles
    </title>

    <meta
        name="description"
        content="Explore the Mimic Haven anime figure collection."
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="style.css"
    >

    <style>

        /* =====================================================
           HEADER SEARCH
        ===================================================== */

        .header-search {
            position: relative;
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }


        .header-search-toggle {
            width: 40px;
            height: 40px;

            padding: 0;

            border: 0;
            border-radius: 50%;

            background: transparent;

            display: flex;
            align-items: center;
            justify-content: center;

            cursor: pointer;

            flex-shrink: 0;
        }


        .header-search-toggle img {
            width: 18px;
            height: 18px;
            object-fit: contain;
            display: block;
        }


        .header-search-input {
            width: 0;
            height: 38px;

            padding: 0;

            opacity: 0;

            border: 0;
            border-radius: 6px;

            outline: none;

            background: #101318;
            color: #ffffff;

            font-family: Inter, sans-serif;
            font-size: 12px;

            box-sizing: border-box;

            transition:
                width 0.3s ease,
                opacity 0.2s ease,
                padding 0.3s ease;
        }


        .header-search-input::placeholder {
            color: #777f89;
        }


        .header-search.active .header-search-input {
            width: 180px;

            opacity: 1;

            padding: 0 12px;

            border: 1px solid rgba(255,255,255,0.10);
        }


        .header-search-input:focus {
            border-color: rgba(63,169,245,0.45);
            box-shadow: 0 0 0 1px rgba(63,169,245,0.10);
        }

    </style>

</head>

<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header
    class="site-header"
    id="home"
>

    <div class="header-inner">


        <!-- LOGO -->

        <a
            class="brand"
            href="index.php"
            aria-label="Mimic Haven Collectibles"
        >

            <?php if (asset('logo')): ?>

                <img
                    class="brand-logo"
                    src="<?= asset('logo') ?>"
                    alt="Mimic Haven Collectibles"
                >

            <?php else: ?>

                <div class="brand-fallback">

                    MIMIC HAVEN

                    <span>
                        COLLECTIBLES
                    </span>

                </div>

            <?php endif; ?>

        </a>


        <!-- NAVIGATION -->

        <nav
            class="nav"
            id="main-nav"
            aria-label="Primary navigation"
        >


            <a href="index.php">

                Home

            </a>


            <a
                class="active"
                href="collection.php"
            >

                Collection

            </a>


            <a href="preorder.php">

                Pre-Orders

            </a>


            <a href="about.php">

                About

            </a>


            <a href="contact.php">

                Contact

            </a>


            <!-- =================================================
                 EXPANDABLE SEARCH
            ================================================== -->

            <div
                class="header-search"
                id="headerSearch"
            >

                <button
                    type="button"
                    class="header-search-toggle"
                    id="headerSearchToggle"
                    aria-label="Search"
                    aria-expanded="false"
                >

                    <?php if (icon('search')): ?>

                        <img
                            src="<?= icon('search') ?>"
                            alt="Search"
                        >

                    <?php else: ?>

                        🔍

                    <?php endif; ?>

                </button>


                <input
                    type="search"
                    id="headerSearchInput"
                    class="header-search-input"
                    placeholder="Search figures..."
                    autocomplete="off"
                    value="<?= e($headerSearchQuery) ?>"
                >

            </div>


            <!-- CART -->

            <a
                class="nav-icon cart-button"
                href="cart.php"
                aria-label="Cart"
            >

                <?php if (icon('pre-order')): ?>

                    <img
                        src="<?= icon('pre-order') ?>"
                        alt="Cart"
                    >

                <?php endif; ?>


                <span id="cart-count">

                    0

                </span>

            </a>


            <!-- ACCOUNT -->

            <?php if (isLoggedIn()): ?>

                <a
                    class="nav-user"
                    href="account.php"
                >

                    Hi, <?= e(currentFirstName()) ?>

                </a>


                <a
                    class="nav-account"
                    href="logout.php"
                >

                    Logout

                </a>

            <?php else: ?>

                <a
                    class="nav-account"
                    href="login.php"
                >

                    Login

                </a>

            <?php endif; ?>

        </nav>


        <!-- MOBILE MENU -->

        <button
            class="menu-toggle"
            type="button"
            aria-expanded="false"
            aria-controls="main-nav"
        >
            ☰
        </button>

    </div>

</header>



<main id="collection">


<!-- =========================================================
     COLLECTION HERO
========================================================= -->

<section class="collection-page-hero">

    <div class="collection-page-hero-inner">

        <div class="collection-page-copy">

            <span class="collection-eyebrow">

                MIMIC HAVEN COLLECTIBLES

            </span>


            <h1>

                Our

                <span>
                    Collection
                </span>

            </h1>


            <p>

                Discover authentic anime figures, collector pieces,
                and carefully selected treasures for every collection.

            </p>

        </div>

    </div>

</section>



<!-- =========================================================
     COLLECTION CONTENT
========================================================= -->

<section class="collection-catalog">

    <div class="collection-catalog-inner">


        <!-- =====================================================
             FILTER SIDEBAR
        ====================================================== -->

        <aside class="collection-sidebar">


            <div class="filter-heading">

                <h2>
                    Filter Collection
                </h2>

            </div>


            <!-- CATEGORY -->

            <div class="filter-group">

                <h3>
                    Category
                </h3>


                <?php

                $categories = [

                    'Action Figures',
                    'Scale Figures',
                    'Nendoroid',
                    'Prize Figures',
                    'Statues',
                    'Others'

                ];

                ?>


                <?php foreach ($categories as $category): ?>

                    <label class="filter-option">

                        <input
                            type="checkbox"
                            class="category-filter"
                            value="<?= e($category) ?>"
                        >

                        <span>
                            <?= e($category) ?>
                        </span>

                    </label>

                <?php endforeach; ?>

            </div>


            <!-- FRANCHISE -->

            <div class="filter-group">

                <h3>
                    Franchise
                </h3>


                <?php

                $franchises = [

                    "Frieren: Beyond Journey's End",
                    "The Apothecary Diaries",
                    "86 - EIGHTY SIX",
                    "Violet Evergarden",
                    "Jujutsu Kaisen",
                    "Demon Slayer",
                    "One Piece",
                    "Chainsaw Man",
                    "Overlord"

                ];

                ?>


                <?php foreach ($franchises as $franchise): ?>

                    <label class="filter-option">

                        <input
                            type="checkbox"
                            class="franchise-filter"
                            value="<?= e($franchise) ?>"
                            <?= $selectedSeries === $franchise ? 'checked' : '' ?>
                        >

                        <span>
                            <?= e($franchise) ?>
                        </span>

                    </label>

                <?php endforeach; ?>

            </div>


            <!-- CONDITION -->

            <div class="filter-group">

                <h3>
                    Condition
                </h3>


                <?php foreach (
                    ['MISB','MIB','BIB','LOOSE']
                    as $condition
                ): ?>

                    <label class="filter-option">

                        <input
                            type="checkbox"
                            class="condition-filter"
                            value="<?= e($condition) ?>"
                        >

                        <span>
                            <?= e($condition) ?>
                        </span>

                    </label>

                <?php endforeach; ?>

            </div>


            <!-- AVAILABILITY -->

            <div class="filter-group">

                <h3>
                    Availability
                </h3>


                <label class="filter-option">

                    <input
                        type="checkbox"
                        class="availability-filter"
                        value="In Stock"
                    >

                    <span>
                        In Stock
                    </span>

                </label>


                <label class="filter-option">

                    <input
                        type="checkbox"
                        class="availability-filter"
                        value="Pre-Order"
                    >

                    <span>
                        Pre-Order
                    </span>

                </label>

            </div>


            <button
                type="button"
                class="clear-filters"
                id="clearFilters"
            >
                Clear All Filters
            </button>


        </aside>



        <!-- =====================================================
             PRODUCTS
        ====================================================== -->

        <div class="collection-products">


            <!-- TOOLBAR -->

            <div class="collection-toolbar">


                <div class="collection-results-count">

                    Showing

                    <strong id="collectionResultStart">
                        1
                    </strong>

                    –

                    <strong id="collectionResultEnd">
                        <?= min(
                            12,
                            count($products)
                        ) ?>
                    </strong>

                    of

                    <strong id="collectionResultTotal">
                        <?= count($products) ?>
                    </strong>

                    results

                </div>


                <div class="collection-toolbar-actions">


                    <select
                        id="collectionSort"
                        class="collection-sort"
                    >

                        <option value="newest">
                            Sort by Newest
                        </option>

                        <option value="name">
                            Name A–Z
                        </option>

                        <option value="price-low">
                            Price: Low to High
                        </option>

                        <option value="price-high">
                            Price: High to Low
                        </option>

                    </select>


                    <button
                        type="button"
                        class="collection-view-button active"
                        id="collectionGridView"
                        aria-label="Grid view"
                    >
                        ▦
                    </button>


                    <button
                        type="button"
                        class="collection-view-button"
                        id="collectionListView"
                        aria-label="List view"
                    >
                        ☰
                    </button>


                </div>

            </div>



            <!-- COLLECTION SEARCH -->

            <div
                class="collection-search"
                id="collection-search"
            >

                <input
                    type="search"
                    id="collectionSearchInput"
                    placeholder="Search figures, characters, or anime..."
                    autocomplete="off"
                    value="<?= e($headerSearchQuery) ?>"
                >

            </div>



            <!-- PRODUCT GRID -->

            <div
                class="collection-product-grid"
                id="collectionProductGrid"
            >


                <?php foreach (
                    $products
                    as $index => $product
                ): ?>


                    <article

                        class="collection-product-card"

                        data-index="<?= $index ?>"

                        data-product-id="<?=
                            isset($product['id'])
                            ? (int)$product['id']
                            : ($index + 1)
                        ?>"

                        data-name="<?=
                            e(
                                strtolower(
                                    $product['name']
                                )
                            )
                        ?>"

                        data-series="<?=
                            e($product['series'])
                        ?>"

                        data-category="<?=
                            e($product['category'])
                        ?>"

                        data-price="<?=
                            (float)$product['price']
                        ?>"

                        data-condition="<?=
                            e(
                                $product['condition_status']
                            )
                        ?>"

                        data-availability="<?=
                            e(
                                $product['availability']
                            )
                        ?>"

                        data-stock="<?=
                            isset($product['stock'])
                            ? max(
                                0,
                                (int)$product['stock']
                            )
                            : 0
                        ?>"
                    >


                        <!-- IMAGE -->

                        <div
                            class="collection-product-image"
                        >

                            <img
                                src="assets/collections/<?= rawurlencode($product['image']) ?>"
                                alt="<?= e($product['name']) ?>"
                                loading="lazy"
                            >


                            <span
                                class="collection-condition"
                            >

                                <?= e(
                                    $product[
                                        'condition_status'
                                    ]
                                ) ?>

                            </span>


                            <button
                                type="button"
                                class="collection-favorite"
                                aria-label="Add to favorites"
                            >
                                ♡
                            </button>


                        </div>



                        <!-- PRODUCT INFO -->

                        <div
                            class="collection-product-info"
                        >


                            <div
                                class="collection-product-series"
                            >

                                <?= e(
                                    $product['series']
                                ) ?>

                            </div>


                            <h3
                                class="collection-product-name"
                            >

                                <?= e(
                                    $product['name']
                                ) ?>

                            </h3>


                            <div
                                class="collection-product-bottom"
                            >


                                <strong
                                    class="collection-product-price"
                                >

                                    <?= peso(
                                        (int)$product['price']
                                    ) ?>

                                </strong>


                                <span
                                    class="collection-product-status <?= $product['availability'] === 'Pre-Order' ? 'preorder' : '' ?>"
                                >

                                    <?= e(
                                        $product['availability']
                                    ) ?>

                                </span>


                            </div>


                        </div>


                    </article>


                <?php endforeach; ?>


            </div>



            <!-- EMPTY STATE -->

            <div
                class="collection-no-results"
                id="collectionNoResults"
            >

                <h2>
                    No Figures Found
                </h2>

                <p>
                    Try changing your search or filters.
                </p>

            </div>



            <!-- PAGINATION -->

            <div
                class="collection-pagination"
                id="collectionPagination"
            ></div>


        </div>

    </div>

</section>


</main>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer id="contact">


    <div class="footer-grid">


        <!-- BRAND -->

        <div>


            <?php if (asset('logo')): ?>

                <img
                    class="footer-logo"
                    src="<?= asset('logo') ?>"
                    alt="Mimic Haven Collectibles"
                >

            <?php endif; ?>


            <p>

                A sanctuary for anime collectors, offering authentic
                figures, trusted pre-orders, and a premium collecting
                experience inspired by the journey behind every masterpiece.

            </p>


        </div>



        <!-- NAVIGATION -->

        <div class="footer-links">


            <h4>
                NAVIGATE
            </h4>


            <a href="index.php">

                Home

            </a>


            <a href="collection.php">

                Collection

            </a>


            <a href="preorder.php">

                Pre-Orders

            </a>


            <a href="about.php">

                About

            </a>


            <a href="contact.php">

                Contact

            </a>


        </div>



        <!-- CONNECT -->

        <div class="footer-links">


            <h4>
                CONNECT
            </h4>


            <a href="mailto:mimichvn.collectibles@gmail.com">

                mimichvn.collectibles@gmail.com

            </a>


            <a href="tel:+639657457775">

                +63 965 745 7775

            </a>


            <div class="socials">


                <a
                    href="#"
                    aria-label="Facebook"
                >

                    <?php if (icon('facebook')): ?>

                        <img
                            src="<?= icon('facebook') ?>"
                            alt="Facebook"
                        >

                    <?php endif; ?>

                </a>


                <a
                    href="#"
                    aria-label="Instagram"
                >

                    <?php if (icon('instagram')): ?>

                        <img
                            src="<?= icon('instagram') ?>"
                            alt="Instagram"
                        >

                    <?php endif; ?>

                </a>


            </div>


        </div>


    </div>



    <div class="footer-bottom">


        <span>

            © 2026 Mimic Haven Collectibles.
            All Rights Reserved.

        </span>


        <span>

            Crafted with precision &amp; passion.

        </span>


    </div>


</footer>



<div
    id="toast"
    class="toast"
    role="status"
    aria-live="polite"
></div>



<script>
window.MIMIC_HAVEN_CART_KEY = <?= json_encode(
    isLoggedIn()
        ? 'mimicHavenCart_' . currentUserId()
        : 'mimicHavenGuestCart'
) ?>;
</script>

<script src="script.js"></script>



<!-- =========================================================
     HEADER SEARCH
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const headerSearch =
            document.getElementById(
                'headerSearch'
            );


        const headerSearchToggle =
            document.getElementById(
                'headerSearchToggle'
            );


        const headerSearchInput =
            document.getElementById(
                'headerSearchInput'
            );


        if (
            !headerSearch ||
            !headerSearchToggle ||
            !headerSearchInput
        ) {

            return;

        }


        /* =================================================
           OPEN SEARCH
        ================================================= */

        headerSearchToggle.addEventListener(
            'click',
            function (event) {

                event.stopPropagation();


                const isOpen =
                    headerSearch.classList.contains(
                        'active'
                    );


                if (isOpen) {

                    headerSearchInput.focus();

                    return;

                }


                headerSearch.classList.add(
                    'active'
                );


                headerSearchToggle.setAttribute(
                    'aria-expanded',
                    'true'
                );


                setTimeout(
                    function () {

                        headerSearchInput.focus();

                    },
                    250
                );

            }
        );


        /* =================================================
           ENTER TO SEARCH
        ================================================= */

        headerSearchInput.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key !== 'Enter'
                ) {

                    return;

                }


                const search =
                    headerSearchInput.value.trim();


                if (!search) {

                    return;

                }


                window.location.href =
                    'collection.php?search=' +
                    encodeURIComponent(
                        search
                    );

            }
        );


        /* =================================================
           CLOSE WHEN CLICKING OUTSIDE
        ================================================= */

        document.addEventListener(
            'click',
            function (event) {

                if (
                    !headerSearch.contains(
                        event.target
                    )
                ) {

                    headerSearch.classList.remove(
                        'active'
                    );


                    headerSearchToggle.setAttribute(
                        'aria-expanded',
                        'false'
                    );

                }

            }
        );


        /* =================================================
           ESCAPE TO CLOSE
        ================================================= */

        headerSearchInput.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape'
                ) {

                    headerSearch.classList.remove(
                        'active'
                    );


                    headerSearchToggle.setAttribute(
                        'aria-expanded',
                        'false'
                    );


                    headerSearchInput.value = '';

                    headerSearchToggle.focus();

                }

            }
        );


    }
);

</script>



<!-- =========================================================
     COLLECTION PAGE JAVASCRIPT
========================================================= -->

<script>

const collectionCards =
    [...document.querySelectorAll(
        '.collection-product-card'
    )];


const collectionGrid =
    document.getElementById(
        'collectionProductGrid'
    );


const collectionSearch =
    document.getElementById(
        'collectionSearchInput'
    );


const collectionSort =
    document.getElementById(
        'collectionSort'
    );


const collectionPagination =
    document.getElementById(
        'collectionPagination'
    );


const collectionNoResults =
    document.getElementById(
        'collectionNoResults'
    );


const collectionResultStart =
    document.getElementById(
        'collectionResultStart'
    );


const collectionResultEnd =
    document.getElementById(
        'collectionResultEnd'
    );


const collectionResultTotal =
    document.getElementById(
        'collectionResultTotal'
    );


const collectionGridView =
    document.getElementById(
        'collectionGridView'
    );


const collectionListView =
    document.getElementById(
        'collectionListView'
    );


const clearFiltersButton =
    document.getElementById(
        'clearFilters'
    );


const selectedSeries =
    <?= json_encode(
        $selectedSeries,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ) ?>;


const headerSearchQuery =
    <?= json_encode(
        $headerSearchQuery,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ) ?>;


const COLLECTION_PER_PAGE = 12;


let collectionCurrentPage = 1;


let collectionFiltered =
    [...collectionCards];


/* =========================================================
   CHECKED VALUES
========================================================= */

function collectionCheckedValues(selector)
{
    return [
        ...document.querySelectorAll(
            selector + ':checked'
        )
    ].map(
        input => input.value
    );
}


/* =========================================================
   FILTER
========================================================= */

function filterCollection()
{

    const search =
        collectionSearch.value
            .trim()
            .toLowerCase();


    const categories =
        collectionCheckedValues(
            '.category-filter'
        );


    const franchises =
        collectionCheckedValues(
            '.franchise-filter'
        );


    const conditions =
        collectionCheckedValues(
            '.condition-filter'
        );


    const availability =
        collectionCheckedValues(
            '.availability-filter'
        );


    collectionFiltered =
        collectionCards.filter(
            card => {


                const name =
                    card.dataset.name
                        .toLowerCase();


                const series =
                    card.dataset.series
                        .toLowerCase();


                const matchesSearch =
                    !search ||
                    name.includes(search) ||
                    series.includes(search);


                const matchesCategory =
                    categories.length === 0 ||
                    categories.includes(
                        card.dataset.category
                    );


                const matchesFranchise =
                    franchises.length === 0 ||
                    franchises.includes(
                        card.dataset.series
                    );


                const matchesCondition =
                    conditions.length === 0 ||
                    conditions.includes(
                        card.dataset.condition
                    );


                const matchesAvailability =
                    availability.length === 0 ||
                    availability.includes(
                        card.dataset.availability
                    );


                return (
                    matchesSearch &&
                    matchesCategory &&
                    matchesFranchise &&
                    matchesCondition &&
                    matchesAvailability
                );

            }
        );


    collectionCurrentPage = 1;


    sortCollection();

}


/* =========================================================
   SORT
========================================================= */

function sortCollection()
{

    const sort =
        collectionSort.value;


    collectionFiltered.sort(
        (a, b) => {


            if (sort === 'name') {

                return a.dataset.name
                    .localeCompare(
                        b.dataset.name
                    );

            }


            if (sort === 'price-low') {

                return (
                    Number(
                        a.dataset.price
                    ) -
                    Number(
                        b.dataset.price
                    )
                );

            }


            if (sort === 'price-high') {

                return (
                    Number(
                        b.dataset.price
                    ) -
                    Number(
                        a.dataset.price
                    )
                );

            }


            return (
                Number(
                    a.dataset.index
                ) -
                Number(
                    b.dataset.index
                )
            );

        }
    );


    renderCollection();

}


/* =========================================================
   RENDER
========================================================= */

function renderCollection()
{

    collectionCards.forEach(
        card => {

            card.style.display =
                'none';

        }
    );


    const start =
        (
            collectionCurrentPage - 1
        ) *
        COLLECTION_PER_PAGE;


    const end =
        start +
        COLLECTION_PER_PAGE;


    const visibleCards =
        collectionFiltered.slice(
            start,
            end
        );


    visibleCards.forEach(
        card => {

            card.style.display =
                '';


            collectionGrid.appendChild(
                card
            );

        }
    );


    const total =
        collectionFiltered.length;


    collectionResultTotal.textContent =
        total;


    if (total === 0) {

        collectionNoResults.style.display =
            'block';


        collectionResultStart.textContent =
            '0';


        collectionResultEnd.textContent =
            '0';

    } else {

        collectionNoResults.style.display =
            'none';


        collectionResultStart.textContent =
            start + 1;


        collectionResultEnd.textContent =
            Math.min(
                end,
                total
            );

    }


    renderCollectionPagination();

}


/* =========================================================
   PAGINATION
========================================================= */

function renderCollectionPagination()
{

    collectionPagination.innerHTML =
        '';


    const totalPages =
        Math.ceil(
            collectionFiltered.length /
            COLLECTION_PER_PAGE
        );


    if (totalPages <= 1) {

        return;

    }


    for (
        let page = 1;
        page <= totalPages;
        page++
    ) {


        const button =
            document.createElement(
                'button'
            );


        button.type =
            'button';


        button.className =
            'collection-page-button';


        button.textContent =
            page;


        if (
            page ===
            collectionCurrentPage
        ) {

            button.classList.add(
                'active'
            );

        }


        button.addEventListener(
            'click',
            () => {

                collectionCurrentPage =
                    page;


                renderCollection();


                window.scrollTo({

                    top: 400,

                    behavior: 'smooth'

                });

            }
        );


        collectionPagination.appendChild(
            button
        );

    }

}


/* =========================================================
   CLEAR FILTERS
========================================================= */

clearFiltersButton.addEventListener(
    'click',
    () => {


        document
            .querySelectorAll(
                '.collection-sidebar input[type="checkbox"]'
            )
            .forEach(
                input => {

                    input.checked =
                        false;

                }
            );


        collectionSearch.value =
            '';


        sortCollection();

    }
);


/* =========================================================
   SEARCH
========================================================= */

collectionSearch.addEventListener(
    'input',
    filterCollection
);


/* =========================================================
   SORT
========================================================= */

collectionSort.addEventListener(
    'change',
    sortCollection
);


/* =========================================================
   FILTER EVENTS
========================================================= */

document
    .querySelectorAll(
        '.collection-sidebar input[type="checkbox"]'
    )
    .forEach(
        input => {

            input.addEventListener(
                'change',
                filterCollection
            );

        }
    );


/* =========================================================
   FAVORITES
========================================================= */

document
    .querySelectorAll(
        '.collection-favorite'
    )
    .forEach(
        button => {


            button.addEventListener(
                'click',
                event => {

                    event.stopPropagation();


                    button.classList.toggle(
                        'favorited'
                    );


                    button.textContent =
                        button.classList.contains(
                            'favorited'
                        )
                        ? '♥'
                        : '♡';

                }
            );

        }
    );


/* =========================================================
   OPEN PRODUCT DETAILS
========================================================= */

document
    .querySelectorAll(
        '.collection-product-card'
    )
    .forEach(
        card => {


            card.addEventListener(
                'click',
                event => {


                    if (
                        event.target.closest(
                            '.collection-favorite'
                        )
                    ) {

                        return;

                    }


                    const productId =
                        card.dataset.productId;


                    if (productId) {

                        window.location.href =
                            `product.php?id=${encodeURIComponent(
                                productId
                            )}`;

                    }

                }
            );

        }
    );


/* =========================================================
   GRID / LIST
========================================================= */

collectionGridView.addEventListener(
    'click',
    () => {

        collectionGrid.classList.remove(
            'list-view'
        );


        collectionGridView.classList.add(
            'active'
        );


        collectionListView.classList.remove(
            'active'
        );

    }
);


collectionListView.addEventListener(
    'click',
    () => {

        collectionGrid.classList.add(
            'list-view'
        );


        collectionListView.classList.add(
            'active'
        );


        collectionGridView.classList.remove(
            'active'
        );

    }
);


/* =========================================================
   INITIAL LOAD
========================================================= */

if (headerSearchQuery) {

    collectionSearch.value =
        headerSearchQuery;


    filterCollection();

} else if (selectedSeries) {

    filterCollection();

} else {

    renderCollection();

}

</script>


</body>
</html>