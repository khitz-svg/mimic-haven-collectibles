<?php

require_once 'auth.php';
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


/* =========================================================
   FEATURED COLLECTIONS
   STOCK COMES FROM MYSQL
========================================================= */

$collectionConfig = [
    [
        'name' => 'Frieren Collection',
        'series' => "Frieren: Beyond Journey's End",
        'image' => 'frieren'
    ],

    [
        'name' => 'Mao Collection',
        'series' => 'The Apothecary Diaries',
        'image' => 'mao'
    ],

    [
        'name' => 'Lena Collection',
        'series' => '86 - EIGHTY SIX',
        'image' => 'lena'
    ],

    [
        'name' => 'Violet Collection',
        'series' => 'Violet Evergarden',
        'image' => 'violet'
    ],

    [
        'name' => 'More Collections',
        'series' => 'Explore more series',
        'image' => 'more-collections'
    ]
];


/* =========================================================
   GET TOTAL STOCK PER SERIES
========================================================= */

$stockTotals = [];


$stockStmt = $conn->prepare(
    "SELECT
        series,
        COALESCE(
            SUM(
                CASE
                    WHEN availability = 'In Stock'
                    THEN GREATEST(stock, 0)
                    ELSE 0
                END
            ),
            0
        ) AS total_stock
     FROM products
     GROUP BY series"
);


if ($stockStmt) {

    $stockStmt->execute();

    $stockResult = $stockStmt->get_result();

    while ($row = $stockResult->fetch_assoc()) {

        $stockTotals[$row['series']] =
            max(0, (int)$row['total_stock']);
    }

    $stockStmt->close();
}


/* =========================================================
   BUILD FEATURED COLLECTION DATA
========================================================= */

$collections = [];

foreach ($collectionConfig as $collection) {

    $isMoreCollections =
        $collection['name'] === 'More Collections';

    $collections[] = [
        'name' => $collection['name'],
        'series' => $collection['series'],
        'items' => $isMoreCollections
            ? 'SEE ALL'
            : ($stockTotals[$collection['series']] ?? 0),
        'image' => $collection['image']
    ];
}


/* =========================================================
   REVIEWS
========================================================= */

$reviews = [
    [
        'name' => 'Marcuz Reyes',
        'image' => 'reviewer-1',
        'text' => 'The figure arrived safely and exactly as described. The packaging was excellent, and the whole transaction was smooth!'
    ],

    [
        'name' => 'Yumi Riken',
        'image' => 'reviewer-2',
        'text' => 'I really liked how easy the pre-order process was. I could easily check my order status and know when my figure would arrive.'
    ],

    [
        'name' => 'Chaeseu Kim',
        'image' => 'reviewer-3',
        'text' => 'The figure condition was clearly listed, so I knew exactly what I was buying. The figure also arrived in great condition!'
    ],
];


/* =========================================================
   CONDITION GUIDE
========================================================= */

$conditions = [
    [
        'code' => 'MISB',
        'title' => 'Mint in Sealed Box',
        'desc' => 'Brand new and factory sealed in its original packaging.',
        'state' => 'NEW',
        'image' => 'misb'
    ],

    [
        'code' => 'MIB',
        'title' => 'Mint in Box',
        'desc' => 'Opened, but the figure remains in excellent condition with its original box.',
        'state' => 'EXCELLENT',
        'image' => 'mib'
    ],

    [
        'code' => 'BIB',
        'title' => 'Box Opened',
        'desc' => 'Opened or displayed, but the figure remains complete and well maintained.',
        'state' => 'VERY GOOD',
        'image' => 'bib'
    ],

    [
        'code' => 'LOOSE',
        'title' => 'No Original Packaging',
        'desc' => 'Figure is sold without its original box or packaging.',
        'state' => 'GOOD',
        'image' => 'loose'
    ],
];


/* =========================================================
   FAQ
========================================================= */

$faqs = [
    [
        'q' => 'How can I become eligible as a returning customer and what requirements do I need to meet?',
        'a' => 'Returning-customer benefits are based on completed purchases and account history. Contact us for your eligibility status.'
    ],

    [
        'q' => 'I missed the pre-order deadline for an item I really want. Can I still place a pre-order for it?',
        'a' => 'Possibly. Availability depends on supplier allocations and whether extra slots remain. Contact us with the item name.'
    ],

    [
        'q' => 'I made a mistake with my order details after checking out. Can I still change my delivery address or pickup method?',
        'a' => 'Contact us as soon as possible. Changes depend on whether the order has already been processed or shipped.'
    ],

    [
        'q' => 'My pre-ordered item has not arrived even though the estimated arrival date has already passed. What should I do?',
        'a' => 'Estimated dates can move. Send us your order details and we will check the latest supplier or shipping update.'
    ],

    [
        'q' => 'What happens to my deposit if my pre-ordered item is cancelled by the supplier or manufacturer?',
        'a' => 'Deposit handling depends on the reason and terms of the cancellation. We will communicate the available refund or replacement options.'
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

    <title>Mimic Haven Collectibles</title>

    <meta
        name="description"
        content="Authentic anime figures, trusted pre-orders, and collector-grade treasures."
    >

    <link rel="preconnect" href="https://fonts.googleapis.com">

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


<header
    class="site-header"
    id="home"
>

    <div class="header-inner">

        <a
            class="brand"
            href="#home"
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
            aria-label="Primary navigation"
        >

            <a
                class="active"
                href="#home"
            >
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


            <?php if (isLoggedIn()): ?>

                <a
                    class="nav-user"
                    href="account.php"
                >
                    Hi, <?= htmlspecialchars(currentFirstName()) ?>
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


            <a
                class="nav-icon"
                href="#collection"
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


<section
    class="hero"
    id="preorders"
>

    <div class="hero-inner">

        <div class="hero-copy">

            <span class="eyebrow">
                YOUR COLLECTION STARTS HERE
            </span>

            <h1>
                Preserve.<br>
                Treasure.
            </h1>

            <p class="script">
                Every Figure
            </p>

            <p class="lede">
                A premium destination for authentic anime figures,
                trusted pre-orders, and a collector-grade experience.
            </p>

            <div class="cta-row">

                <a
                    class="btn primary"
                    href="collection.php"
                >
                    Explore Collection

                    <?php if (icon('right-arrow')): ?>

                        <img
                            src="<?= icon('right-arrow') ?>"
                            alt=""
                        >

                    <?php endif; ?>

                </a>


                <a
                    class="btn outline"
                    href="preorder.php"
                >
                    Pre-Order Now
                </a>

            </div>

        </div>


        <div class="hero-art">

            <img
                src="<?= asset('hero-figures') ?>"
                alt="Anime figure collection display"
            >

        </div>

    </div>

</section>


<section class="trust">

    <div class="trust-item">

        <img
            src="<?= icon('quality') ?>"
            alt=""
        >

        <div>

            <strong>
                100% Authentic
            </strong>

            <small>
                Original &amp; official products
            </small>

        </div>

    </div>


    <div class="trust-item">

        <img
            src="<?= icon('secure_preorder') ?>"
            alt=""
        >

        <div>

            <strong>
                Secure Pre-Order
            </strong>

            <small>
                Safe &amp; reliable reservation
            </small>

        </div>

    </div>


    <div class="trust-item">

        <img
            src="<?= icon('target') ?>"
            alt=""
        >

        <div>

            <strong>
                Collector Focused
            </strong>

            <small>
                Made for collectors
            </small>

        </div>

    </div>


    <div class="trust-item">

        <img
            src="<?= icon('present') ?>"
            alt=""
        >

        <div>

            <strong>
                Packed with Care
            </strong>

            <small>
                Secure packaging guaranteed
            </small>

        </div>

    </div>

</section>


<section
    class="content-section"
    id="collection"
>

    <div class="section-heading">

        <h2>
            FEATURED
            <span>COLLECTIONS</span>
        </h2>

    </div>


    <div class="collection-grid">

        <?php foreach ($collections as $collection): ?>

            <?php

            $isMoreCollections =
                $collection['items'] === 'SEE ALL';

            $collectionLink =
                $isMoreCollections
                    ? 'collection.php'
                    : 'collection.php?series=' . urlencode($collection['series']);

            ?>

            <a
                class="collection-card"
                href="<?= htmlspecialchars($collectionLink) ?>"
                aria-label="Open <?= htmlspecialchars($collection['name']) ?>"
            >

                <img
                    src="<?= asset($collection['image']) ?>"
                    alt="<?= htmlspecialchars($collection['name']) ?>"
                >


                <div class="collection-info">

                    <h3>
                        <?= htmlspecialchars($collection['name']) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars($collection['series']) ?>
                    </p>


                    <span>

                        <?php if ($isMoreCollections): ?>

                            SEE ALL →

                        <?php else: ?>

                            <?= (int)$collection['items'] ?>
                            ITEMS IN STOCK

                        <?php endif; ?>

                    </span>

                </div>

            </a>

        <?php endforeach; ?>

    </div>

</section>


<section
    class="content-section"
    id="about"
>

    <div class="section-heading">

        <h2>
            WHAT OUR
            <span>CUSTOMERS SAY</span>
        </h2>

    </div>


    <div class="review-list">

        <?php foreach ($reviews as $i => $review): ?>

            <article
                class="review <?= $i === 1 ? 'right' : '' ?>"
            >

                <div class="quote">

                    <img
                        src="<?= icon('quote') ?>"
                        alt=""
                    >

                </div>


                <div class="review-content">

                    <div class="rating">

                        <img
                            src="<?= icon('stars') ?>"
                            alt="5 stars"
                        >

                        <span>
                            5.0/5
                        </span>

                    </div>


                    <p>
                        <?= htmlspecialchars($review['text']) ?>
                    </p>


                    <div class="reviewer">

                        <img
                            src="<?= asset($review['image']) ?>"
                            alt="<?= htmlspecialchars($review['name']) ?>"
                        >

                        <span>

                            <b>
                                <?= htmlspecialchars($review['name']) ?>
                            </b>

                            <small>
                                Verified Buyer
                            </small>

                        </span>

                    </div>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

</section>


<section
    class="content-section"
    id="condition"
>

    <div class="section-heading">

        <h2>
            CONDITION
            <span>GUIDE</span>
        </h2>

    </div>


    <div class="condition-grid">

        <?php foreach ($conditions as $condition): ?>

            <article class="condition-card">

                <img
                    src="<?= asset($condition['image']) ?>"
                    alt="<?= htmlspecialchars($condition['code']) ?> condition example"
                >


                <div>

                    <h3>
                        <?= htmlspecialchars($condition['code']) ?>
                    </h3>

                    <b>
                        <?= htmlspecialchars($condition['title']) ?>
                    </b>

                    <p>
                        <?= htmlspecialchars($condition['desc']) ?>
                    </p>

                    <strong>
                        CONDITION:
                        <em>
                            <?= htmlspecialchars($condition['state']) ?>
                        </em>
                    </strong>

                </div>

            </article>

        <?php endforeach; ?>

    </div>


    <p class="note">
        Condition details are provided on each product listing.
        Please review the product description before placing your order.
    </p>

</section>


<section
    class="content-section faq-section"
    id="faq"
>

    <div class="section-heading centered">

        <h2>
            FREQUENTLY ASKED
            <span>QUESTIONS</span>
        </h2>

    </div>


    <div class="faq-list">

        <?php foreach ($faqs as $faq): ?>

            <details>

                <summary>

                    <span>
                        <?= htmlspecialchars($faq['q']) ?>
                    </span>


                    <?php if (icon('chevron')): ?>

                        <img
                            src="<?= icon('chevron') ?>"
                            alt=""
                        >

                    <?php endif; ?>

                </summary>


                <p>
                    <?= htmlspecialchars($faq['a']) ?>
                </p>

            </details>

        <?php endforeach; ?>

    </div>

</section>


<section
    class="preorder-note"
    id="preorder-info"
>

    <p>
        Please review our
        <a href="#terms">
            Terms &amp; Conditions
        </a>
        before placing an order.
    </p>

    <a
        class="link-arrow"
        href="#terms"
    >
        View Terms &amp; Conditions →
    </a>

</section>


<section
    class="terms-section content-section"
    id="terms"
>

    <div class="terms-box">

        <h2>
            Terms &amp; Conditions
        </h2>

        <p>
            Condition details are provided on each product listing.
            Please review the product description before placing an order.
        </p>

        <p>
            Pre-order availability and supplier timing may change.
            Specific deposit and cancellation handling depends on the applicable order terms.
        </p>

    </div>

</section>


</main>


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

            <a href="#home">
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