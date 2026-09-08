<?php

require_once 'auth.php';
require_once 'db.php';

requireLogin();

$userId = currentUserId();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}


/*
|--------------------------------------------------------------------------
| GET ORDER ID
|--------------------------------------------------------------------------
*/

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId || $orderId <= 0) {
    header('Location: account.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| GET ORDER
|--------------------------------------------------------------------------
|
| IMPORTANT:
| The order must belong to the currently logged-in user.
|
*/

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
     WHERE id = ?
       AND user_id = ?
     LIMIT 1"
);

$orderStmt->bind_param("ii", $orderId, $userId);
$orderStmt->execute();

$orderResult = $orderStmt->get_result();
$order = $orderResult->fetch_assoc();

$orderStmt->close();


if (!$order) {
    header('Location: account.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| GET ORDER ITEMS
|--------------------------------------------------------------------------
*/

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
        ON oi.product_id = p.id
     WHERE oi.order_id = ?
     ORDER BY oi.id ASC"
);

$itemStmt->bind_param("i", $orderId);
$itemStmt->execute();

$itemResult = $itemStmt->get_result();

$items = [];

while ($item = $itemResult->fetch_assoc()) {
    $items[] = $item;
}

$itemStmt->close();


/*
|--------------------------------------------------------------------------
| LOGO
|--------------------------------------------------------------------------
*/

$logo = '';

foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {

    $file = __DIR__ . "/assets/logo.$ext";

    if (file_exists($file)) {
        $logo = "assets/logo.$ext";
        break;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Order #<?= e((string)$order['id']) ?>
        | Mimic Haven Collectibles
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<header class="site-header">

    <div class="header-inner">


        <a
            href="index.php"
            class="brand"
        >

            <?php if ($logo): ?>

                <img
                    src="<?= e($logo) ?>"
                    alt="Mimic Haven Collectibles"
                    class="brand-logo"
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


        <nav class="nav">

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


            <a
                href="cart.php"
                class="nav-icon cart-button"
                aria-label="Shopping cart"
            >

                🛒

                <span id="cart-count">
                    0
                </span>

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



<main class="account-page">


    <!-- HERO -->

    <section class="account-hero">

        <div class="account-container">

            <span class="account-eyebrow">
                MIMIC HAVEN COLLECTIBLES
            </span>

            <h1>
                Order <span>#<?= e((string)$order['id']) ?></span>
            </h1>

            <p>
                View the complete details of your order.
            </p>

        </div>

    </section>



    <!-- ORDER CONTENT -->

    <section class="account-content">

        <div class="account-container">


            <!-- BACK -->

            <div style="margin-bottom: 24px;">

                <a
                    href="account.php"
                    class="account-action secondary"
                >
                    ← Back to My Account
                </a>

            </div>



            <!-- ORDER SUMMARY -->

            <section class="account-card">

                <div class="account-card-header">

                    <div>

                        <span class="account-label">
                            ORDER #<?= e((string)$order['id']) ?>
                        </span>

                        <h2>
                            Order Details
                        </h2>

                    </div>


                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">

    <span class="account-order-status">
        <?= e($order['status']) ?>
    </span>

    <a
        href="order.php?id=<?= (int)$order['id'] ?>"
        class="account-action secondary"
    >
        View Details
    </a>

</div>
                </div>



                <!-- ORDER INFORMATION -->

                <div class="account-info">


                    <div class="account-info-row">

                        <span>
                            Order Date
                        </span>

                        <strong>
                            <?= e(
                                date(
                                    'F j, Y g:i A',
                                    strtotime($order['created_at'])
                                )
                            ) ?>
                        </strong>

                    </div>


                    <div class="account-info-row">

                        <span>
                            Payment Method
                        </span>

                        <strong>
                            <?= e($order['payment_method']) ?>
                        </strong>

                    </div>


                    <div class="account-info-row">

                        <span>
                            Order Status
                        </span>

                        <strong>
                            <?= e($order['status']) ?>
                        </strong>

                    </div>


                    <div class="account-info-row">

                        <span>
                            Total Amount
                        </span>

                        <strong>
                            ₱<?= number_format(
                                (float)$order['total_amount'],
                                2
                            ) ?>
                        </strong>

                    </div>


                </div>

            </section>



            <!-- PRODUCTS -->

            <section
                class="account-card"
                style="margin-top: 24px;"
            >

                <div class="account-card-header">

                    <div>

                        <span class="account-label">
                            PURCHASED ITEMS
                        </span>

                        <h2>
                            Your Figures
                        </h2>

                    </div>

                </div>



                <?php if (empty($items)): ?>

                    <div class="account-empty">

                        <div class="account-empty-icon">
                            ✦
                        </div>

                        <h3>
                            No items found
                        </h3>

                        <p>
                            The items for this order could not be found.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="account-orders">


                        <?php foreach ($items as $item): ?>


                            <article class="account-order">


                                <div class="account-order-item">


                                    <!-- IMAGE -->

                                    <div class="account-order-image">

                                        <?php if (!empty($item['image'])): ?>

                                            <?php

                                            $image = trim(
                                                (string)$item['image']
                                            );

                                            $image = preg_replace(
                                                '#^.*?assets/collections/#i',
                                                '',
                                                $image
                                            );

                                            $imagePath =
                                                'assets/collections/' .
                                                $image;

                                            ?>

                                            <img
                                                src="<?= e($imagePath) ?>"
                                                alt="<?= e(
                                                    $item['name']
                                                    ?? 'Product'
                                                ) ?>"
                                            >

                                        <?php else: ?>

                                            <div class="account-order-image-placeholder">
                                                ✦
                                            </div>

                                        <?php endif; ?>

                                    </div>



                                    <!-- DETAILS -->

                                    <div class="account-order-details">

                                        <strong>
                                            <?= e(
                                                $item['name']
                                                ?? 'Product'
                                            ) ?>
                                        </strong>

                                        <?php if (!empty($item['series'])): ?>

                                            <span>
                                                <?= e($item['series']) ?>
                                            </span>

                                        <?php endif; ?>

                                        <span>
                                            Quantity:
                                            <?= e(
                                                (string)$item['quantity']
                                            ) ?>
                                        </span>

                                    </div>



                                    <!-- PRICE -->

                                    <div class="account-order-price">

                                        ₱<?= number_format(
                                            (float)$item['price'],
                                            2
                                        ) ?>

                                    </div>


                                </div>


                            </article>


                        <?php endforeach; ?>


                    </div>

                <?php endif; ?>

            </section>



            <!-- DELIVERY -->

            <section
                class="account-card"
                style="margin-top: 24px;"
            >

                <div class="account-card-header">

                    <div>

                        <span class="account-label">
                            DELIVERY
                        </span>

                        <h2>
                            Shipping Information
                        </h2>

                    </div>

                </div>



                <div class="account-order-address">

                    <span>
                        Delivery Address
                    </span>

                    <p>
                        <?= nl2br(
                            e($order['delivery_address'])
                        ) ?>
                    </p>

                </div>



                <?php if (!empty($order['order_notes'])): ?>

                    <div
                        class="account-order-address"
                        style="margin-top: 20px;"
                    >

                        <span>
                            Order Notes
                        </span>

                        <p>
                            <?= nl2br(
                                e($order['order_notes'])
                            ) ?>
                        </p>

                    </div>

                <?php endif; ?>


            </section>



            <!-- TOTAL -->

            <section
                class="account-card"
                style="margin-top: 24px;"
            >

                <div class="account-order-footer">


                    <div>

                        <span>
                            Payment Method
                        </span>

                        <strong>
                            <?= e($order['payment_method']) ?>
                        </strong>

                    </div>


                    <div>

                        <span>
                            Order Total
                        </span>

                        <strong>
                            ₱<?= number_format(
                                (float)$order['total_amount'],
                                2
                            ) ?>
                        </strong>

                    </div>


                </div>

            </section>



            <!-- ACTION -->

            <section class="account-actions">

                <a
                    href="collection.php"
                    class="account-action secondary"
                >
                    Continue Shopping
                </a>

                <a
                    href="account.php"
                    class="account-action"
                >
                    Back to My Account
                </a>

            </section>


        </div>

    </section>

</main>



<script src="script.js"></script>

</body>

</html>