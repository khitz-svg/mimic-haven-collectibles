<?php

require_once 'auth.php';
require_once 'db.php';

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

function e(string $value): string {
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

/* =========================================================
   CONTACT FORM HANDLING
========================================================= */

if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
}

$contactCsrf = $_SESSION['contact_csrf'];
$contactSuccess = '';
$contactError = '';

$contactName = '';
$contactEmail = '';
$contactSubject = '';
$contactMessage = '';

$subjectLabels = [
    'product' => 'Product Inquiry',
    'preorder' => 'Pre-Order',
    'order' => 'Existing Order',
    'general' => 'General Question',
    'other' => 'Other'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (
        !is_string($postedToken) ||
        !hash_equals($contactCsrf, $postedToken)
    ) {
        $contactError = 'Invalid security token. Please refresh the page and try again.';
    } elseif ($action !== 'contact_submit') {
        $contactError = 'Invalid request.';
    } else {
        $contactName = trim((string)($_POST['name'] ?? ''));
        $contactEmail = trim((string)($_POST['email'] ?? ''));
        $contactSubject = trim((string)($_POST['subject'] ?? ''));
        $contactMessage = trim((string)($_POST['message'] ?? ''));

        if ($contactName === '') {
            $contactError = 'Please enter your name.';
        } elseif (mb_strlen($contactName) > 100) {
            $contactError = 'Name is too long.';
        } elseif (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $contactError = 'Please enter a valid email address.';
        } elseif (!isset($subjectLabels[$contactSubject])) {
            $contactError = 'Please select a valid subject.';
        } elseif ($contactMessage === '') {
            $contactError = 'Please enter your message.';
        } elseif (mb_strlen($contactMessage) > 2000) {
            $contactError = 'Message is too long. Please keep it within 2,000 characters.';
        }

        if ($contactError === '') {
            $subjectText = $subjectLabels[$contactSubject];

            $messageStmt = $conn->prepare(
                "INSERT INTO contact_messages (name, email, subject, message)
                 VALUES (?, ?, ?, ?)"
            );

            if (!$messageStmt) {
                $contactError = 'Unable to send your message right now. Please try again.';
            } else {
                $messageStmt->bind_param(
                    'ssss',
                    $contactName,
                    $contactEmail,
                    $subjectText,
                    $contactMessage
                );

                if ($messageStmt->execute()) {
                    $contactSuccess = 'Your message has been sent successfully. We will get back to you as soon as possible.';
                    $contactName = '';
                    $contactEmail = '';
                    $contactSubject = '';
                    $contactMessage = '';
                    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
                    $contactCsrf = $_SESSION['contact_csrf'];
                } else {
                    $contactError = 'Unable to send your message right now. Please try again.';
                }

                $messageStmt->close();
            }
        }
    }
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
.contact-form-message {
    margin: 0 0 20px;
    padding: 13px 16px;
    border-radius: 10px;
    font-size: 13px;
    line-height: 1.5;
}
.contact-form-message.success {
    background: rgba(102, 204, 153, 0.12);
    border: 1px solid rgba(102, 204, 153, 0.25);
    color: #9de6bd;
}
.contact-form-message.error {
    background: rgba(255, 100, 100, 0.10);
    border: 1px solid rgba(255, 100, 100, 0.22);
    color: #ffb4b4;
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


            <!-- HOME -->

            <a href="index.php">
                Home
            </a>


            <!-- COLLECTION -->

            <a href="collection.php">
                Collection
            </a>


            <!-- PRE-ORDERS -->

            <a href="preorder.php">
                Pre-Orders
            </a>


            <!-- ABOUT -->

            <a href="about.php">
                About
            </a>


            <!-- CONTACT -->

            <a
                href="contact.php"
                class="active"
            >
                Contact
            </a>


            <!-- EXPANDABLE SEARCH -->

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
                            alt=""
                        >

                    <?php endif; ?>

                </button>


                <input
                    type="search"
                    class="header-search-input"
                    id="headerSearchInput"
                    placeholder="Search figures..."
                    autocomplete="off"
                    aria-label="Search figures"
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


                <?php if ($contactSuccess): ?>

                <div class="contact-form-message success" role="status">
                    <?= e($contactSuccess) ?>
                </div>

                <?php endif; ?>

                <?php if ($contactError): ?>

                <div class="contact-form-message error" role="alert">
                    <?= e($contactError) ?>
                </div>

                <?php endif; ?>

                <form
                    class="contact-form"
                    id="contactForm"
                    method="POST"
                    action="contact.php"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($contactCsrf) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="contact_submit"
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
                                value="<?= e($contactName) ?>"
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
                                value="<?= e($contactEmail) ?>"
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
                                <?= $contactSubject === '' ? 'selected' : '' ?>
                                disabled
                            >
                                Select a topic
                            </option>

                            <option value="product" <?= $contactSubject === 'product' ? 'selected' : '' ?>>
                                Product Inquiry
                            </option>

                            <option value="preorder" <?= $contactSubject === 'preorder' ? 'selected' : '' ?>>
                                Pre-Order
                            </option>

                            <option value="order" <?= $contactSubject === 'order' ? 'selected' : '' ?>>
                                Existing Order
                            </option>

                            <option value="general" <?= $contactSubject === 'general' ? 'selected' : '' ?>>
                                General Question
                            </option>

                            <option value="other" <?= $contactSubject === 'other' ? 'selected' : '' ?>>
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
                        ><?= e($contactMessage) ?></textarea>

                    </div>


                    <button
                        type="submit"
                        class="contact-submit"
                    >
                        Send Message →
                    </button>


                    <p class="contact-form-note">
                        Your message will be securely recorded for customer support.
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

            <a
                href="contact.php"
                class="active"
            >
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
            document.getElementById('headerSearch');

        const headerSearchToggle =
            document.getElementById('headerSearchToggle');

        const headerSearchInput =
            document.getElementById('headerSearchInput');


        if (
            !headerSearch ||
            !headerSearchToggle ||
            !headerSearchInput
        ) {
            return;
        }


        headerSearchToggle.addEventListener(
            'click',
            function (event) {

                event.stopPropagation();

                const isOpen =
                    headerSearch.classList.contains('active');


                if (isOpen) {

                    headerSearchInput.focus();

                    return;
                }


                headerSearch.classList.add('active');

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


        document.addEventListener(
            'click',
            function (event) {

                if (
                    !headerSearch.contains(event.target)
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

                    headerSearchInput.value = '';

                    headerSearchToggle.focus();

                }

            }
        );

    }
);


</script>


</body>
</html>