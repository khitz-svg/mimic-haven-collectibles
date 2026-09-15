<?php

require_once 'auth.php';

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
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
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
        Terms & Conditions | Mimic Haven Collectibles
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

    <style>

        .terms-page {
            max-width: 1000px;
            margin: 0 auto;
            padding: 80px 24px 100px;
        }

        .terms-page-heading {
            text-align: center;
            margin-bottom: 55px;
        }

        .terms-page-heading span {
            display: block;
            margin-bottom: 12px;
            color: var(--blue);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
        }

        .terms-page-heading h1 {
            margin: 0;
            font-family: Montserrat, sans-serif;
            font-size: 48px;
            font-weight: 800;
        }

        .terms-page-heading p {
            max-width: 680px;
            margin: 18px auto 0;
            color: #9aa3ad;
            line-height: 1.7;
        }

        .terms-content {
            display: grid;
            gap: 24px;
        }

        .terms-card {
            padding: 30px;
            background: #101318;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
        }

        .terms-card h2 {
            margin: 0 0 14px;
            font-family: Montserrat, sans-serif;
            font-size: 20px;
        }

        .terms-card p,
        .terms-card li {
            color: #aeb6bf;
            font-family: Inter, sans-serif;
            font-size: 14px;
            line-height: 1.8;
        }

        .terms-card ul {
            margin: 10px 0 0;
            padding-left: 20px;
        }

        .terms-back {
            display: inline-block;
            margin-top: 45px;
            color: var(--blue);
            text-decoration: none;
            font-family: Montserrat, sans-serif;
            font-size: 13px;
            font-weight: 700;
        }

        .terms-back:hover {
            text-decoration: underline;
        }

    </style>

</head>

<body>

<header class="site-header">

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

            <a href="about.php">
                About
            </a>

            <a href="contact.php">
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


<main>

    <section class="terms-page">

        <div class="terms-page-heading">

            <span>
                MIMIC HAVEN COLLECTIBLES
            </span>

            <h1>
                Terms &amp; Conditions
            </h1>

            <p>
                Please review these terms before placing an order
                or making a pre-order reservation.
            </p>

        </div>


        <div class="terms-content">

            <div class="terms-card">

                <h2>
                    1. General Terms
                </h2>

                <p>
                    By using Mimic Haven Collectibles, you agree
                    to follow these Terms &amp; Conditions and all
                    applicable ordering procedures.
                </p>

            </div>


            <div class="terms-card">

                <h2>
                    2. Product Information
                </h2>

                <p>
                    Product condition, availability, description,
                    and other details are provided on each product
                    listing. Customers are encouraged to review
                    all product information before placing an order.
                </p>

            </div>


            <div class="terms-card">

                <h2>
                    3. Pre-Orders
                </h2>

                <p>
                    Pre-orders are reservations for upcoming figures.
                    Customers may be required to provide a deposit
                    to secure their reservation.
                </p>

                <p>
                    Supplier and manufacturer release dates may
                    change without prior notice.
                </p>

            </div>


            <div class="terms-card">

                <h2>
                    4. Deposits and Balance Payments
                </h2>

                <p>
                    Deposits are applied to the customer's pre-order.
                    Any remaining balance must be settled within the
                    stated payment deadline.
                </p>

            </div>


            <div class="terms-card">

                <h2>
                    5. Cancellation
                </h2>

                <p>
                    Cancellation requests are subject to the applicable
                    terms of the order and pre-order reservation.
                </p>

            </div>


            <div class="terms-card">

                <h2>
                    6. Orders
                </h2>

                <p>
                    Customers are responsible for providing accurate
                    contact and delivery information when placing an
                    order.
                </p>

            </div>


            <div class="terms-card">

                <h2>
                    7. Authenticity and Condition
                </h2>

                <p>
                    Mimic Haven Collectibles is committed to providing
                    authentic collectible figures. Product condition
                    information is indicated on the corresponding
                    product listing.
                </p>

            </div>


            <div class="terms-card">

                <h2>
                    8. Changes to These Terms
                </h2>

                <p>
                    Mimic Haven Collectibles may update these Terms
                    &amp; Conditions when necessary to reflect changes
                    in services, policies, or ordering procedures.
                </p>

            </div>

        </div>


        <a
            class="terms-back"
            href="index.php"
        >
            ← Back to Home
        </a>

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
                experience inspired by the journey behind every
                masterpiece.
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