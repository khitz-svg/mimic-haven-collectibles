<?php

/* =========================================================
   MIMIC HAVEN - PRE-ORDERS PAGE
========================================================= */

function asset(string $name): string {
    foreach (['png','jpg','jpeg','webp','svg'] as $ext) {
        $assetFile = __DIR__ . "/assets/{$name}.{$ext}";
        if (file_exists($assetFile)) return "assets/{$name}.{$ext}";

        $rootFile = __DIR__ . "/{$name}.{$ext}";
        if (file_exists($rootFile)) return "{$name}.{$ext}";
    }

    return "";
}

function icon(string $name): string {
    foreach (['png','jpg','jpeg','webp','svg'] as $ext) {
        $file = __DIR__ . "/assets/icons/{$name}.{$ext}";
        if (file_exists($file)) return "assets/icons/{$name}.{$ext}";
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
   PRE-ORDER PRODUCTS
   NOTE:
   Prices/deposits below are starter/demo values.
   Update them when your actual store pricing is finalized.
========================================================= */

$preorders = [

    [
        'name' => 'Frieren – Maximatic Ver. 2',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Prize Figure',
        'price' => 1850,
        'deposit' => 550,
        'release' => 'Release Date TBA',
        'image' => 'frieren maximatic v2.webp'
    ],

    [
        'name' => 'Frieren – Ichibansho Art Scale Bust',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Scale Figure',
        'price' => 4200,
        'deposit' => 1250,
        'release' => 'Release Date TBA',
        'image' => 'Ichibansho Frieren Art Scale Bust.webp'
    ],

    [
        'name' => 'Fern – Yumemirize Nap Ver.',
        'series' => "Frieren: Beyond Journey's End",
        'category' => 'Prize Figure',
        'price' => 1450,
        'deposit' => 450,
        'release' => 'Release Date TBA',
        'image' => 'Yumemirize Fern (Nap Ver.) Figure.webp'
    ],

    [
        'name' => 'Maomao – Moon Fairy Figure',
        'series' => 'The Apothecary Diaries',
        'category' => 'Scale Figure',
        'price' => 2800,
        'deposit' => 850,
        'release' => 'Release Date TBA',
        'image' => 'Maomao (Moon Fairy) Figure.webp'
    ],

    [
        'name' => 'Satoru Gojo – 1/8 Scale Figure',
        'series' => 'Jujutsu Kaisen',
        'category' => 'Scale Figure',
        'price' => 8500,
        'deposit' => 2550,
        'release' => 'Release Date TBA',
        'image' => 'Gojo Satoru 1over8 Scale Figure.webp'
    ],

    [
        'name' => 'Choso – S.H.Figuarts',
        'series' => 'Jujutsu Kaisen',
        'category' => 'Action Figure',
        'price' => 3200,
        'deposit' => 950,
        'release' => 'Release Date TBA',
        'image' => 'S.H.Figuarts Choso Action Figure.webp'
    ],

    [
        'name' => 'Mahito – S.H.Figuarts',
        'series' => 'Jujutsu Kaisen',
        'category' => 'Action Figure',
        'price' => 3100,
        'deposit' => 950,
        'release' => 'Release Date TBA',
        'image' => 'S.H.Figuarts Mahito Action Figure.webp'
    ],

    [
        'name' => 'Toji Fushiguro – S.H.Figuarts',
        'series' => 'Jujutsu Kaisen',
        'category' => 'Action Figure',
        'price' => 3400,
        'deposit' => 1000,
        'release' => 'Release Date TBA',
        'image' => 'S.H.Figuarts Toji Fushiguro Action Figure.webp'
    ],

    [
        'name' => 'Zenitsu – Deluxe 1/4 Scale Limited Edition Statue',
        'series' => 'Demon Slayer',
        'category' => 'Statue',
        'price' => 12500,
        'deposit' => 3750,
        'release' => 'Release Date TBA',
        'image' => 'Zenitsu Deluxe 1over4 Scale Limited Edition Statue.jpg'
    ],

    [
        'name' => 'Monkey D. Luffy – Gear 5 Ver. III',
        'series' => 'One Piece',
        'category' => 'Prize Figure',
        'price' => 2300,
        'deposit' => 700,
        'release' => 'Release Date TBA',
        'image' => 'One Piece Grandista Monkey D. Luffy (Gear 5 Ver. III) Figure.webp'
    ],

    [
        'name' => 'Makima – FNEX 1/7 Scale Figure',
        'series' => 'Chainsaw Man',
        'category' => 'Scale Figure',
        'price' => 11500,
        'deposit' => 3450,
        'release' => 'Release Date TBA',
        'image' => 'Chainsaw Man FNex Makima 1over7 Scale Figure.webp'
    ],

    [
        'name' => 'Albedo – 1/7 Scale Figure',
        'series' => 'Overlord',
        'category' => 'Scale Figure',
        'price' => 9500,
        'deposit' => 2850,
        'release' => 'Release Date TBA',
        'image' => 'Overlord Albedo 1over7 Scale Figure.webp'
    ],

];

?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Pre-Orders | Mimic Haven Collectibles</title>

    <meta
        name="description"
        content="Reserve upcoming anime figures and collectibles through Mimic Haven pre-orders."
    >

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

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
     SAME HEADER AS HOMEPAGE
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

            <a
                class="active"
                href="preorder.php"
            >
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
                href="#preorder-products"
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



<main>


<!-- =========================================================
     HERO
========================================================= -->

<section class="preorder-hero">

    <div class="preorder-hero-inner">

        <div class="preorder-hero-copy">

            <span class="preorder-eyebrow">
                RESERVE WHAT COMES NEXT
            </span>

            <h1>
                Pre-Order
            </h1>

            <p class="preorder-script">
                Upcoming Figures
            </p>

            <p class="preorder-hero-description">
                Don't miss out on the figures you love.
                Reserve your piece before it arrives and secure
                your place in the next release.
            </p>

            <div class="preorder-hero-buttons">

                <a
                    class="btn primary"
                    href="#preorder-products"
                >
                    Browse Pre-Orders

                    <?php if (icon('right-arrow')): ?>

                        <img
                            src="<?= icon('right-arrow') ?>"
                            alt=""
                        >

                    <?php endif; ?>

                </a>

                <a
                    class="btn outline"
                    href="#how-it-works"
                >
                    How It Works
                </a>

            </div>

        </div>


        <div class="preorder-hero-art">

            <img
                src="<?= asset('frieren maximatic v2') ?>"
                alt="Frieren pre-order figure"
            >

        </div>

    </div>

</section>



<!-- =========================================================
     PRE-ORDER PRODUCT SECTION
========================================================= -->

<section
    class="preorder-products-section"
    id="preorder-products"
>

    <div class="preorder-section-heading">

        <span>
            AVAILABLE NOW
        </span>

        <h2>
            CURRENT
            <strong>PRE-ORDERS</strong>
        </h2>

        <p>
            Figures currently available for reservation.
        </p>

    </div>



    <div class="preorder-products-grid">

        <?php foreach ($preorders as $product): ?>

            <article class="preorder-product-card">


                <div class="preorder-product-image">

                    <img
                        src="assets/collections/<?= rawurlencode($product['image']) ?>"
                        alt="<?= e($product['name']) ?>"
                        loading="lazy"
                    >

                    <span class="preorder-badge">
                        PRE-ORDER
                    </span>


                    <button
                        type="button"
                        class="preorder-favorite"
                        aria-label="Add to favorites"
                    >
                        ♡
                    </button>

                </div>


                <div class="preorder-product-info">

                    <span class="preorder-product-series">
                        <?= e($product['series']) ?>
                    </span>

                    <h3>
                        <?= e($product['name']) ?>
                    </h3>

                    <span class="preorder-product-category">
                        <?= e($product['category']) ?>
                    </span>


                    <div class="preorder-price-row">

                        <strong>
                            <?= peso($product['price']) ?>
                        </strong>

                    </div>


                    <div class="preorder-meta">

                        <div>
                            <span>
                                Deposit
                            </span>

                            <strong>
                                <?= peso($product['deposit']) ?>
                            </strong>
                        </div>

                        <div>
                            <span>
                                Release
                            </span>

                            <strong>
                                <?= e($product['release']) ?>
                            </strong>
                        </div>

                    </div>


                    <div class="preorder-card-footer">

                        <span class="preorder-condition">
                            MISB
                        </span>

                        <span class="preorder-status">
                            Reservation Open
                        </span>

                    </div>

                </div>

            </article>

        <?php endforeach; ?>

    </div>


    <div class="preorder-view-all">

        <a
            class="btn outline"
            href="collection.php"
        >
            View All Figures
            →
        </a>

    </div>

</section>



<!-- =========================================================
     HOW IT WORKS
========================================================= -->

<section
    class="how-it-works"
    id="how-it-works"
>

    <div class="preorder-section-heading">

        <span>
            SIMPLE &amp; SECURE
        </span>

        <h2>
            PRE-ORDER
            <strong>PROCESS</strong>
        </h2>

        <p>
            Simple steps to secure your figure.
        </p>

    </div>


    <div class="preorder-steps">


        <div class="preorder-step">

            <div class="preorder-step-number">
                01
            </div>

            <div class="preorder-step-icon">
                +
            </div>

            <h3>
                Choose Your Figure
            </h3>

            <p>
                Browse our upcoming releases
                and select the figure you want.
            </p>

        </div>


        <div class="preorder-step-line"></div>


        <div class="preorder-step">

            <div class="preorder-step-number">
                02
            </div>

            <div class="preorder-step-icon">
                ✓
            </div>

            <h3>
                Reserve Your Slot
            </h3>

            <p>
                Confirm your pre-order and
                provide your order details.
            </p>

        </div>


        <div class="preorder-step-line"></div>


        <div class="preorder-step">

            <div class="preorder-step-number">
                03
            </div>

            <div class="preorder-step-icon">
                ₱
            </div>

            <h3>
                Pay Your Deposit
            </h3>

            <p>
                Pay the required deposit to
                secure your reservation.
            </p>

        </div>


        <div class="preorder-step-line"></div>


        <div class="preorder-step">

            <div class="preorder-step-number">
                04
            </div>

            <div class="preorder-step-icon">
                ◷
            </div>

            <h3>
                Wait for Release
            </h3>

            <p>
                We'll keep you updated when
                the figure becomes available.
            </p>

        </div>


        <div class="preorder-step-line"></div>


        <div class="preorder-step">

            <div class="preorder-step-number">
                05
            </div>

            <div class="preorder-step-icon">
                ₱
            </div>

            <h3>
                Pay Remaining Balance
            </h3>

            <p>
                Once your figure arrives,
                complete the remaining balance.
            </p>

        </div>


        <div class="preorder-step-line"></div>


        <div class="preorder-step">

            <div class="preorder-step-number">
                06
            </div>

            <div class="preorder-step-icon">
                □
            </div>

            <h3>
                Receive Your Figure
            </h3>

            <p>
                Your figure will be carefully
                packed and shipped to you.
            </p>

        </div>

    </div>

</section>



<!-- =========================================================
     IMPORTANT NOTICE
========================================================= -->

<section class="preorder-notice-section">

    <div class="preorder-section-heading">

        <span>
            PLEASE READ
        </span>

        <h2>
            IMPORTANT
            <strong>NOTICE</strong>
        </h2>

    </div>


    <div class="preorder-notice-grid">


        <div class="preorder-notice-card">

            <div class="preorder-notice-icon">
                ◈
            </div>

            <h3>
                Deposits
            </h3>

            <p>
                Deposits reserve your slot and are
                subject to the applicable pre-order terms.
            </p>

        </div>


        <div class="preorder-notice-card">

            <div class="preorder-notice-icon">
                ◷
            </div>

            <h3>
                Release Dates May Vary
            </h3>

            <p>
                Manufacturer and supplier release dates
                may change without prior notice.
            </p>

        </div>


        <div class="preorder-notice-card">

            <div class="preorder-notice-icon">
                ×
            </div>

            <h3>
                Cancellation Policy
            </h3>

            <p>
                Cancellation requests are subject to
                the terms agreed upon when ordering.
            </p>

        </div>


        <div class="preorder-notice-card">

            <div class="preorder-notice-icon">
                ₱
            </div>

            <h3>
                Balance Payment
            </h3>

            <p>
                The remaining balance must be settled
                within the stated payment deadline.
            </p>

        </div>

    </div>


    <p class="preorder-terms-line">

        By placing a pre-order, you agree to our

        <a href="index.php#terms">
            Terms &amp; Conditions
        </a>.

    </p>

</section>

</main>



<!-- =========================================================
     SAME FOOTER AS HOMEPAGE
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

            <a
                class="active"
                href="preorder.php"
            >
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

</body>

</html>