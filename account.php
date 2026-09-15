<?php

require_once 'auth.php';
require_once 'db.php';

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
   CUSTOMER INFORMATION
========================================================= */

$userStmt = $conn->prepare(
    "SELECT
        id,
        first_name,
        last_name,
        email,
        reliability_score,
        completed_preorders,
        cancelled_preorders,
        created_at
     FROM users
     WHERE id = ?
     LIMIT 1"
);

$userStmt->bind_param(
    "i",
    $userId
);

$userStmt->execute();

$userResult = $userStmt->get_result();

$user = $userResult->fetch_assoc();

$userStmt->close();


if (!$user) {
    logoutUser();
    header('Location: login.php');
    exit;
}


/* =========================================================
   ORDERS
========================================================= */

$orders = [];

$orderStmt = $conn->prepare(
    "SELECT
        id,
        total_amount,
        payment_method,
        status,
        delivery_address,
        order_notes,
        created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$orderStmt->bind_param(
    "i",
    $userId
);

$orderStmt->execute();

$orderResult = $orderStmt->get_result();

while ($row = $orderResult->fetch_assoc()) {

    $orders[] = $row;

}

$orderStmt->close();


/* =========================================================
   PRE-ORDERS
========================================================= */

$preorders = [];

$preorderStmt = $conn->prepare(
    "SELECT
        pr.id,
        pr.product_id,
        pr.quantity,
        pr.unit_price,
        pr.total_amount,
        pr.deposit_amount,
        pr.deposit_paid_amount,
        pr.deposit_status,
        pr.deposit_paid_at,
        pr.remaining_balance,
        pr.balance_paid_amount,
        pr.balance_status,
        pr.balance_paid_at,
        pr.deposit_due_date,
        pr.balance_due_date,
        pr.expected_release_date,
        pr.status,
        pr.release_status,
        pr.notes,
        pr.created_at,
        p.name AS product_name,
        p.series,
        p.image
     FROM preorders pr
     INNER JOIN products p
        ON p.id = pr.product_id
     WHERE pr.user_id = ?
     ORDER BY pr.created_at DESC"
);

$preorderStmt->bind_param(
    "i",
    $userId
);

$preorderStmt->execute();

$preorderResult =
    $preorderStmt->get_result();

while (
    $row =
    $preorderResult->fetch_assoc()
) {

    $preorders[] = $row;

}

$preorderStmt->close();


/* =========================================================
   EXISTING REVIEWS
   Used to determine whether a completed order has already
   been reviewed.
========================================================= */

$reviewedOrders = [];

$reviewStmt = $conn->prepare(
    "SELECT
        order_id,
        product_id
     FROM reviews
     WHERE user_id = ?"
);

$reviewStmt->bind_param(
    "i",
    $userId
);

$reviewStmt->execute();

$reviewResult =
    $reviewStmt->get_result();

while (
    $row =
    $reviewResult->fetch_assoc()
) {

    $key =
        $row['order_id'] .
        '-' .
        $row['product_id'];

    $reviewedOrders[$key] = true;

}

$reviewStmt->close();


/* =========================================================
   GET PRODUCTS FOR EACH ORDER
========================================================= */

$orderProducts = [];

if (!empty($orders)) {

    $orderIds = [];

    foreach ($orders as $order) {

        $orderIds[] =
            (int)$order['id'];

    }

    foreach ($orderIds as $orderId) {

        $itemStmt = $conn->prepare(
            "SELECT
                oi.product_id,
                oi.quantity,
                oi.price,
                p.name,
                p.series,
                p.image
             FROM order_items oi
             LEFT JOIN products p
                ON p.id = oi.product_id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC"
        );

        $itemStmt->bind_param(
            "i",
            $orderId
        );

        $itemStmt->execute();

        $itemResult =
            $itemStmt->get_result();

        $orderProducts[$orderId] = [];

        while (
            $item =
            $itemResult->fetch_assoc()
        ) {

            $orderProducts[$orderId][] =
                $item;

        }

        $itemStmt->close();

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
        My Account | Mimic Haven Collectibles
    </title>

    <meta
        name="description"
        content="View your orders, pre-orders, payments, and customer information."
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
           ACCOUNT PAGE
        ===================================================== */

        .account-page {

            max-width: 1180px;

            margin: 0 auto;

            padding:
                70px 24px 100px;

        }


        .account-heading {

            margin-bottom: 40px;

        }


        .account-heading span {

            display: block;

            margin-bottom: 10px;

            color: var(--blue);

            font-family:
                Montserrat,
                sans-serif;

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 2px;

        }


        .account-heading h1 {

            margin: 0;

            color: #ffffff;

            font-family:
                Montserrat,
                sans-serif;

            font-size: 44px;

            font-weight: 800;

        }


        .account-heading p {

            margin-top: 12px;

            color: #89929c;

            font-family:
                Inter,
                sans-serif;

            font-size: 14px;

        }


        /* =====================================================
           PROFILE
        ===================================================== */

        .account-profile {

            display: grid;

            grid-template-columns:
                1.5fr
                1fr
                1fr;

            gap: 18px;

            margin-bottom: 28px;

        }


        .account-card {

            padding: 26px;

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


        .account-label {

            display: block;

            margin-bottom: 8px;

            color: var(--blue);

            font-family:
                Montserrat,
                sans-serif;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 1.5px;

        }


        .account-card h2 {

            margin: 0;

            color: #ffffff;

            font-family:
                Montserrat,
                sans-serif;

            font-size: 20px;

        }


        .account-card p {

            margin:
                7px 0 0;

            color: #8f99a3;

            font-family:
                Inter,
                sans-serif;

            font-size: 13px;

        }


        .account-number {

            color: #ffffff !important;

            font-family:
                Montserrat,
                sans-serif !important;

            font-size: 25px !important;

            font-weight: 800;

        }


        /* =====================================================
           SECTION HEADING
        ===================================================== */

        .account-section-heading {

            display: flex;

            align-items: flex-end;

            justify-content: space-between;

            gap: 20px;

            margin:
                45px 0
                18px;

        }


        .account-section-heading h2 {

            margin: 0;

            color: #ffffff;

            font-family:
                Montserrat,
                sans-serif;

            font-size: 25px;

        }


        /* =====================================================
           ORDER CARD
        ===================================================== */

        .account-order {

            margin-bottom: 18px;

        }


        .account-order-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 18px;

            flex-wrap: wrap;

        }


        .account-order-header h3 {

            margin: 0;

            color: #ffffff;

            font-family:
                Montserrat,
                sans-serif;

            font-size: 17px;

        }


        .account-order-date {

            margin-top: 5px !important;

            color: #69737d !important;

            font-size: 11px !important;

        }


        .account-order-status {

            display: inline-flex;

            align-items: center;

            padding:
                7px
                11px;

            border-radius: 999px;

            background:
                rgba(
                    154,
                    220,
                    247,
                    0.08
                );

            border:
                1px solid
                rgba(
                    154,
                    220,
                    247,
                    0.15
                );

            color: var(--blue);

            font-family:
                Montserrat,
                sans-serif;

            font-size: 10px;

            font-weight: 700;

        }


        .account-products {

            margin-top: 22px;

            display: grid;

            gap: 12px;

        }


        .account-product {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding:
                13px;

            background: #0b0e12;

            border-radius: 9px;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.05
                );

        }


        .account-product-info {

            display: flex;

            align-items: center;

            gap: 13px;

            min-width: 0;

        }


        .account-product-image {

            width: 55px;

            height: 55px;

            flex-shrink: 0;

            border-radius: 7px;

            overflow: hidden;

            background: #101318;

        }


        .account-product-image img {

            width: 100%;

            height: 100%;

            object-fit: contain;

        }


        .account-product-info h4 {

            margin: 0;

            color: #ffffff;

            font-family:
                Montserrat,
                sans-serif;

            font-size: 12px;

        }


        .account-product-info p {

            margin:
                4px 0 0;

            color: #69737d;

            font-family:
                Inter,
                sans-serif;

            font-size: 11px;

        }


        .account-product-price {

            flex-shrink: 0;

            color: #ffffff;

            font-family:
                Montserrat,
                sans-serif;

            font-size: 12px;

            font-weight: 700;

        }


        /* =====================================================
           ORDER FOOTER
        ===================================================== */

        .account-order-footer {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            flex-wrap: wrap;

            margin-top: 20px;

            padding-top: 18px;

            border-top:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.06
                );

        }


        .account-order-total span {

            display: block;

            color: #69737d;

            font-family:
                Inter,
                sans-serif;

            font-size: 10px;

        }


        .account-order-total strong {

            display: block;

            margin-top: 4px;

            color: #ffffff;

            font-family:
                Montserrat,
                sans-serif;

            font-size: 18px;

        }


        /* =====================================================
           ACTIONS
        ===================================================== */

        .account-actions {

            display: flex;

            gap: 10px;

            flex-wrap: wrap;

        }


        .account-action {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                10px
                15px;

            border-radius: 7px;

            background:
                var(--blue);

            color: #101318;

            text-decoration: none;

            font-family:
                Montserrat,
                sans-serif;

            font-size: 10px;

            font-weight: 800;

            transition:
                transform 0.2s ease,
                opacity 0.2s ease;

        }


        .account-action:hover {

            transform:
                translateY(-2px);

            opacity: 0.92;

        }


        .account-action.secondary {

            background:
                transparent;

            color: var(--blue);

            border:
                1px solid
                rgba(
                    154,
                    220,
                    247,
                    0.35
                );

        }


        .account-action.feedback {

            background:
                transparent;

            color: #f5c542;

            border:
                1px solid
                rgba(
                    245,
                    197,
                    66,
                    0.35
                );

        }


        .account-action.feedback:hover {

            border-color:
                #f5c542;

        }


        /* =====================================================
           PRE-ORDER
        ===================================================== */

        .account-preorder {

            margin-bottom: 18px;

        }


        .account-preorder-product {

            display: flex;

            align-items: center;

            gap: 15px;

            margin-bottom: 22px;

        }


        .account-preorder-image {

            width: 75px;

            height: 75px;

            flex-shrink: 0;

            border-radius: 9px;

            overflow: hidden;

            background: #0b0e12;

        }


        .account-preorder-image img {

            width: 100%;

            height: 100%;

            object-fit: contain;

        }


        .account-preorder-product h3 {

            margin: 0;

            color: #ffffff;

            font-family:
                Montserrat,
                sans-serif;

            font-size: 16px;

        }


        .account-preorder-product p {

            margin-top: 5px;

            color: #747e88;

            font-family:
                Inter,
                sans-serif;

            font-size: 11px;

        }


        .account-preorder-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 12px;

        }


        .account-preorder-stat {

            padding:
                14px;

            background: #0b0e12;

            border-radius: 8px;

        }


        .account-preorder-stat span {

            display: block;

            color: #69737d;

            font-family:
                Inter,
                sans-serif;

            font-size: 9px;

            text-transform: uppercase;

        }


        .account-preorder-stat strong {

            display: block;

            margin-top: 5px;

            color: #ffffff;

            font-family:
                Montserrat,
                sans-serif;

            font-size: 13px;

        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .account-empty {

            padding:
                35px
                25px;

            text-align: center;

            color: #69737d;

            font-family:
                Inter,
                sans-serif;

            font-size: 13px;

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


        @media (max-width: 800px) {

            .account-profile {

                grid-template-columns:
                    1fr;

            }

            .account-preorder-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 550px) {

            .account-page {

                padding:
                    45px
                    18px
                    70px;

            }

            .account-heading h1 {

                font-size: 34px;

            }

            .account-product {

                align-items:
                    flex-start;

            }

            .account-order-footer {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }

            .account-preorder-grid {

                grid-template-columns:
                    1fr;

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
                class="nav-user"
                href="account.php"
            >
                Hi, <?= e($user['first_name']) ?>
            </a>


            <a
                class="nav-account"
                href="logout.php"
            >
                Logout
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
     MAIN
========================================================= -->

<main>

    <section class="account-page">


        <!-- =================================================
             HEADING
        ================================================== -->

        <div class="account-heading">

            <span>
                COLLECTOR ACCOUNT
            </span>

            <h1>
                My Account
            </h1>

            <p>
                Welcome back, <?= e($user['first_name']) ?>.
                Manage your orders, pre-orders, and payments here.
            </p>

        </div>



        <!-- =================================================
             PROFILE / RELIABILITY
        ================================================== -->

        <div class="account-profile">


            <div class="account-card">

                <span class="account-label">
                    CUSTOMER
                </span>

                <h2>
                    <?= e(
                        $user['first_name'] .
                        ' ' .
                        $user['last_name']
                    ) ?>
                </h2>

                <p>
                    <?= e($user['email']) ?>
                </p>

                <p>
                    Member since
                    <?= e(
                        date(
                            'F Y',
                            strtotime(
                                $user['created_at']
                            )
                        )
                    ) ?>
                </p>

            </div>


            <div class="account-card">

                <span class="account-label">
                    RELIABILITY SCORE
                </span>

                <p class="account-number">

                    <?= number_format(
                        (float)$user['reliability_score'],
                        0
                    ) ?>

                </p>

                <p>
                    Based on completed and cancelled pre-orders.
                </p>

            </div>


            <div class="account-card">

                <span class="account-label">
                    COMPLETED PRE-ORDERS
                </span>

                <p class="account-number">

                    <?= (int)$user['completed_preorders'] ?>

                </p>

                <p>
                    Successfully completed reservations.
                </p>

            </div>

        </div>



        <!-- =================================================
             ORDERS
        ================================================== -->

        <div class="account-section-heading">

            <div>

                <span class="account-label">
                    PURCHASE HISTORY
                </span>

                <h2>
                    My Orders
                </h2>

            </div>

        </div>


        <?php if (empty($orders)): ?>

            <div class="account-empty">

                You have not placed any orders yet.

            </div>


        <?php else: ?>


            <?php foreach ($orders as $order): ?>

                <?php

                $orderId =
                    (int)$order['id'];

                $items =
                    $orderProducts[$orderId]
                    ?? [];

                ?>


                <article class="account-card account-order">


                    <!-- ORDER HEADER -->

                    <div class="account-order-header">

                        <div>

                            <h3>
                                Order #<?= $orderId ?>
                            </h3>

                            <p class="account-order-date">

                                <?= e(
                                    date(
                                        'F j, Y g:i A',
                                        strtotime(
                                            $order['created_at']
                                        )
                                    )
                                ) ?>

                            </p>

                        </div>


                        <span class="account-order-status">

                            <?= e(
                                $order['status']
                            ) ?>

                        </span>

                    </div>



                    <!-- PRODUCTS -->

                    <?php if (!empty($items)): ?>

                        <div class="account-products">

                            <?php foreach ($items as $item): ?>

                                <div
                                    class="account-product"
                                >


                                    <div
                                        class="account-product-info"
                                    >

                                        <div
                                            class="account-product-image"
                                        >

                                            <?php

                                            $itemImage =
                                                asset(
                                                    $item['image']
                                                    ?: 'placeholder'
                                                );

                                            ?>

                                            <?php if ($itemImage): ?>

                                                <img
                                                    src="<?= e($itemImage) ?>"
                                                    alt="<?= e($item['name'] ?? 'Figure') ?>"
                                                >

                                            <?php endif; ?>

                                        </div>


                                        <div>

                                            <h4>
                                                <?= e(
                                                    $item['name']
                                                    ?? 'Anime Figure'
                                                ) ?>
                                            </h4>

                                            <p>

                                                <?= e(
                                                    $item['series']
                                                    ?? ''
                                                ) ?>

                                                · Qty:
                                                <?= (int)$item['quantity'] ?>

                                            </p>

                                        </div>

                                    </div>


                                    <div
                                        class="account-product-price"
                                    >

                                        ₱<?= number_format(
                                            (float)$item['price'],
                                            2
                                        ) ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>



                    <!-- ORDER FOOTER -->

                    <div class="account-order-footer">


                        <div
                            class="account-order-total"
                        >

                            <span>
                                ORDER TOTAL
                            </span>

                            <strong>
                                ₱<?= number_format(
                                    (float)$order['total_amount'],
                                    2
                                ) ?>
                            </strong>

                        </div>


                        <div class="account-actions">


                            <a
                                class="account-action secondary"
                                href="order.php?id=<?= $orderId ?>"
                            >
                                View Details
                            </a>


                            <?php if (
                                $order['status'] === 'Completed'
                            ): ?>


                                <?php

                                /*
                                 * Only show Leave Feedback
                                 * when the completed order
                                 * contains at least one product
                                 * that has not yet been reviewed.
                                 */

                                $canReview = false;

                                foreach ($items as $item) {

                                    $reviewKey =
                                        $orderId .
                                        '-' .
                                        (int)$item['product_id'];

                                    if (
                                        !isset(
                                            $reviewedOrders[
                                                $reviewKey
                                            ]
                                        )
                                    ) {

                                        $canReview = true;

                                        break;

                                    }

                                }

                                ?>


                                <?php if ($canReview): ?>

                                    <a
                                        class="account-action feedback"
                                        href="feedback.php"
                                    >
                                        ★ Leave Feedback
                                    </a>

                                <?php endif; ?>


                            <?php endif; ?>


                        </div>

                    </div>

                </article>


            <?php endforeach; ?>


        <?php endif; ?>



        <!-- =================================================
             PRE-ORDERS
        ================================================== -->

        <div class="account-section-heading">

            <div>

                <span class="account-label">
                    RESERVATIONS
                </span>

                <h2>
                    My Pre-Orders
                </h2>

            </div>

        </div>


        <?php if (empty($preorders)): ?>

            <div class="account-empty">

                You do not have any pre-orders yet.

            </div>


        <?php else: ?>


            <?php foreach ($preorders as $preorder): ?>


                <article
                    class="account-card account-preorder"
                >


                    <!-- PRODUCT -->

                    <div
                        class="account-preorder-product"
                    >

                        <div
                            class="account-preorder-image"
                        >

                            <?php

                            $preorderImage =
                                asset(
                                    $preorder['image']
                                    ?: 'placeholder'
                                );

                            ?>

                            <?php if ($preorderImage): ?>

                                <img
                                    src="<?= e($preorderImage) ?>"
                                    alt="<?= e($preorder['product_name']) ?>"
                                >

                            <?php endif; ?>

                        </div>


                        <div>

                            <h3>
                                <?= e(
                                    $preorder['product_name']
                                ) ?>
                            </h3>

                            <p>

                                <?= e(
                                    $preorder['series']
                                ) ?>

                                · Qty:
                                <?= (int)$preorder['quantity'] ?>

                            </p>

                        </div>

                    </div>



                    <!-- PRE-ORDER DATA -->

                    <div
                        class="account-preorder-grid"
                    >


                        <div
                            class="account-preorder-stat"
                        >

                            <span>
                                Status
                            </span>

                            <strong>
                                <?= e(
                                    $preorder['status']
                                ) ?>
                            </strong>

                        </div>


                        <div
                            class="account-preorder-stat"
                        >

                            <span>
                                Release
                            </span>

                            <strong>
                                <?= e(
                                    $preorder['release_status']
                                ) ?>
                            </strong>

                        </div>


                        <div
                            class="account-preorder-stat"
                        >

                            <span>
                                Total
                            </span>

                            <strong>

                                ₱<?= number_format(
                                    (float)$preorder['total_amount'],
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <div
                            class="account-preorder-stat"
                        >

                            <span>
                                Remaining Balance
                            </span>

                            <strong>

                                ₱<?= number_format(
                                    (float)$preorder['remaining_balance'],
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <div
                            class="account-preorder-stat"
                        >

                            <span>
                                Deposit
                            </span>

                            <strong>

                                ₱<?= number_format(
                                    (float)$preorder['deposit_amount'],
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <div
                            class="account-preorder-stat"
                        >

                            <span>
                                Deposit Status
                            </span>

                            <strong>
                                <?= e(
                                    $preorder['deposit_status']
                                ) ?>
                            </strong>

                        </div>


                        <div
                            class="account-preorder-stat"
                        >

                            <span>
                                Balance Paid
                            </span>

                            <strong>

                                ₱<?= number_format(
                                    (float)$preorder['balance_paid_amount'],
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <div
                            class="account-preorder-stat"
                        >

                            <span>
                                Balance Status
                            </span>

                            <strong>
                                <?= e(
                                    $preorder['balance_status']
                                ) ?>
                            </strong>

                        </div>


                    </div>


                </article>


            <?php endforeach; ?>


        <?php endif; ?>


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