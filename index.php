<?php

require_once 'auth.php';
require_once 'db.php';

/* =========================================================
   HELPER FUNCTIONS
========================================================= */

function asset(string $name): string
{
    foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {

        $file = __DIR__ . "/assets/{$name}.{$ext}";

        if (file_exists($file)) {
            return "assets/{$name}.{$ext}";
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

/* =========================================================
   FEATURED COLLECTIONS
========================================================= */

$collectionSeries = [

    "Frieren: Beyond Journey's End" => [
        'name' => 'Frieren Collection',
        'series' => "Frieren: Beyond Journey's End",
        'image' => 'frieren'
    ],

    'The Apothecary Diaries' => [
        'name' => 'Mao Collection',
        'series' => 'The Apothecary Diaries',
        'image' => 'mao'
    ],

    '86 - EIGHTY SIX' => [
        'name' => 'Lena Collection',
        'series' => '86 - EIGHTY SIX',
        'image' => 'lena'
    ],

    'Violet Evergarden' => [
        'name' => 'Violet Collection',
        'series' => 'Violet Evergarden',
        'image' => 'violet'
    ]
];

/* =========================================================
   GET STOCK COUNTS FROM DATABASE
========================================================= */

$collectionCounts = [];

foreach ($collectionSeries as $series => $collection) {

    $collectionCounts[$series] = 0;

    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(stock), 0) AS total_stock
         FROM products
         WHERE series = ?
           AND availability = 'In Stock'
           AND stock > 0"
    );

    if ($stmt) {

        $stmt->bind_param(
            "s",
            $series
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            $collectionCounts[$series] =
                (int)$row['total_stock'];
        }

        $stmt->close();
    }
}

/* =========================================================
   BUILD COLLECTION CARDS
========================================================= */

$collections = [];

foreach ($collectionSeries as $series => $collection) {

    $collections[] = [

        'name' =>
            $collection['name'],

        'series' =>
            $collection['series'],

        'items' =>
            ($collectionCounts[$series] ?? 0)
            . ' ITEMS IN STOCK',

        'image' =>
            $collection['image']
    ];
}

/* =========================================================
   MORE COLLECTIONS
========================================================= */

$collections[] = [

    'name' => 'More Collections',

    'series' => 'Explore more series',

    'items' => 'SEE ALL',

    'image' => 'more-collections'
];

/* =========================================================
   CUSTOMER FEEDBACK
   ONLY APPROVED REVIEWS ARE DISPLAYED
========================================================= */

$reviews = [];

$reviewStmt = $conn->prepare(
    "SELECT
        r.rating,
        r.review_text,
        u.first_name,
        u.last_name,
        r.created_at
     FROM reviews r
     INNER JOIN users u
        ON u.id = r.user_id
     WHERE TRIM(r.status) = 'Approved'
     ORDER BY r.created_at DESC
     LIMIT 3"
);

if ($reviewStmt) {

    $reviewStmt->execute();

    $reviewResult =
        $reviewStmt->get_result();

    while ($row = $reviewResult->fetch_assoc()) {

        $reviews[] = [

            'rating' =>
                (int)$row['rating'],

            'text' =>
                $row['review_text'],

            'name' =>
                trim(
                    $row['first_name']
                    . ' '
                    . $row['last_name']
                )
        ];
    }

    $reviewStmt->close();
}

/* =========================================================
   CONDITION GUIDE
========================================================= */

$conditions = [

    [
        'code' => 'MISB',

        'title' =>
            'Mint in Sealed Box',

        'desc' =>
            'Brand new and factory sealed in its original packaging.',

        'state' =>
            'NEW',

        'image' =>
            'misb'
    ],

    [
        'code' => 'LOOSE',

        'title' =>
            'No Original Packaging',

        'desc' =>
            'Figure is sold without its original box or packaging.',

        'state' =>
            'GOOD',

        'image' =>
            'loose'
    ],

    [
        'code' => 'MIB',

        'title' =>
            'Mint in Box',

        'desc' =>
            'Opened, but the figure remains in excellent condition with its original box.',

        'state' =>
            'EXCELLENT',

        'image' =>
            'mib'
    ],

    [
        'code' => 'BIB',

        'title' =>
            'Box Opened',

        'desc' =>
            'Opened or displayed, but the figure remains complete and well maintained.',

        'state' =>
            'VERY GOOD',

        'image' =>
            'bib'
    ]
];

/* =========================================================
   FAQ
========================================================= */

$faqs = [

    [
        'q' =>
            'How can I become eligible as a returning customer and what requirements do I need to meet?',

        'a' =>
            'Returning-customer benefits are based on completed purchases and account history. Contact us for your eligibility status.'
    ],

    [
        'q' =>
            'I missed the pre-order deadline for an item I really want. Can I still place a pre-order for it?',

        'a' =>
            'Possibly. Availability depends on supplier allocations and whether extra slots remain. Contact us with the item name.'
    ],

    [
        'q' =>
            'I made a mistake with my order details after checking out. Can I still change my delivery address or pickup method?',

        'a' =>
            'Contact us as soon as possible. Changes depend on whether the order has already been processed or shipped.'
    ],

    [
        'q' =>
            'My pre-ordered item has not arrived even though the estimated arrival date has already passed. What should I do?',

        'a' =>
            'Estimated dates can move. Send us your order details and we will check the latest supplier or shipping update.'
    ],

    [
        'q' =>
            'What happens to my deposit if my pre-ordered item is cancelled by the supplier or manufacturer?',

        'a' =>
            'Deposit handling depends on the reason and terms of the cancellation. We will communicate the available refund or replacement options.'
    ]
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

    <title>
        Mimic Haven Collectibles
    </title>

    <meta
        name="description"
        content="Authentic anime figures, trusted pre-orders, and collector-grade treasures."
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
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Parisienne&display=swap"
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


            <a
                class="active"
                href="index.php"
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



<main>


<!-- =========================================================
     HERO
========================================================= -->

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



<!-- =========================================================
     TRUST STRIP
========================================================= -->

<section class="trust">


    <div class="trust-item">

        <?php if (icon('quality')): ?>

            <img
                src="<?= icon('quality') ?>"
                alt=""
            >

        <?php endif; ?>


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

        <?php if (icon('pre-order')): ?>

            <img
                src="<?= icon('pre-order') ?>"
                alt=""
            >

        <?php endif; ?>


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

        <?php if (icon('target')): ?>

            <img
                src="<?= icon('target') ?>"
                alt=""
            >

        <?php endif; ?>


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

        <?php if (icon('present')): ?>

            <img
                src="<?= icon('present') ?>"
                alt=""
            >

        <?php endif; ?>


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



<!-- =========================================================
     FEATURED COLLECTIONS
========================================================= -->

<section
    class="content-section"
    id="collection"
>

    <div class="section-heading">

        <h2>

            FEATURED

            <span>
                COLLECTIONS
            </span>

        </h2>

    </div>


    <div class="collection-grid">


        <?php foreach ($collections as $collection): ?>


            <?php

            $isMore =
                $collection['items'] === 'SEE ALL';


            $link =
                $isMore

                    ? 'collection.php'

                    : 'collection.php?series=' .
                      urlencode(
                          $collection['series']
                      );

            ?>


            <a
                class="collection-card"
                href="<?= e($link) ?>"
            >

                <img
                    src="<?= asset($collection['image']) ?>"
                    alt="<?= e($collection['name']) ?>"
                >


                <div class="collection-info">

                    <h3>
                        <?= e($collection['name']) ?>
                    </h3>


                    <p>
                        <?= e($collection['series']) ?>
                    </p>


                    <span>

                        <?= e($collection['items']) ?>


                        <?php if ($isMore): ?>

                            →

                        <?php endif; ?>

                    </span>

                </div>

            </a>


        <?php endforeach; ?>


    </div>

</section>



<!-- =========================================================
     CUSTOMER FEEDBACK
========================================================= -->

<section
    class="content-section"
    id="about"
>

    <div class="section-heading">

        <h2>

            WHAT OUR

            <span>
                CUSTOMERS SAY
            </span>

        </h2>

    </div>


    <div class="review-list">


        <?php if (empty($reviews)): ?>


            <article class="review">


                <div class="quote">

                    <?php if (icon('quote')): ?>

                        <img
                            src="<?= icon('quote') ?>"
                            alt=""
                        >

                    <?php endif; ?>

                </div>


                <div class="review-content">

                    <p>
                        Customer feedback will appear here after
                        an approved customer review is submitted.
                    </p>

                </div>


            </article>


        <?php else: ?>


            <?php foreach ($reviews as $i => $review): ?>


                <?php

                $initials = '';

                $nameParts = preg_split(
                    '/\s+/',
                    trim($review['name'])
                );


                if (!empty($nameParts[0])) {

                    $initials .= strtoupper(
                        substr(
                            $nameParts[0],
                            0,
                            1
                        )
                    );

                }


                if (count($nameParts) > 1) {

                    $initials .= strtoupper(
                        substr(
                            $nameParts[
                                count($nameParts) - 1
                            ],
                            0,
                            1
                        )
                    );

                }

                ?>


                <article
                    class="review <?= $i % 2 === 1 ? 'right' : '' ?>"
                >


                    <div class="quote">

                        <?php if (icon('quote')): ?>

                            <img
                                src="<?= icon('quote') ?>"
                                alt=""
                            >

                        <?php endif; ?>

                    </div>


                    <div class="review-content">


                        <!-- CUSTOMER RATING -->

                        <div class="rating">


                            <div
                                class="customer-rating-stars"
                                style="--rating: <?= (int)$review['rating'] ?>;"
                                aria-label="<?= (int)$review['rating'] ?> out of 5 stars"
                            >

                                <span
                                    class="stars-empty"
                                ></span>


                                <span
                                    class="stars-filled"
                                ></span>

                            </div>


                            <span>

                                <?= number_format(
                                    (int)$review['rating'],
                                    1
                                ) ?>/5

                            </span>

                        </div>


                        <!-- CUSTOMER REVIEW -->

                        <p>

                            <?= e($review['text']) ?>

                        </p>


                        <!-- CUSTOMER NAME -->

                        <div class="reviewer">


                            <div
                                class="reviewer-placeholder"
                                aria-hidden="true"
                            >

                                <?= e($initials) ?>

                            </div>


                            <span>

                                <b>

                                    <?= e($review['name']) ?>

                                </b>


                                <small>

                                    Verified Buyer

                                </small>

                            </span>


                        </div>

                    </div>


                </article>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>

</section>



<!-- =========================================================
     CONDITION GUIDE
========================================================= -->

<section
    class="content-section"
    id="condition"
>

    <div class="section-heading">

        <h2>

            CONDITION

            <span>
                GUIDE
            </span>

        </h2>

    </div>


    <div class="condition-grid">


        <?php foreach ($conditions as $condition): ?>


            <article class="condition-card">


                <img
                    src="<?= asset($condition['image']) ?>"
                    alt="<?= e($condition['code']) ?> condition example"
                >


                <div>

                    <h3>
                        <?= e($condition['code']) ?>
                    </h3>


                    <b>
                        <?= e($condition['title']) ?>
                    </b>


                    <p>
                        <?= e($condition['desc']) ?>
                    </p>


                    <strong>

                        CONDITION:

                        <em>
                            <?= e($condition['state']) ?>
                        </em>

                    </strong>

                </div>


            </article>


        <?php endforeach; ?>


    </div>


    <p class="note">

        Condition details are provided on each product listing.
        Please review the product description before placing
        your order.

    </p>

</section>



<!-- =========================================================
     FAQ
========================================================= -->

<section
    class="content-section faq-section"
    id="faq"
>

    <div class="section-heading centered">

        <h2>

            FREQUENTLY ASKED

            <span>
                QUESTIONS
            </span>

        </h2>

    </div>


    <div class="faq-list">


        <?php foreach ($faqs as $faq): ?>


            <details>


                <summary>


                    <span>

                        <?= e($faq['q']) ?>

                    </span>


                    <?php if (icon('chevron')): ?>

                        <img
                            src="<?= icon('chevron') ?>"
                            alt=""
                        >

                    <?php else: ?>

                        <span>
                            +
                        </span>

                    <?php endif; ?>


                </summary>


                <p>

                    <?= e($faq['a']) ?>

                </p>


            </details>


        <?php endforeach; ?>


    </div>

</section>



<!-- =========================================================
     TERMS NOTICE
========================================================= -->

<section
    class="preorder-note"
    id="preorder-info"
>

    <p>

        Please review our

        <a href="terms.php">
            Terms &amp; Conditions
        </a>

        before placing an order.

    </p>


    <a
        class="link-arrow"
        href="terms.php"
    >

        View Terms &amp; Conditions →

    </a>

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
                experience inspired by the journey behind every
                masterpiece.

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



<script src="script.js"></script>



<!-- =========================================================
     HEADER SEARCH JAVASCRIPT
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

                if (event.key !== 'Enter') {
                    return;
                }


                const search =
                    headerSearchInput.value.trim();


                if (!search) {
                    return;
                }


                window.location.href =
                    'collection.php?search=' +
                    encodeURIComponent(search);

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

                if (event.key === 'Escape') {

                    headerSearch.classList.remove(
                        'active'
                    );


                    headerSearchToggle.setAttribute(
                        'aria-expanded',
                        'false'
                    );


                    headerSearchToggle.focus();

                }

            }
        );

    }
);

</script>


</body>

</html>