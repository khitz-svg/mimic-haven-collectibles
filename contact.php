<?php

/* =========================================================
   MIMIC HAVEN - CONTACT PAGE
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
        Contact | Mimic Haven Collectibles
    </title>

    <meta
        name="description"
        content="Contact Mimic Haven Collectibles for questions, product inquiries, and pre-order assistance."
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

            <a href="about.php">
                About
            </a>

            <a
                href="contact.php"
                class="active"
            >
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
     CONTACT PAGE
========================================================= -->

<main class="contact-page">


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="contact-hero">

        <div class="contact-hero-inner">

            <span>
                WE'RE HERE TO HELP
            </span>

            <h1>
                Let's Talk,
                <strong>Collectors.</strong>
            </h1>

            <p>
                Have a question about a figure, a pre-order, or your
                collecting journey? Reach out to Mimic Haven Collectibles.
            </p>

        </div>

    </section>



    <!-- =====================================================
         CONTACT CONTENT
    ====================================================== -->

    <section class="contact-content">

        <div class="contact-grid">


            <!-- =============================================
                 CONTACT INFORMATION
            ============================================== -->

            <div class="contact-info">

                <span class="contact-label">
                    GET IN TOUCH
                </span>

                <h2>
                    We're only a
                    <strong>message away.</strong>
                </h2>

                <p>
                    Whether you need help choosing a figure, checking
                    a pre-order, or simply want to ask us something,
                    we're happy to hear from you.
                </p>


                <div class="contact-details">


                    <div class="contact-detail">

                        <div class="contact-detail-icon">
                            @
                        </div>

                        <div>

                            <span>
                                EMAIL
                            </span>

                            <a href="mailto:mimichvn.collectibles@gmail.com">
                                mimichvn.collectibles@gmail.com
                            </a>

                        </div>

                    </div>


                    <div class="contact-detail">

                        <div class="contact-detail-icon">
                            ☎
                        </div>

                        <div>

                            <span>
                                PHONE
                            </span>

                            <a href="tel:+639657457775">
                                +63 965 745 7775
                            </a>

                        </div>

                    </div>


                    <div class="contact-detail">

                        <div class="contact-detail-icon">
                            ◷
                        </div>

                        <div>

                            <span>
                                RESPONSE TIME
                            </span>

                            <p>
                                We aim to respond to inquiries as soon as possible.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="contact-social">

                    <span>
                        FOLLOW MIMIC HAVEN
                    </span>

                    <div class="contact-social-links">

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



            <!-- =============================================
                 CONTACT FORM
            ============================================== -->

            <div class="contact-form-card">

                <div class="contact-form-header">

                    <span>
                        SEND US A MESSAGE
                    </span>

                    <h2>
                        How can we
                        <strong>help?</strong>
                    </h2>

                </div>


                <form
                    class="contact-form"
                    id="contactForm"
                >


                    <div class="contact-form-row">


                        <div class="contact-field">

                            <label for="name">
                                Name
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                placeholder="Your name"
                                required
                            >

                        </div>


                        <div class="contact-field">

                            <label for="email">
                                Email
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="your@email.com"
                                required
                            >

                        </div>

                    </div>


                    <div class="contact-field">

                        <label for="subject">
                            Subject
                        </label>

                        <select
                            id="subject"
                            name="subject"
                            required
                        >

                            <option
                                value=""
                                selected
                                disabled
                            >
                                Select a topic
                            </option>

                            <option value="product">
                                Product Inquiry
                            </option>

                            <option value="preorder">
                                Pre-Order
                            </option>

                            <option value="order">
                                Existing Order
                            </option>

                            <option value="general">
                                General Question
                            </option>

                            <option value="other">
                                Other
                            </option>

                        </select>

                    </div>


                    <div class="contact-field">

                        <label for="message">
                            Message
                        </label>

                        <textarea
                            id="message"
                            name="message"
                            rows="6"
                            placeholder="Tell us how we can help..."
                            required
                        ></textarea>

                    </div>


                    <button
                        type="submit"
                        class="contact-submit"
                    >
                        Send Message →
                    </button>


                    <p class="contact-form-note">
                        This form is currently for front-end demonstration.
                    </p>

                </form>

            </div>

        </div>

    </section>



    <!-- =====================================================
         SUPPORT SECTION
    ====================================================== -->

    <section class="contact-support">

        <div class="contact-support-inner">

            <span>
                COLLECTOR SUPPORT
            </span>

            <h2>
                Looking for something
                <strong>specific?</strong>
            </h2>

            <p>
                Explore our collection or check upcoming releases
                through our pre-order selection.
            </p>


            <div class="contact-support-buttons">

                <a
                    href="collection.php"
                    class="btn primary"
                >
                    Browse Collection →
                </a>

                <a
                    href="preorder.php"
                    class="contact-outline-button"
                >
                    View Pre-Orders
                </a>

            </div>

        </div>

    </section>

</main>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

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


<script>

/* =========================================================
   CONTACT FORM
========================================================= */

const contactForm =
    document.getElementById('contactForm');

contactForm?.addEventListener('submit', event => {

    event.preventDefault();

    const name =
        document.getElementById('name')?.value.trim();

    if (typeof showToast === 'function') {

        showToast(
            `Thanks${name ? `, ${name}` : ''}! Your message has been received.`
        );

    }

    contactForm.reset();

});

</script>

</body>
</html>