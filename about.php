<?php

/* =========================================================
   MIMIC HAVEN - ABOUT PAGE
========================================================= */

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
        About | Mimic Haven Collectibles
    </title>

    <meta
        name="description"
        content="Learn more about Mimic Haven Collectibles, a sanctuary for anime collectors."
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


<!-- =========================================================
     HEADER
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

                    <span>
                        COLLECTIBLES
                    </span>

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

            <a
                href="about.php"
                class="active"
            >
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
     ABOUT PAGE
========================================================= -->

<main class="about-page">


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="about-hero">

        <div class="about-hero-content">

            <span class="about-eyebrow">
                THE STORY BEHIND THE COLLECTION
            </span>

            <h1>
                More Than
                <strong>Figures.</strong>
            </h1>

            <p>
                Mimic Haven Collectibles is a sanctuary created for
                collectors who see more than a figure on a shelf.
                Every piece represents a character, a story, and a
                moment worth remembering.
            </p>

            <div class="about-hero-actions">

                <a
                    href="collection.php"
                    class="btn primary"
                >
                    Explore Collection →
                </a>

                <a
                    href="preorder.php"
                    class="about-text-link"
                >
                    View Pre-Orders
                </a>

            </div>

        </div>

    </section>



    <!-- =====================================================
         OUR STORY
    ====================================================== -->

    <section class="about-story">

        <div class="about-section-label">
            OUR STORY
        </div>

        <div class="about-story-grid">

            <div class="about-story-heading">

                <h2>
                    A Haven
                    <strong>for Collectors.</strong>
                </h2>

            </div>


            <div class="about-story-text">

                <p>
                    Mimic Haven Collectibles was created from a simple
                    appreciation for anime and the memories attached to
                    the characters we grow to love.
                </p>

                <p>
                    What begins as a favorite character can become
                    something much more meaningful. A figure can remind
                    us of a story, a journey, a friendship, or a moment
                    that stayed with us long after the series ended.
                </p>

                <p>
                    Our goal is to make collecting feel just as
                    meaningful. From authentic figures to carefully
                    handled pre-orders, Mimic Haven focuses on giving
                    collectors a reliable place to discover and grow
                    their collection.
                </p>

            </div>

        </div>

    </section>



    <!-- =====================================================
         MISSION
    ====================================================== -->

    <section class="about-mission">

        <div class="about-mission-inner">

            <span>
                OUR MISSION
            </span>

            <h2>
                Making Every
                <strong>Collection Meaningful.</strong>
            </h2>

            <p>
                We aim to provide collectors with authentic anime
                figures, transparent product information, reliable
                pre-orders, and a shopping experience built around
                trust.
            </p>

        </div>

    </section>



    <!-- =====================================================
         WHY MIMIC HAVEN
    ====================================================== -->

    <section class="about-values">

        <div class="about-values-header">

            <div>

                <span>
                    WHY MIMIC HAVEN
                </span>

                <h2>
                    Built Around
                    <strong>Collectors.</strong>
                </h2>

            </div>

            <p>
                Every part of Mimic Haven is designed to make
                collecting easier, clearer, and more enjoyable.
            </p>

        </div>


        <div class="about-values-grid">


            <article class="about-value-card">

                <div class="about-value-number">
                    01
                </div>

                <h3>
                    Authenticity
                </h3>

                <p>
                    We prioritize authentic collectible figures and
                    clear product information so collectors can shop
                    with greater confidence.
                </p>

            </article>


            <article class="about-value-card">

                <div class="about-value-number">
                    02
                </div>

                <h3>
                    Transparency
                </h3>

                <p>
                    Product conditions, pricing, and pre-order details
                    are presented clearly so collectors know what they
                    are purchasing.
                </p>

            </article>


            <article class="about-value-card">

                <div class="about-value-number">
                    03
                </div>

                <h3>
                    Reliability
                </h3>

                <p>
                    From product selection to pre-orders, we aim to
                    provide a dependable experience throughout every
                    step of the collecting journey.
                </p>

            </article>


            <article class="about-value-card">

                <div class="about-value-number">
                    04
                </div>

                <h3>
                    Passion
                </h3>

                <p>
                    Mimic Haven exists because collecting is more than
                    ownership. It is about the stories and characters
                    that make every piece worth keeping.
                </p>

            </article>

        </div>

    </section>



    <!-- =====================================================
         CONDITION GUIDE
    ====================================================== -->

    <section class="about-condition">

        <div class="about-condition-header">

            <span>
                OUR CONDITION STANDARD
            </span>

            <h2>
                Know What
                <strong>You Collect.</strong>
            </h2>

            <p>
                We use a simple condition system to help collectors
                understand the state of each figure before purchasing.
            </p>

        </div>


        <div class="condition-grid">


            <div class="condition-card">

                <strong>
                    MISB
                </strong>

                <h3>
                    Mint in Sealed Box
                </h3>

                <p>
                    Factory sealed and unopened.
                </p>

            </div>


            <div class="condition-card">

                <strong>
                    MIB
                </strong>

                <h3>
                    Mint in Box
                </h3>

                <p>
                    Figure remains with its original packaging.
                </p>

            </div>


            <div class="condition-card">

                <strong>
                    BIB
                </strong>

                <h3>
                    Back in Box
                </h3>

                <p>
                    Previously removed from the box and returned.
                </p>

            </div>


            <div class="condition-card">

                <strong>
                    LOOSE
                </strong>

                <h3>
                    Figure Only
                </h3>

                <p>
                    Figure is sold without its original packaging.
                </p>

            </div>


        </div>

    </section>



    <!-- =====================================================
         FINAL CTA
    ====================================================== -->

    <section class="about-cta">

        <span>
            YOUR NEXT FAVORITE FIGURE IS WAITING
        </span>

        <h2>
            Start Your
            <strong>Collection.</strong>
        </h2>

        <p>
            Discover characters worth remembering and pieces worth
            keeping.
        </p>

        <a
            href="collection.php"
            class="btn primary"
        >
            Browse Figures →
        </a>

    </section>


</main>



<!-- =========================================================
     FOOTER
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

            <a href="about.php">
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