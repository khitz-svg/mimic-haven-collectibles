<?php

require_once 'auth.php';
require_once 'db.php';


/* =========================================================
   LOGIN REQUIRED
========================================================= */

requireLogin();

$userId = currentUserId();


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


/* =========================================================
   CSRF TOKEN
========================================================= */

if (empty($_SESSION['feedback_csrf'])) {

    $_SESSION['feedback_csrf'] =
        bin2hex(random_bytes(32));
}

$feedbackCsrf =
    $_SESSION['feedback_csrf'];


/* =========================================================
   MESSAGE
========================================================= */

$message = '';
$messageType = '';


/* =========================================================
   SUBMIT FEEDBACK
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken =
        $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrfToken) ||
        !hash_equals(
            $_SESSION['feedback_csrf'],
            $csrfToken
        )
    ) {

        $message =
            'Security validation failed. Please try again.';

        $messageType = 'error';

    } else {

        $orderId =
            filter_input(
                INPUT_POST,
                'order_id',
                FILTER_VALIDATE_INT
            );

        $productId =
            filter_input(
                INPUT_POST,
                'product_id',
                FILTER_VALIDATE_INT
            );

        $rating =
            filter_input(
                INPUT_POST,
                'rating',
                FILTER_VALIDATE_INT
            );

        $reviewText =
            trim(
                (string)(
                    $_POST['review_text'] ?? ''
                )
            );


        /* -------------------------------------------------
           VALIDATE BASIC INPUT
        ------------------------------------------------- */

        if (
            !$orderId ||
            !$productId
        ) {

            $message =
                'Please select the order and figure you want to review.';

            $messageType = 'error';

        } elseif (
            !$rating ||
            $rating < 1 ||
            $rating > 5
        ) {

            $message =
                'Please select a rating from 1 to 5 stars.';

            $messageType = 'error';

        } elseif ($reviewText === '') {

            $message =
                'Please write your feedback.';

            $messageType = 'error';

        } elseif (strlen($reviewText) > 2000) {

            $message =
                'Your feedback must not exceed 2000 characters.';

            $messageType = 'error';

        } else {


            /* -------------------------------------------------
               VERIFY COMPLETED ORDER + PRODUCT
            ------------------------------------------------- */

            $verifyStmt = $conn->prepare(

                "SELECT
                    o.id AS order_id,
                    oi.product_id,
                    p.name AS product_name
                 FROM orders o

                 INNER JOIN order_items oi
                    ON oi.order_id = o.id

                 INNER JOIN products p
                    ON p.id = oi.product_id

                 WHERE o.id = ?
                   AND o.user_id = ?
                   AND o.status = 'Completed'
                   AND oi.product_id = ?

                 LIMIT 1"

            );


            if (!$verifyStmt) {

                $message =
                    'Unable to verify your purchase.';

                $messageType = 'error';

            } else {

                $verifyStmt->bind_param(
                    "iii",
                    $orderId,
                    $userId,
                    $productId
                );

                $verifyStmt->execute();

                $verifyResult =
                    $verifyStmt->get_result();

                $purchase =
                    $verifyResult->fetch_assoc();

                $verifyStmt->close();


                if (!$purchase) {

                    $message =
                        'You can only review figures from your completed purchases.';

                    $messageType = 'error';

                } else {


                    /* -------------------------------------------------
                       PREVENT DUPLICATE REVIEW
                    ------------------------------------------------- */

                    $existingStmt = $conn->prepare(

                        "SELECT id
                         FROM reviews
                         WHERE user_id = ?
                           AND order_id = ?
                           AND product_id = ?
                         LIMIT 1"

                    );


                    if (!$existingStmt) {

                        $message =
                            'Unable to check your existing reviews.';

                        $messageType = 'error';

                    } else {

                        $existingStmt->bind_param(
                            "iii",
                            $userId,
                            $orderId,
                            $productId
                        );

                        $existingStmt->execute();

                        $existingResult =
                            $existingStmt->get_result();

                        $existingReview =
                            $existingResult->fetch_assoc();

                        $existingStmt->close();


                        if ($existingReview) {

                            $message =
                                'You have already submitted feedback for this purchase.';

                            $messageType = 'error';

                        } else {


                            /* -------------------------------------------------
                               SAVE REVIEW
                            ------------------------------------------------- */

                            $insertStmt = $conn->prepare(

                                "INSERT INTO reviews
                                (
                                    user_id,
                                    order_id,
                                    product_id,
                                    rating,
                                    review_text,
                                    status
                                )
                                VALUES
                                (
                                    ?, ?, ?, ?, ?, 'Pending'
                                )"

                            );


                            if (!$insertStmt) {

                                $message =
                                    'Unable to save your feedback.';

                                $messageType = 'error';

                            } else {

                                $insertStmt->bind_param(
                                    "iiiis",
                                    $userId,
                                    $orderId,
                                    $productId,
                                    $rating,
                                    $reviewText
                                );


                                if ($insertStmt->execute()) {

                                    $message =
                                        'Thank you! Your feedback has been submitted and is waiting for approval.';

                                    $messageType =
                                        'success';


                                    /* -------------------------------------------------
                                       REFRESH CSRF TOKEN
                                    ------------------------------------------------- */

                                    $_SESSION['feedback_csrf'] =
                                        bin2hex(
                                            random_bytes(32)
                                        );

                                    $feedbackCsrf =
                                        $_SESSION['feedback_csrf'];

                                } else {

                                    $message =
                                        'Unable to save your feedback. Please try again.';

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

}


/* =========================================================
   GET COMPLETED ORDERS
========================================================= */

$completedItems = [];


$ordersStmt = $conn->prepare(

    "SELECT
        o.id AS order_id,
        o.created_at,
        p.id AS product_id,
        p.name AS product_name,
        p.series,
        p.image

     FROM orders o

     INNER JOIN order_items oi
        ON oi.order_id = o.id

     INNER JOIN products p
        ON p.id = oi.product_id

     WHERE o.user_id = ?
       AND o.status = 'Completed'

     ORDER BY
        o.created_at DESC,
        oi.id ASC"

);


if ($ordersStmt) {

    $ordersStmt->bind_param(
        "i",
        $userId
    );

    $ordersStmt->execute();

    $ordersResult =
        $ordersStmt->get_result();


    while (
        $row =
        $ordersResult->fetch_assoc()
    ) {

        $completedItems[] =
            $row;

    }


    $ordersStmt->close();

}


/* =========================================================
   GET EXISTING REVIEWS
========================================================= */

$reviewedItems = [];

$reviewedStmt = $conn->prepare(

    "SELECT
        order_id,
        product_id,
        rating,
        review_text,
        status

     FROM reviews

     WHERE user_id = ?"

);


if ($reviewedStmt) {

    $reviewedStmt->bind_param(
        "i",
        $userId
    );

    $reviewedStmt->execute();

    $reviewedResult =
        $reviewedStmt->get_result();


    while (
        $row =
        $reviewedResult->fetch_assoc()
    ) {

        $reviewKey =
            $row['order_id'] .
            '-' .
            $row['product_id'];

        $reviewedItems[$reviewKey] =
            $row;

    }


    $reviewedStmt->close();

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
        __DIR__ . "/assets/logo.{$ext}";

    if (file_exists($file)) {

        $logo =
            "assets/logo.{$ext}";

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
        Customer Feedback | Mimic Haven Collectibles
    </title>

    <meta
        name="description"
        content="Share your experience with Mimic Haven Collectibles."
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
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
           FEEDBACK PAGE
        ===================================================== */

        .feedback-page {

            max-width: 1050px;

            margin: 0 auto;

            padding:
                75px
                24px
                100px;

        }


        .feedback-heading {

            text-align: center;

            margin-bottom: 45px;

        }


        .feedback-heading span {

            display: block;

            margin-bottom: 12px;

            color: var(--blue);

            font-family:
                Montserrat,
                sans-serif;

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 2px;

        }


        .feedback-heading h1 {

            margin: 0;

            font-family:
                Montserrat,
                sans-serif;

            font-size: 46px;

            font-weight: 800;

            color: #ffffff;

        }


        .feedback-heading p {

            max-width: 680px;

            margin:
                16px
                auto
                0;

            color: #939ca6;

            font-family:
                Inter,
                sans-serif;

            font-size: 14px;

            line-height: 1.7;

        }


        /* =====================================================
           MESSAGE
        ===================================================== */

        .feedback-message {

            margin:
                0
                auto
                28px;

            padding:
                14px
                18px;

            max-width: 850px;

            border-radius: 8px;

            font-family:
                Inter,
                sans-serif;

            font-size: 13px;

        }


        .feedback-message.success {

            color: #8be3b0;

            background:
                rgba(
                    39,
                    174,
                    96,
                    0.10
                );

            border:
                1px solid
                rgba(
                    39,
                    174,
                    96,
                    0.25
                );

        }


        .feedback-message.error {

            color: #ff9b91;

            background:
                rgba(
                    231,
                    76,
                    60,
                    0.10
                );

            border:
                1px solid
                rgba(
                    231,
                    76,
                    60,
                    0.25
                );

        }


        /* =====================================================
           FEEDBACK CARD
        ===================================================== */

        .feedback-card {

            max-width: 850px;

            margin:
                0
                auto
                25px;

            padding: 30px;

            background: #101318;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.08
                );

            border-radius: 14px;

        }


        .feedback-product {

            display: flex;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;

        }


        .feedback-product-image {

            width: 92px;

            height: 92px;

            flex-shrink: 0;

            border-radius: 10px;

            overflow: hidden;

            background: #0b0e12;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.08
                );

        }


        .feedback-product-image img {

            width: 100%;

            height: 100%;

            object-fit: contain;

        }


        .feedback-product-info h2 {

            margin:
                0
                0
                7px;

            color: #ffffff;

            font-family:
                Montserrat,
                sans-serif;

            font-size: 18px;

        }


        .feedback-product-info p {

            margin: 3px 0;

            color: #89929c;

            font-family:
                Inter,
                sans-serif;

            font-size: 12px;

        }


        .feedback-order {

            color: #6f7984 !important;

            font-size: 11px !important;

        }


        /* =====================================================
           STAR RATING
        ===================================================== */

        .feedback-label {

            display: block;

            margin-bottom: 10px;

            color: #ffffff;

            font-family:
                Montserrat,
                sans-serif;

            font-size: 12px;

            font-weight: 700;

            letter-spacing: 0.5px;

        }


        .star-rating {

            display: flex;

            flex-direction: row-reverse;

            justify-content: flex-end;

            gap: 5px;

            margin-bottom: 25px;

        }


        .star-rating input {

            display: none;

        }


        .star-rating label {

            color: #3c434b;

            font-size: 30px;

            line-height: 1;

            cursor: pointer;

            transition:
                color 0.2s ease,
                transform 0.2s ease;

        }


        .star-rating label:hover {

            transform:
                scale(1.08);

        }


        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {

            color: #f5c542;

        }


        /* =====================================================
           TEXTAREA
        ===================================================== */

        .feedback-textarea {

            width: 100%;

            min-height: 145px;

            padding: 14px 16px;

            resize: vertical;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.10
                );

            border-radius: 8px;

            background: #0b0e12;

            color: #ffffff;

            font-family:
                Inter,
                sans-serif;

            font-size: 13px;

            line-height: 1.6;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;

        }


        .feedback-textarea::placeholder {

            color: #626b75;

        }


        .feedback-textarea:focus {

            border-color:
                var(--blue);

            box-shadow:
                0 0 0 3px
                rgba(
                    63,
                    169,
                    245,
                    0.08
                );

        }


        /* =====================================================
           SUBMIT BUTTON
        ===================================================== */

        .feedback-submit {

            margin-top: 18px;

            padding:
                12px
                22px;

            border: none;

            border-radius: 8px;

            background:
                var(--blue);

            color: #101318;

            font-family:
                Montserrat,
                sans-serif;

            font-size: 12px;

            font-weight: 800;

            cursor: pointer;

            transition:
                transform 0.2s ease,
                opacity 0.2s ease;

        }


        .feedback-submit:hover {

            transform:
                translateY(-2px);

            opacity: 0.92;

        }


        /* =====================================================
           ALREADY REVIEWED
        ===================================================== */

        .feedback-reviewed {

            margin-top: 20px;

            padding:
                15px
                17px;

            border-radius: 8px;

            background:
                rgba(
                    154,
                    220,
                    247,
                    0.05
                );

            border:
                1px solid
                rgba(
                    154,
                    220,
                    247,
                    0.10
                );

        }


        .feedback-reviewed-rating {

            color: #f5c542;

            font-size: 18px;

            letter-spacing: 2px;

        }


        .feedback-reviewed p {

            margin:
                8px
                0
                5px;

            color: #aeb6bf;

            font-family:
                Inter,
                sans-serif;

            font-size: 13px;

            line-height: 1.6;

        }


        .feedback-status {

            color: #89929c;

            font-family:
                Inter,
                sans-serif;

            font-size: 11px;

        }


        /* =====================================================
           NO ORDERS
        ===================================================== */

        .feedback-empty {

            max-width: 700px;

            margin: 0 auto;

            padding: 45px 30px;

            text-align: center;

            background: #101318;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.08
                );

            border-radius: 14px;

        }


        .feedback-empty h2 {

            margin: 0 0 10px;

            color: #ffffff;

            font-family:
                Montserrat,
                sans-serif;

        }


        .feedback-empty p {

            margin: 0;

            color: #89929c;

            font-family:
                Inter,
                sans-serif;

            font-size: 13px;

        }


        .feedback-back {

            display: block;

            width: fit-content;

            margin:
                35px
                auto
                0;

            color: var(--blue);

            text-decoration: none;

            font-family:
                Montserrat,
                sans-serif;

            font-size: 12px;

            font-weight: 700;

        }


        .feedback-back:hover {

            text-decoration: underline;

        }


        @media (max-width: 600px) {

            .feedback-page {

                padding:
                    50px
                    18px
                    70px;

            }

            .feedback-heading h1 {

                font-size: 34px;

            }

            .feedback-card {

                padding: 22px;

            }

            .feedback-product {

                align-items: flex-start;

            }

            .feedback-product-image {

                width: 75px;

                height: 75px;

            }

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


        <!-- NAVIGATION -->

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


            <!-- SEARCH -->

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


            <!-- BROWSE -->

            <a
                class="browse-btn"
                href="collection.php"
            >
                Browse Figures
            </a>

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
     MAIN
========================================================= -->

<main>

    <section class="feedback-page">


        <!-- PAGE HEADING -->

        <div class="feedback-heading">

            <span>
                YOUR EXPERIENCE MATTERS
            </span>

            <h1>
                Customer Feedback
            </h1>

            <p>
                Share your experience with your purchased figures.
                Your feedback helps other collectors make informed
                decisions.
            </p>

        </div>



        <!-- =================================================
             MESSAGE
        ================================================== -->

        <?php if ($message !== ''): ?>

            <div
                class="feedback-message <?= e($messageType) ?>"
            >
                <?= e($message) ?>
            </div>

        <?php endif; ?>



        <!-- =================================================
             COMPLETED ORDERS
        ================================================== -->

        <?php if (empty($completedItems)): ?>

            <div class="feedback-empty">

                <h2>
                    No Completed Purchases Yet
                </h2>

                <p>
                    You can submit feedback after one of your
                    orders has been completed.
                </p>

            </div>


        <?php else: ?>


            <?php foreach ($completedItems as $item): ?>

                <?php

                $reviewKey =
                    $item['order_id'] .
                    '-' .
                    $item['product_id'];

                $existingReview =
                    $reviewedItems[$reviewKey]
                    ?? null;

                ?>

                <article class="feedback-card">


                    <!-- PRODUCT -->

                    <div class="feedback-product">

                        <div class="feedback-product-image">

                            <?php

                            $productImage =
                                asset(
                                    $item['image']
                                    ?: 'placeholder'
                                );

                            ?>

                            <?php if ($productImage): ?>

                                <img
                                    src="<?= e($productImage) ?>"
                                    alt="<?= e($item['product_name']) ?>"
                                >

                            <?php endif; ?>

                        </div>


                        <div class="feedback-product-info">

                            <h2>
                                <?= e($item['product_name']) ?>
                            </h2>

                            <p>
                                <?= e($item['series']) ?>
                            </p>

                            <p class="feedback-order">

                                Order #<?= e(
                                    (string)$item['order_id']
                                ) ?>

                                ·

                                Completed

                            </p>

                        </div>

                    </div>



                    <!-- ALREADY REVIEWED -->

                    <?php if ($existingReview): ?>


                        <div class="feedback-reviewed">

                            <div
                                class="feedback-reviewed-rating"
                                aria-label="<?= (int)$existingReview['rating'] ?> out of 5 stars"
                            >

                                <?php

                                for (
                                    $star = 1;
                                    $star <= 5;
                                    $star++
                                ):

                                ?>

                                    <?= $star <=
                                        (int)$existingReview['rating']
                                        ? '★'
                                        : '☆'
                                    ?>

                                <?php endfor; ?>

                            </div>


                            <p>
                                <?= e(
                                    $existingReview['review_text']
                                ) ?>
                            </p>


                            <span class="feedback-status">

                                Status:
                                <?= e(
                                    $existingReview['status']
                                ) ?>

                            </span>

                        </div>


                    <?php else: ?>


                        <!-- FEEDBACK FORM -->

                        <form
                            method="POST"
                            action="feedback.php"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e($feedbackCsrf) ?>"
                            >

                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= (int)$item['order_id'] ?>"
                            >

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= (int)$item['product_id'] ?>"
                            >


                            <label class="feedback-label">

                                Your Rating

                            </label>


                            <div
                                class="star-rating"
                                aria-label="Choose a rating"
                            >

                                <input
                                    type="radio"
                                    id="star5-<?= (int)$item['order_id'] ?>-<?= (int)$item['product_id'] ?>"
                                    name="rating"
                                    value="5"
                                >

                                <label
                                    for="star5-<?= (int)$item['order_id'] ?>-<?= (int)$item['product_id'] ?>"
                                    title="5 stars"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star4-<?= (int)$item['order_id'] ?>-<?= (int)$item['product_id'] ?>"
                                    name="rating"
                                    value="4"
                                >

                                <label
                                    for="star4-<?= (int)$item['order_id'] ?>-<?= (int)$item['product_id'] ?>"
                                    title="4 stars"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star3-<?= (int)$item['order_id'] ?>-<?= (int)$item['product_id'] ?>"
                                    name="rating"
                                    value="3"
                                >

                                <label
                                    for="star3-<?= (int)$item['order_id'] ?>-<?= (int)$item['product_id'] ?>"
                                    title="3 stars"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star2-<?= (int)$item['order_id'] ?>-<?= (int)$item['product_id'] ?>"
                                    name="rating"
                                    value="2"
                                >

                                <label
                                    for="star2-<?= (int)$item['order_id'] ?>-<?= (int)$item['product_id'] ?>"
                                    title="2 stars"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star1-<?= (int)$item['order_id'] ?>-<?= (int)$item['product_id'] ?>"
                                    name="rating"
                                    value="1"
                                >

                                <label
                                    for="star1-<?= (int)$item['order_id'] ?>-<?= (int)$item['product_id'] ?>"
                                    title="1 star"
                                >
                                    ★
                                </label>

                            </div>


                            <label
                                class="feedback-label"
                                for="review-text-<?= (int)$item['order_id'] ?>-<?= (int)$item['product_id'] ?>"
                            >

                                Your Feedback

                            </label>


                            <textarea
                                class="feedback-textarea"
                                id="review-text-<?= (int)$item['order_id'] ?>-<?= (int)$item['product_id'] ?>"
                                name="review_text"
                                maxlength="2000"
                                placeholder="Tell us about your experience with this figure..."
                                required
                            ></textarea>


                            <button
                                type="submit"
                                class="feedback-submit"
                            >
                                Submit Feedback
                            </button>

                        </form>


                    <?php endif; ?>

                </article>

            <?php endforeach; ?>


        <?php endif; ?>


        <a
            class="feedback-back"
            href="account.php"
        >
            ← Back to My Account
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

</body>

</html>