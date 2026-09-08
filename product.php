<?php

/* =========================================================
   MIMIC HAVEN - PRODUCT DETAILS PAGE
========================================================= */

require_once 'db.php';

function asset(string $name): string {
    foreach (['png','jpg','jpeg','webp','svg'] as $ext) {

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


function icon(string $name): string {

    foreach (['png','jpg','jpeg','webp','svg'] as $ext) {

        $file = __DIR__ . "/assets/icons/{$name}.{$ext}";

        if (file_exists($file)) {
            return "assets/icons/{$name}.{$ext}";
        }
    }

    return "";
}


function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}


function peso(int $price): string {
    return '₱' . number_format($price);
}


/* =========================================================
   PRODUCT DATABASE
   Temporary PHP array for our current project.
========================================================= */

$products = [

    /* =========================
       1 - FRIEREN AMP
    ========================= */

    1 => [
        'name' => 'Frieren – AMP Figure',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Prize Figure',
        'price' => 1450,
        'condition' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'frieren amp.webp',
        'description' =>
            'A carefully selected Frieren figure for collectors looking to add a character piece from Frieren: Beyond Journey’s End to their collection.'
    ],


    /* =========================
       2 - FRIEREN ESPRESTO
    ========================= */

    2 => [
        'name' => 'Frieren – ESPRESTO Figure',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Prize Figure',
        'price' => 1350,
        'condition' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'frieren espresto.webp',
        'description' =>
            'A Frieren collectible selected for fans and collectors of the series.'
    ],


    /* =========================
       3 - FRIEREN MAXIMATIC
    ========================= */

    3 => [
        'name' => 'Frieren – Maximatic Ver. 2',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Prize Figure',
        'price' => 1850,
        'condition' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'frieren maximatic v2.webp',
        'description' =>
            'Reserve this Frieren figure through Mimic Haven’s pre-order system.'
    ],


    /* =========================
       4 - FRIEREN SOFVIMATES
    ========================= */

    4 => [
        'name' => 'Frieren – Sofvimates',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Prize Figure',
        'price' => 950,
        'condition' => 'MIB',
        'availability' => 'In Stock',
        'image' => 'frieren sofvimates.webp',
        'description' =>
            'A compact Frieren collectible for anime figure collectors.'
    ],


    /* =========================
       5 - ICHIBANSHO FRIEREN
    ========================= */

    5 => [
        'name' => 'Frieren – Ichibansho Art Scale Bust',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Statue',
        'price' => 4200,
        'condition' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Ichibansho Frieren Art Scale Bust.webp',
        'description' =>
            'A premium Frieren display piece available for pre-order.'
    ],


    /* =========================
       6 - FRIEREN FUNKO
    ========================= */

    6 => [
        'name' => 'Frieren – Funko Pop! Deluxe #2358',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Others',
        'price' => 1250,
        'condition' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Pop! Deluxe #2358 Frieren.webp',
        'description' =>
            'A Funko collectible featuring Frieren.'
    ],


    /* =========================
       7 - FERN
    ========================= */

    7 => [
        'name' => 'Fern – Yumemirize Nap Ver.',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Prize Figure',
        'price' => 1450,
        'condition' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Yumemirize Fern (Nap Ver.) Figure.webp',
        'description' =>
            'A Fern collectible available for pre-order.'
    ],


    /* =========================
       8 - HIMMEL
    ========================= */

    8 => [
        'name' => 'Himmel – ESPRESTO Figure',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Prize Figure',
        'price' => 1550,
        'condition' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'himmel espresto.webp',
        'description' =>
            'A Himmel figure selected for collectors of the series.'
    ],


    /* =========================
       9 - MAOMAO BREAK TIME
    ========================= */

    9 => [
        'name' => 'Maomao – Break Time Collection Vol. 1',
        'series' => 'The Apothecary Diaries',
        'category' => 'Prize Figure',
        'price' => 1450,
        'condition' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Break Time Collection Vol.1 Maomao.webp',
        'description' =>
            'A Maomao collectible from The Apothecary Diaries.'
    ],


    /* =========================
       10 - MAOMAO MOON FAIRY
    ========================= */

    10 => [
        'name' => 'Maomao – Moon Fairy Figure',
        'series' => 'The Apothecary Diaries',
        'category' => 'Scale Figure',
        'price' => 2800,
        'condition' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Maomao (Moon Fairy) Figure.webp',
        'description' =>
            'A Maomao figure currently offered as a pre-order.'
    ],


    /* =========================
       11 - MAOMAO GARDEN PARTY
    ========================= */

    11 => [
        'name' => 'Maomao – Oshi Works Mini Garden Party Ver.',
        'series' => 'The Apothecary Diaries',
        'category' => 'Prize Figure',
        'price' => 1600,
        'condition' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Oshi Works Mini Maomao (Garden Party Ver.).webp',
        'description' =>
            'A compact Maomao collectible featuring the Garden Party version.'
    ],


    /* =========================
       12 - MAOMAO MINI
    ========================= */

    12 => [
        'name' => 'Maomao – Oshi Works Mini Figure',
        'series' => 'The Apothecary Diaries',
        'category' => 'Prize Figure',
        'price' => 1300,
        'condition' => 'MIB',
        'availability' => 'In Stock',
        'image' => 'Oshi Works Mini Maomao Figure.webp',
        'description' =>
            'A compact collectible featuring Maomao.'
    ],


    /* =========================
       13 - GOJO
    ========================= */

    13 => [
        'name' => 'Satoru Gojo – 1/8 Scale Figure',
        'series' => 'Jujutsu Kaisen',
        'category' => 'Scale Figure',
        'price' => 8500,
        'condition' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Gojo Satoru 1over8 Scale Figure.webp',
        'description' =>
            'A larger-scale Satoru Gojo collectible available for pre-order.'
    ],


    /* =========================
       14 - CHOSO
    ========================= */

    14 => [
        'name' => 'Choso – S.H.Figuarts',
        'series' => 'Jujutsu Kaisen',
        'category' => 'Action Figure',
        'price' => 3200,
        'condition' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'S.H.Figuarts Choso Action Figure.webp',
        'description' =>
            'A Choso action figure available for reservation.'
    ],


    /* =========================
       15 - NANAMI
    ========================= */

    15 => [
        'name' => 'Kento Nanami – S.H.Figuarts',
        'series' => 'Jujutsu Kaisen',
        'category' => 'Action Figure',
        'price' => 3200,
        'condition' => 'MIB',
        'availability' => 'In Stock',
        'image' => 'S.H.Figuarts Kento Nanami Action Figure.webp',
        'description' =>
            'A Kento Nanami action figure for Jujutsu Kaisen collectors.'
    ],


    /* =========================
       16 - MAHITO
    ========================= */

    16 => [
        'name' => 'Mahito – S.H.Figuarts',
        'series' => 'Jujutsu Kaisen',
        'category' => 'Action Figure',
        'price' => 3100,
        'condition' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'S.H.Figuarts Mahito Action Figure.webp',
        'description' =>
            'A Mahito action figure available through pre-order.'
    ],


    /* =========================
       17 - TOJI
    ========================= */

    17 => [
        'name' => 'Toji Fushiguro – S.H.Figuarts',
        'series' => 'Jujutsu Kaisen',
        'category' => 'Action Figure',
        'price' => 3400,
        'condition' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'S.H.Figuarts Toji Fushiguro Action Figure.webp',
        'description' =>
            'A Toji Fushiguro action figure for collectors.'
    ],


    /* =========================
       18 - AKAZA
    ========================= */

    18 => [
        'name' => 'Akaza II – Grandista',
        'series' => 'Demon Slayer',
        'category' => 'Prize Figure',
        'price' => 1900,
        'condition' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Grandista Akaza II Figure.webp',
        'description' =>
            'A Demon Slayer collectible featuring Akaza.'
    ],


    /* =========================
       19 - DOMA
    ========================= */

    19 => [
        'name' => 'Doma II – Grandista',
        'series' => 'Demon Slayer',
        'category' => 'Prize Figure',
        'price' => 1900,
        'condition' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Grandista Doma II Figure.webp',
        'description' =>
            'A Demon Slayer collectible featuring Doma.'
    ],


    /* =========================
       20 - GIYU
    ========================= */

    20 => [
        'name' => 'Giyu Tomioka – Grandista',
        'series' => 'Demon Slayer',
        'category' => 'Prize Figure',
        'price' => 2200,
        'condition' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Grandista Giyu Tomioka.jpg',
        'description' =>
            'A Giyu Tomioka collectible for Demon Slayer collectors.'
    ],


    /* =========================
       21 - ZENITSU A
    ========================= */

    21 => [
        'name' => 'Zenitsu Agatsuma – Grandista Another Color Ver. A',
        'series' => 'Demon Slayer',
        'category' => 'Prize Figure',
        'price' => 1800,
        'condition' => 'MIB',
        'availability' => 'In Stock',
        'image' => 'Grandista Zenitsu Agatsuma (Another Color Ver. A).jpg',
        'description' =>
            'A Zenitsu Agatsuma collectible in Another Color Ver. A.'
    ],


    /* =========================
       22 - ZENITSU B
    ========================= */

    22 => [
        'name' => 'Zenitsu Agatsuma – Grandista Another Color Ver. B',
        'series' => 'Demon Slayer',
        'category' => 'Prize Figure',
        'price' => 1800,
        'condition' => 'MIB',
        'availability' => 'Pre-Order',
        'image' => 'Grandista Zenitsu Agatsuma (Another Color Ver. B).jpg',
        'description' =>
            'A Zenitsu Agatsuma collectible in Another Color Ver. B.'
    ],


    /* =========================
       23 - ZENITSU DELUXE
    ========================= */

    23 => [
        'name' => 'Zenitsu – Deluxe 1/4 Scale Limited Edition Statue',
        'series' => 'Demon Slayer',
        'category' => 'Statue',
        'price' => 12500,
        'condition' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Zenitsu Deluxe 1over4 Scale Limited Edition Statue.jpg',
        'description' =>
            'A larger-scale Zenitsu display piece available for pre-order.'
    ],


    /* =========================
       24 - LUFFY GEAR 5 II
    ========================= */

    24 => [
        'name' => 'Monkey D. Luffy – Grandista Gear 5 II',
        'series' => 'One Piece',
        'category' => 'Prize Figure',
        'price' => 2200,
        'condition' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Grandista Monkey D. Luffy (Gear 5) II Figure.webp',
        'description' =>
            'A Monkey D. Luffy collectible featuring Gear 5.'
    ],


    /* =========================
       25 - LUFFY SPECIAL
    ========================= */

    25 => [
        'name' => 'Monkey D. Luffy – Grandista Gear 5 Special Edition',
        'series' => 'One Piece',
        'category' => 'Prize Figure',
        'price' => 2400,
        'condition' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Grandista Monkey D. Luffy (Gear5 Special Edition) Figure.webp',
        'description' =>
            'A Gear 5 Special Edition Luffy collectible available for reservation.'
    ],


    /* =========================
       26 - LUFFY SPECIAL EDITION
    ========================= */

    26 => [
        'name' => 'Monkey D. Luffy – Grandista Special Edition',
        'series' => 'One Piece',
        'category' => 'Prize Figure',
        'price' => 2100,
        'condition' => 'MIB',
        'availability' => 'In Stock',
        'image' => 'Grandista Monkey D. Luffy (Special Edition).webp',
        'description' =>
            'A special-edition Luffy figure for One Piece collectors.'
    ],


    /* =========================
       27 - LUFFY GEAR 5 II SPECIAL
    ========================= */

    27 => [
        'name' => 'Monkey D. Luffy – Gear 5 II Special Edition',
        'series' => 'One Piece',
        'category' => 'Prize Figure',
        'price' => 2500,
        'condition' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Grandista Monkey D. Luffy Gear 5 II (Special Edition) Figure.jpg',
        'description' =>
            'A Gear 5 II Special Edition Luffy collectible.'
    ],


    /* =========================
       28 - LUFFY VER III
    ========================= */

    28 => [
        'name' => 'Monkey D. Luffy – Gear 5 Ver. III',
        'series' => 'One Piece',
        'category' => 'Prize Figure',
        'price' => 2300,
        'condition' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'One Piece Grandista Monkey D. Luffy (Gear 5 Ver. III) Figure.webp',
        'description' =>
            'A Gear 5 Ver. III Luffy collectible available for reservation.'
    ],


    /* =========================
       29 - NAMI
    ========================= */

    29 => [
        'name' => 'Nami – Grandista',
        'series' => 'One Piece',
        'category' => 'Prize Figure',
        'price' => 1800,
        'condition' => 'MIB',
        'availability' => 'In Stock',
        'image' => 'Grandista nero Nami.webp',
        'description' =>
            'A Nami collectible for One Piece collectors.'
    ],


    /* =========================
       30 - MAKIMA FNEX
    ========================= */

    30 => [
        'name' => 'Makima – FNEX 1/7 Scale Figure',
        'series' => 'Chainsaw Man',
        'category' => 'Scale Figure',
        'price' => 11500,
        'condition' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Chainsaw Man FNex Makima 1over7 Scale Figure.webp',
        'description' =>
            'A 1/7 scale Makima collectible available for pre-order.'
    ],


    /* =========================
       31 - MAKIMA FUNKO
    ========================= */

    31 => [
        'name' => 'Makima – Funko Pop! Animation #1679',
        'series' => 'Chainsaw Man',
        'category' => 'Others',
        'price' => 950,
        'condition' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Pop! Animation #1679 Makima.jpg',
        'description' =>
            'A Funko collectible featuring Makima.'
    ],


    /* =========================
       32 - ALBEDO NOODLE STOPPER
    ========================= */

    32 => [
        'name' => 'Albedo – Loungewear Pearl White Ver. Noodle Stopper',
        'series' => 'Overlord',
        'category' => 'Prize Figure',
        'price' => 1500,
        'condition' => 'MISB',
        'availability' => 'In Stock',
        'image' => 'Overlord Albedo (Loungewear Pearl White Ver.) Noodle Stopper Figure.webp',
        'description' =>
            'An Albedo collectible from Overlord.'
    ],


    /* =========================
       33 - ALBEDO 1/7
    ========================= */

    33 => [
        'name' => 'Albedo – 1/7 Scale Figure',
        'series' => 'Overlord',
        'category' => 'Scale Figure',
        'price' => 9500,
        'condition' => 'MISB',
        'availability' => 'Pre-Order',
        'image' => 'Overlord Albedo 1over7 Scale Figure.webp',
        'description' =>
            'A larger-scale Albedo collectible available for pre-order.'
    ],


    /* =========================
       34 - ALBEDO FIGMA
    ========================= */

    34 => [
        'name' => 'Albedo – figma No. 693',
        'series' => 'Overlord',
        'category' => 'Action Figure',
        'price' => 5200,
        'condition' => 'MIB',
        'availability' => 'In Stock',
        'image' => 'Overlord figma No.693 Albedo Action Figure.webp',
        'description' =>
            'An articulated Albedo collectible for Overlord fans.'
    ],


    /* =========================
       35 - IMAGE PLACEHOLDER
       This file was uploaded but its figure identity
       is not confirmed yet, so we keep it generic.
    ========================= */

    35 => [
        'name' => 'Mimic Haven Featured Collectible',
        'series' => 'Featured Collection',
        'category' => 'Others',
        'price' => 1500,
        'condition' => 'LOOSE',
        'availability' => 'In Stock',
        'image' => 'images.jpg',
        'description' =>
            'A featured collectible currently listed in the Mimic Haven collection.'
    ],

];

/* =========================================================
   LOAD PRODUCT FROM MYSQL
========================================================= */

$productId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$productId) {
    $productId = 1;
}

$stmt = $conn->prepare(
    "SELECT
        id,
        name,
        series,
        category,
        price,
        condition_status,
        availability,
        image,
        description
     FROM products
     WHERE id = ?"
);

$stmt->bind_param(
    "i",
    $productId
);

$stmt->execute();

$result = $stmt->get_result();

$dbProduct = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   USE MYSQL PRODUCT
========================================================= */

if ($dbProduct) {

    $product = [
        'name' => $dbProduct['name'],
        'series' => $dbProduct['series'],
        'category' => $dbProduct['category'],
        'price' => (int) $dbProduct['price'],
        'condition' => $dbProduct['condition_status'],
        'availability' => $dbProduct['availability'],
        'image' => $dbProduct['image'],
        'description' => $dbProduct['description'] ?? ''
    ];

} else {

    $productId = 1;
    $product = $products[1];

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
        <?= e($product['name']) ?> | Mimic Haven Collectibles
    </title>

    <meta
        name="description"
        content="<?= e($product['name']) ?> — <?= e($product['series']) ?> available from Mimic Haven Collectibles."
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

</head>


<body>


<!-- =========================================================
     SAME HEADER
========================================================= -->

<header
    class="site-header"
    id="home"
>

    <div class="header-inner">


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
                    <span>COLLECTIBLES</span>
                </div>

            <?php endif; ?>

        </a>


        <nav
            class="nav"
            id="main-nav"
            aria-label="Primary navigation"
        >

            <a href="index.php">
                Home
            </a>

            <a href="collection.php">
                Collection
            </a>

            <a href="preorder.php">
                Pre-Orders
            </a>

            <a href="index.php#about">
                About
            </a>

            <a href="index.php#contact">
                Contact
            </a>


            <a
                class="nav-icon"
                href="collection.php#collection-search"
                aria-label="Search"
            >

                <?php if (icon('search')): ?>

                    <img
                        src="<?= icon('search') ?>"
                        alt="Search"
                    >

                <?php endif; ?>

            </a>


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


            <a
                class="browse-btn"
                href="collection.php"
            >
                Browse Figures
            </a>

        </nav>


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



<!-- =========================================================
     PRODUCT PAGE
========================================================= -->

<main class="product-page">


    <!-- BREADCRUMB -->

    <div class="product-breadcrumb">

        <a href="index.php">
            Home
        </a>

        <span>
            ›
        </span>

        <a href="collection.php">
            Collection
        </a>

        <span>
            ›
        </span>

        <strong>
            <?= e($product['name']) ?>
        </strong>

    </div>



    <!-- PRODUCT DETAIL -->

    <section class="product-detail">


        <!-- PRODUCT IMAGE -->

        <div class="product-gallery">

            <div class="product-main-image">

                <img
                    src="assets/collections/<?= rawurlencode($product['image']) ?>"
                    alt="<?= e($product['name']) ?>"
                >


                <span class="product-condition-badge">

                    <?= e($product['condition']) ?>

                </span>

            </div>


            <div class="product-thumbnail">

                <img
                    src="assets/collections/<?= rawurlencode($product['image']) ?>"
                    alt="<?= e($product['name']) ?>"
                >

            </div>

        </div>



        <!-- PRODUCT INFO -->

        <div class="product-detail-info">


            <span class="product-detail-series">
                <?= e($product['series']) ?>
            </span>


            <h1>
                <?= e($product['name']) ?>
            </h1>


            <div class="product-detail-price">

                <?= peso($product['price']) ?>

            </div>


            <div class="product-detail-status">

                <span class="
                    <?= $product['availability'] === 'Pre-Order'
                        ? 'preorder'
                        : 'instock'
                    ?>
                ">

                    <?= e($product['availability']) ?>

                </span>

            </div>


            <div class="product-detail-divider"></div>


            <p class="product-description">

                <?= e($product['description']) ?>

            </p>


            <!-- PRODUCT SPECIFICATIONS -->

            <div class="product-specs">

                <div class="product-spec">

                    <span>
                        Category
                    </span>

                    <strong>
                        <?= e($product['category']) ?>
                    </strong>

                </div>


                <div class="product-spec">

                    <span>
                        Condition
                    </span>

                    <strong>
                        <?= e($product['condition']) ?>
                    </strong>

                </div>


                <div class="product-spec">

                    <span>
                        Availability
                    </span>

                    <strong>
                        <?= e($product['availability']) ?>
                    </strong>

                </div>

            </div>


            <!-- QUANTITY -->

            <div class="product-purchase-row">

                <div class="quantity-control">

                    <button
                        type="button"
                        id="quantityMinus"
                    >
                        −
                    </button>

                    <input
                        type="number"
                        id="productQuantity"
                        value="1"
                        min="1"
                        max="10"
                    >

                    <button
                        type="button"
                        id="quantityPlus"
                    >
                        +
                    </button>

                </div>


                <button
                    type="button"
                    class="product-add-cart"
                    id="addToCart"
                    data-product-id="<?= $productId ?>"
                    data-product-name="<?= e($product['name']) ?>"
                    data-product-price="<?= $product['price'] ?>"
                >

                    Add to Cart

                </button>

            </div>


            <!-- PRE-ORDER -->

            <?php if ($product['availability'] === 'Pre-Order'): ?>

                <a
                    class="product-preorder-button"
                    href="preorder.php"
                >
                    Reserve as Pre-Order
                    →
                </a>

            <?php endif; ?>


            <!-- TRUST -->

            <div class="product-trust">

                <div>
                    <strong>
                        100% Authentic
                    </strong>

                    <span>
                        Original &amp; official products
                    </span>
                </div>


                <div>
                    <strong>
                        Packed with Care
                    </strong>

                    <span>
                        Secure packaging guaranteed
                    </span>
                </div>


                <div>
                    <strong>
                        Collector Focused
                    </strong>

                    <span>
                        Made for collectors
                    </span>
                </div>

            </div>

        </div>

    </section>



    <!-- =====================================================
         PRODUCT INFORMATION
    ===================================================== -->

    <section class="product-information">

        <div class="product-information-tabs">

            <button
                class="product-tab active"
                type="button"
            >
                Product Details
            </button>

            <button
                class="product-tab"
                type="button"
            >
                Condition Guide
            </button>

            <button
                class="product-tab"
                type="button"
            >
                Ordering Information
            </button>

        </div>


        <div class="product-information-content">

            <div class="product-info-block active">

                <h2>
                    Product Details
                </h2>

                <p>
                    This collectible is part of the Mimic Haven
                    curated collection. Product information shown
                    on this page is intended to help collectors
                    review the item before adding it to their order.
                </p>

                <div class="product-detail-list">

                    <div>
                        <span>
                            Figure
                        </span>

                        <strong>
                            <?= e($product['name']) ?>
                        </strong>
                    </div>


                    <div>
                        <span>
                            Series
                        </span>

                        <strong>
                            <?= e($product['series']) ?>
                        </strong>
                    </div>


                    <div>
                        <span>
                            Category
                        </span>

                        <strong>
                            <?= e($product['category']) ?>
                        </strong>
                    </div>


                    <div>
                        <span>
                            Condition
                        </span>

                        <strong>
                            <?= e($product['condition']) ?>
                        </strong>
                    </div>

                </div>

            </div>



            <div class="product-info-block">

                <h2>
                    Condition Guide
                </h2>

                <p>
                    Mimic Haven uses standardized condition labels
                    to make the state of each collectible easier
                    to understand.
                </p>

                <div class="product-condition-list">

                    <div>
                        <strong>MISB</strong>
                        <span>
                            Mint in Sealed Box
                        </span>
                    </div>

                    <div>
                        <strong>MIB</strong>
                        <span>
                            Mint in Box
                        </span>
                    </div>

                    <div>
                        <strong>BIB</strong>
                        <span>
                            Box Opened
                        </span>
                    </div>

                    <div>
                        <strong>LOOSE</strong>
                        <span>
                            No Original Packaging
                        </span>
                    </div>

                </div>

            </div>



            <div class="product-info-block">

                <h2>
                    Ordering Information
                </h2>

                <p>
                    Please review the product condition,
                    availability, and applicable Mimic Haven
                    terms before completing your order.
                </p>

                <a
                    href="preorder.php"
                    class="product-information-link"
                >
                    View Pre-Order Information →
                </a>

            </div>

        </div>

    </section>



    <!-- =====================================================
         BACK TO COLLECTION
    ===================================================== -->

    <div class="back-to-collection">

        <a
            class="btn outline"
            href="collection.php"
        >
            ← Back to Collection
        </a>

    </div>

</main>



<!-- =========================================================
     SAME FOOTER
========================================================= -->

<footer id="contact">

    <div class="footer-grid">


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

            <a href="index.php#about">
                About
            </a>

            <a href="index.php#contact">
                Contact
            </a>

        </div>


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
            © 2026 Mimic Haven Collectibles. All Rights Reserved.
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


<script src="script.js"></script>


<script>

/* =========================================================
   PRODUCT PAGE - QUANTITY
========================================================= */

const quantityInput =
    document.getElementById('productQuantity');

const quantityMinus =
    document.getElementById('quantityMinus');

const quantityPlus =
    document.getElementById('quantityPlus');


quantityMinus?.addEventListener('click', () => {

    let quantity =
        Number(quantityInput.value) || 1;

    quantity =
        Math.max(1, quantity - 1);

    quantityInput.value =
        quantity;

});


quantityPlus?.addEventListener('click', () => {

    let quantity =
        Number(quantityInput.value) || 1;

    quantity =
        Math.min(10, quantity + 1);

    quantityInput.value =
        quantity;

});


/* =========================================================
   PRODUCT TABS
========================================================= */

const productTabs =
    document.querySelectorAll('.product-tab');

const productBlocks =
    document.querySelectorAll('.product-info-block');


productTabs.forEach((tab, index) => {

    tab.addEventListener('click', () => {

        productTabs.forEach(item => {
            item.classList.remove('active');
        });

        productBlocks.forEach(block => {
            block.classList.remove('active');
        });

        tab.classList.add('active');

        productBlocks[index]
            ?.classList.add('active');

    });

});


/* =========================================================
   ADD TO CART
========================================================= */

const addToCart =
    document.getElementById('addToCart');


addToCart?.addEventListener('click', () => {

    const id =
        addToCart.dataset.productId;

    const name =
        addToCart.dataset.productName;

    const price =
        Number(addToCart.dataset.productPrice);

    const quantity =
        Math.max(
            1,
            Number(quantityInput?.value) || 1
        );


    let cart =
        JSON.parse(
            localStorage.getItem('mimicHavenCart') || '[]'
        );


    const existing =
        cart.find(item => String(item.id) === String(id));


    if (existing) {

        existing.quantity += quantity;

    } else {

        cart.push({
    id: id,
    name: name,
    price: price,
    quantity: quantity,
    series: <?= json_encode($product['series']) ?>,
    condition: <?= json_encode($product['condition']) ?>,
    image: <?= json_encode($product['image']) ?>
});

    }


    localStorage.setItem(
        'mimicHavenCart',
        JSON.stringify(cart)
    );


    const cartCount =
        document.getElementById('cart-count');


    const totalItems =
        cart.reduce(
            (total, item) => total + item.quantity,
            0
        );


    if (cartCount) {

        cartCount.textContent =
            totalItems;

    }


    addToCart.textContent =
        'Added to Cart ✓';


    setTimeout(() => {

        addToCart.textContent =
            'Add to Cart';

    }, 1800);


    if (typeof showToast === 'function') {

        showToast(
            `${name} added to your cart.`
        );

    }

});


/* =========================================================
   LOAD CART COUNT
========================================================= */

const savedCart =
    JSON.parse(
        localStorage.getItem('mimicHavenCart') || '[]'
    );


const savedCartCount =
    savedCart.reduce(
        (total, item) => total + item.quantity,
        0
    );


const pageCartCount =
    document.getElementById('cart-count');


if (pageCartCount) {

    pageCartCount.textContent =
        savedCartCount;

}

</script>


</body>

</html>