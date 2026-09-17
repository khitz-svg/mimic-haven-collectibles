<?php

/* =========================================================
   MIMIC HAVEN - PRE-ORDERS PAGE
========================================================= */

require_once 'auth.php';
require_once 'db.php';


/* =========================================================
   HELPERS
========================================================= */

function asset(string $name): string
{
    foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {

        $assetFile =
            __DIR__ . "/assets/{$name}.{$ext}";

        if (file_exists($assetFile)) {
            return "assets/{$name}.{$ext}";
        }


        $rootFile =
            __DIR__ . "/{$name}.{$ext}";

        if (file_exists($rootFile)) {
            return "{$name}.{$ext}";
        }
    }

    return "";
}


function icon(string $name): string
{
    foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {

        $file =
            __DIR__ . "/assets/icons/{$name}.{$ext}";

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


function peso(float $price): string
{
    return '₱' . number_format(
        $price,
        2
    );
}


/* =========================================================
   BUILD PRE-ORDER PRODUCTS FROM DATABASE
========================================================= */

$preorders = [];

/*
 * Pre-order products are loaded directly from the database.
 * A 30% deposit is calculated per figure and rounded to the
 * nearest ₱50 so newly added pre-order products are included
 * automatically without maintaining a hardcoded product list.
 */
function preorderDeposit(float $price): float
{
    return max(
        50.00,
        round(($price * 0.30) / 50) * 50
    );
}

$productStmt = $conn->prepare(
    "SELECT
        id,
        name,
        series,
        category,
        price,
        image
     FROM products
     WHERE availability = 'Pre-Order'
     ORDER BY id ASC"
);

if ($productStmt) {

    if ($productStmt->execute()) {

        $result =
            $productStmt->get_result();

        while ($dbProduct = $result->fetch_assoc()) {

            $price =
                (float)$dbProduct['price'];

            $preorders[] = [
                'id' => (int)$dbProduct['id'],
                'name' => $dbProduct['name'],
                'series' => $dbProduct['series'],
                'category' => $dbProduct['category'],
                'price' => $price,
                'deposit' => preorderDeposit($price),
                'release' => 'Release Date TBA',
                'image' => $dbProduct['image']
            ];
        }
    }

    $productStmt->close();
}


/* =========================================================
   CSRF TOKEN
========================================================= */

if (
    empty($_SESSION['preorder_csrf'])
) {

    $_SESSION['preorder_csrf'] =
        bin2hex(
            random_bytes(32)
        );
}

$preorderCsrf =
    $_SESSION['preorder_csrf'];


/* =========================================================
   PROCESS PRE-ORDER
========================================================= */

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isLoggedIn()) {

        $message =
            'Please log in to place a pre-order.';

        $messageType =
            'error';

    } else {

        $postedToken =
            $_POST['csrf_token'] ?? '';

        if (
            !is_string($postedToken) ||
            !hash_equals(
                $preorderCsrf,
                $postedToken
            )
        ) {

            $message =
                'Security validation failed. Please try again.';

            $messageType =
                'error';

        } else {

            $productId =
                filter_input(
                    INPUT_POST,
                    'product_id',
                    FILTER_VALIDATE_INT
                );

            $quantity =
                filter_input(
                    INPUT_POST,
                    'quantity',
                    FILTER_VALIDATE_INT
                );


            if (
                !$productId ||
                $productId <= 0
            ) {

                $message =
                    'Invalid pre-order product.';

                $messageType =
                    'error';

            } elseif (
                !$quantity ||
                $quantity < 1 ||
                $quantity > 10
            ) {

                $message =
                    'Quantity must be between 1 and 10.';

                $messageType =
                    'error';

            } else {

                /*
                 * Find the product in the approved
                 * pre-order catalog.
                 */

                $selectedPreorder = null;

                foreach (
                    $preorders as $preorderProduct
                ) {

                    if (
                        (int)$preorderProduct['id']
                        ===
                        (int)$productId
                    ) {

                        $selectedPreorder =
                            $preorderProduct;

                        break;
                    }
                }


                if (!$selectedPreorder) {

                    $message =
                        'This figure is not currently available for pre-order.';

                    $messageType =
                        'error';

                } else {

                    $userId =
                        currentUserId();

                    $unitPrice =
                        (float)$selectedPreorder['price'];

                    $depositPerUnit =
                        (float)$selectedPreorder['deposit'];


                    /*
                     * Calculate the reservation totals.
                     */

                    $totalAmount =
                        $unitPrice * $quantity;

                    $depositAmount =
                        $depositPerUnit * $quantity;

                    $remainingBalance =
                        $totalAmount - $depositAmount;


                    if (
                        $depositAmount <= 0 ||
                        $remainingBalance < 0
                    ) {

                        $message =
                            'Invalid pre-order payment values.';

                        $messageType =
                            'error';

                    } else {

                        /*
                         * Prevent the same customer from
                         * accidentally creating another
                         * active reservation for the same figure.
                         */

                        $existingStmt =
                            $conn->prepare(
                                "SELECT id
                                 FROM preorders
                                 WHERE user_id = ?
                                   AND product_id = ?
                                   AND status NOT IN
                                       ('Completed', 'Cancelled')
                                 LIMIT 1"
                            );

                        $existingStmt->bind_param(
                            "ii",
                            $userId,
                            $productId
                        );

                        $existingStmt->execute();

                        $existingResult =
                            $existingStmt->get_result();

                        $existingPreorder =
                            $existingResult->fetch_assoc();

                        $existingStmt->close();


                        if ($existingPreorder) {

                            $message =
                                'You already have an active pre-order for this figure.';

                            $messageType =
                                'error';

                        } else {

                            /*
                             * Save the reservation.
                             */

                            $insertStmt =
                                $conn->prepare(
                                    "INSERT INTO preorders
                                    (
                                        user_id,
                                        product_id,
                                        quantity,
                                        unit_price,
                                        total_amount,
                                        deposit_amount,
                                        remaining_balance,
                                        expected_release_date,
                                        status,
                                        release_status,
                                        notes
                                    )
                                    VALUES
                                    (
                                        ?, ?, ?, ?, ?, ?, ?, NULL,
                                        'Pending',
                                        'Waiting for Manufacturer',
                                        NULL
                                    )"
                                );


                            $insertStmt->bind_param(
                                "iiidddd",
                                $userId,
                                $productId,
                                $quantity,
                                $unitPrice,
                                $totalAmount,
                                $depositAmount,
                                $remainingBalance
                            );


                            if (
                                $insertStmt->execute()
                            ) {

                                $message =
                                    'Your pre-order has been reserved successfully!';

                                $messageType =
                                    'success';

                                /*
                                 * Refresh the token after
                                 * successful submission.
                                 */

                                $_SESSION['preorder_csrf'] =
                                    bin2hex(
                                        random_bytes(32)
                                    );

                                $preorderCsrf =
                                    $_SESSION['preorder_csrf'];

                            } else {

                                $message =
                                    'Unable to save your pre-order.';

                                $messageType =
                                    'error';
                            }


                            $insertStmt->close();
                        }
                    }
                }
            }
        }
    }
}


/* =========================================================
   LOGO
========================================================= */

$logo = '';

foreach (
    ['png', 'jpg', 'jpeg', 'webp', 'svg']
    as $ext
) {

    $file =
        __DIR__ . "/assets/logo.$ext";

    if (file_exists($file)) {

        $logo =
            "assets/logo.$ext";

        break;
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
        Pre-Orders | Mimic Haven Collectibles
    </title>

    <meta
        name="description"
        content="Reserve upcoming anime figures and collectibles through Mimic Haven pre-orders."
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

    <style>

        .preorder-reserve-form {
            margin-top: 18px;
        }

        .preorder-reserve-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .preorder-quantity {
            width: 70px;
            padding: 10px;
            background: #111315;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 7px;
            font-family: Inter, sans-serif;
        }

        .preorder-reserve-button {
            flex: 1;
            min-width: 140px;
            border: none;
            border-radius: 7px;
            padding: 11px 16px;
            background: var(--blue);
            color: #101214;
            font-family: Montserrat, sans-serif;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
        }

        .preorder-reserve-button:hover {
            opacity: 0.9;
        }

        .preorder-login-note {
            margin-top: 12px;
            color: #89929c;
            font-size: 12px;
            line-height: 1.5;
        }

        .preorder-login-note a {
            color: var(--blue);
            text-decoration: none;
            font-weight: 700;
        }

        .preorder-message {
            max-width: 1000px;
            margin: 0 auto 30px;
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 13px;
        }

        .preorder-message.success {
            color: #8be3b0;
            background: rgba(39,174,96,0.10);
            border: 1px solid rgba(39,174,96,0.25);
        }

        .preorder-message.error {
            color: #ff9b91;
            background: rgba(231,76,60,0.10);
            border: 1px solid rgba(231,76,60,0.25);
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


        <a
            class="brand"
            href="index.php"
            aria-label="Mimic Haven Collectibles"
        >

            <?php if ($logo): ?>

                <img
                    class="brand-logo"
                    src="<?= e($logo) ?>"
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

            <a
                class="active"
                href="preorder.php"
            >
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


            <?php if (isLoggedIn()): ?>

                <a
                    class="nav-user"
                    href="account.php"
                >
                    Hi, <?= e(currentFirstName()) ?>
                </a>

            <?php endif; ?>


            <?php if (isLoggedIn()): ?>

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


    </div>

</section>



<!-- =========================================================
     MESSAGE
========================================================= -->

<?php if ($message !== ''): ?>

    <div
        class="preorder-message <?= e($messageType) ?>"
    >

        <?= e($message) ?>

    </div>

<?php endif; ?>



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



    <?php if (empty($preorders)): ?>

        <div class="preorder-section-heading">

            <p>
                No pre-order figures are currently available.
            </p>

        </div>

    <?php else: ?>


        <div class="preorder-products-grid">


            <?php foreach ($preorders as $product): ?>

                <?php
                $remainingDeposit =
                    (
                        (float)$product['price']
                        -
                        (float)$product['deposit']
                    );
                ?>


                <article
                    class="preorder-product-card"
                >


                    <div
                        class="preorder-product-image"
                    >

                        <img
                            src="assets/collections/<?= e($product['image']) ?>"
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



                    <div
                        class="preorder-product-info"
                    >


                        <span
                            class="preorder-product-series"
                        >
                            <?= e($product['series']) ?>
                        </span>


                        <h3>
                            <?= e($product['name']) ?>
                        </h3>


                        <span
                            class="preorder-product-category"
                        >
                            <?= e($product['category']) ?>
                        </span>


                        <div
                            class="preorder-price-row"
                        >

                            <strong>
                                <?= peso(
                                    (float)$product['price']
                                ) ?>
                            </strong>

                        </div>



                        <div
                            class="preorder-meta"
                        >


                            <div>

                                <span>
                                    Deposit
                                </span>

                                <strong>
                                    <?= peso(
                                        (float)$product['deposit']
                                    ) ?>
                                </strong>

                            </div>


                            <div>

                                <span>
                                    Release
                                </span>

                                <strong>
                                    <?= e(
                                        $product['release']
                                    ) ?>
                                </strong>

                            </div>


                        </div>



                        <div
                            class="preorder-card-footer"
                        >

                            <span
                                class="preorder-condition"
                            >
                                MISB
                            </span>

                            <span
                                class="preorder-status"
                            >
                                Reservation Open
                            </span>

                        </div>



                        <!-- RESERVE -->

                        <?php if (isLoggedIn()): ?>

                            <form
                                method="POST"
                                class="preorder-reserve-form"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(
                                        $preorderCsrf
                                    ) ?>"
                                >


                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= (int)$product['id'] ?>"
                                >


                                <div
                                    class="preorder-reserve-row"
                                >

                                    <input
                                        type="number"
                                        name="quantity"
                                        class="preorder-quantity"
                                        min="1"
                                        max="10"
                                        value="1"
                                        aria-label="Quantity"
                                    >


                                    <button
                                        type="submit"
                                        class="preorder-reserve-button"
                                    >
                                        Reserve Now
                                    </button>

                                </div>


                                <p
                                    class="preorder-login-note"
                                >
                                    Required deposit:
                                    <strong>
                                        <?= peso(
                                            (float)$product['deposit']
                                        ) ?>
                                    </strong>
                                    per figure.
                                </p>

                            </form>


                        <?php else: ?>

                            <p
                                class="preorder-login-note"
                            >
                                <a href="login.php">
                                    Log in
                                </a>
                                to reserve this figure.
                            </p>

                        <?php endif; ?>


                    </div>

                </article>

            <?php endforeach; ?>


        </div>

    <?php endif; ?>



    <div
        class="preorder-view-all"
    >

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


    <div
        class="preorder-section-heading"
    >

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

<section
    class="preorder-notice-section"
>


    <div
        class="preorder-section-heading"
    >

        <span>
            PLEASE READ
        </span>

        <h2>
            IMPORTANT
            <strong>NOTICE</strong>
        </h2>

    </div>



    <div
        class="preorder-notice-grid"
    >


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



    <p
        class="preorder-terms-line"
    >

        By placing a pre-order, you agree to our

        <a href="index.php#terms">
            Terms &amp; Conditions
        </a>.

    </p>

</section>

</main>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer id="contact">


    <div class="footer-grid">


        <div>

            <?php if ($logo): ?>

                <img
                    class="footer-logo"
                    src="<?= e($logo) ?>"
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



<script>
window.MIMIC_HAVEN_CART_KEY = <?= json_encode(
    isLoggedIn()
        ? 'mimicHavenCart_' . currentUserId()
        : 'mimicHavenGuestCart'
) ?>;
</script>

<script src="script.js"></script>


</body>

</html>