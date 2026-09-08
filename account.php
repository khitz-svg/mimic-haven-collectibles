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
| GET CURRENT USER
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT id, first_name, last_name, email, created_at
     FROM users
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (!$user) {
    logoutUser();

    header('Location: login.php');
    exit;
}

$fullName = $user['first_name'] . ' ' . $user['last_name'];


/*
|--------------------------------------------------------------------------
| GET USER ORDERS
|--------------------------------------------------------------------------
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
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$orderStmt->bind_param("i", $userId);
$orderStmt->execute();

$orderResult = $orderStmt->get_result();

$orders = [];

while ($order = $orderResult->fetch_assoc()) {
    $orders[] = $order;
}

$orderStmt->close();


/*
|--------------------------------------------------------------------------
| GET ITEMS FOR EACH ORDER
|--------------------------------------------------------------------------
*/

$orderItems = [];

if (!empty($orders)) {

    $itemStmt = $conn->prepare(
        "SELECT
            oi.order_id,
            oi.product_id,
            oi.quantity,
            oi.price,
            p.name,
            p.image
         FROM order_items oi
         LEFT JOIN products p
            ON oi.product_id = p.id
         WHERE oi.order_id = ?
         ORDER BY oi.id ASC"
    );

    foreach ($orders as $order) {

        $orderId = (int)$order['id'];

        $itemStmt->bind_param("i", $orderId);
        $itemStmt->execute();

        $itemResult = $itemStmt->get_result();

        $orderItems[$orderId] = [];

        while ($item = $itemResult->fetch_assoc()) {
            $orderItems[$orderId][] = $item;
        }
    }

    $itemStmt->close();
}


/*
|--------------------------------------------------------------------------
| LOGO HELPER
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

    <title>My Account | Mimic Haven Collectibles</title>

    <link rel="stylesheet" href="style.css">

</head>


<body>


<header class="site-header">

    <div class="header-inner">


        <a href="index.php" class="brand">

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
                Hi, <?= e($user['first_name']) ?>
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
                My <span>Account</span>
            </h1>

            <p>
                Manage your customer information and keep track of your
                Mimic Haven experience.
            </p>

        </div>

    </section>



    <!-- CONTENT -->

    <section class="account-content">

        <div class="account-container">


            <div class="account-grid">


                <!-- PROFILE -->

                <section class="account-card">

                    <div class="account-card-header">

                        <div>

                            <span class="account-label">
                                CUSTOMER PROFILE
                            </span>

                            <h2>
                                Personal Information
                            </h2>

                        </div>

                    </div>


                    <div class="account-info">


                        <div class="account-info-row">

                            <span>
                                Full Name
                            </span>

                            <strong>
                                <?= e($fullName) ?>
                            </strong>

                        </div>


                        <div class="account-info-row">

                            <span>
                                Email Address
                            </span>

                            <strong>
                                <?= e($user['email']) ?>
                            </strong>

                        </div>


                        <div class="account-info-row">

                            <span>
                                Account ID
                            </span>

                            <strong>
                                #<?= e((string)$user['id']) ?>
                            </strong>

                        </div>


                        <div class="account-info-row">

                            <span>
                                Member Since
                            </span>

                            <strong>
                                <?= e(
                                    date(
                                        'F j, Y',
                                        strtotime($user['created_at'])
                                    )
                                ) ?>
                            </strong>

                        </div>


                    </div>

                </section>



                <!-- ORDERS -->

                <section class="account-card">

                    <div class="account-card-header">

                        <div>

                            <span class="account-label">
                                YOUR ACTIVITY
                            </span>

                            <h2>
                                Orders &amp; Pre-Orders
                            </h2>

                        </div>

                    </div>



                    <?php if (empty($orders)): ?>


                        <div class="account-empty">

                            <div class="account-empty-icon">
                                ✦
                            </div>

                            <h3>
                                No orders yet
                            </h3>

                            <p>
                                Once you place an order or pre-order,
                                your order history will be displayed here.
                            </p>

                            <a
                                href="collection.php"
                                class="account-action"
                            >
                                Browse Figures
                            </a>

                        </div>


                    <?php else: ?>


                        <div class="account-orders">


                            <?php foreach ($orders as $order): ?>

                                <?php
                                $orderId = (int)$order['id'];
                                $items = $orderItems[$orderId] ?? [];
                                ?>


                                <article class="account-order">


                                    <!-- ORDER HEADER -->

                                    <div class="account-order-header">

                                        <div>

                                            <span class="account-label">
                                                ORDER #<?= $orderId ?>
                                            </span>

                                            <h3>
                                                <?= e(
                                                    date(
                                                        'F j, Y',
                                                        strtotime($order['created_at'])
                                                    )
                                                ) ?>
                                            </h3>

                                        </div>


                                        <span class="account-order-status">

                                            <?= e($order['status']) ?>

                                        </span>

                                    </div>



                                    <!-- ORDER ITEMS -->

                                    <div class="account-order-items">


                                        <?php foreach ($items as $item): ?>


                                            <div class="account-order-item">


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
                                                            alt="<?= e($item['name'] ?? 'Product') ?>"
                                                        >

                                                    <?php else: ?>

                                                        <div class="account-order-image-placeholder">
                                                            ✦
                                                        </div>

                                                    <?php endif; ?>

                                                </div>



                                                <div class="account-order-details">

                                                    <strong>
                                                        <?= e(
                                                            $item['name']
                                                            ?? 'Product'
                                                        ) ?>
                                                    </strong>

                                                    <span>
                                                        Qty:
                                                        <?= e(
                                                            (string)$item['quantity']
                                                        ) ?>
                                                    </span>

                                                </div>



                                                <div class="account-order-price">

                                                    ₱<?= number_format(
                                                        (float)$item['price'],
                                                        2
                                                    ) ?>

                                                </div>


                                            </div>


                                        <?php endforeach; ?>


                                    </div>



                                    <!-- ORDER FOOTER -->

                                    <div class="account-order-footer">


                                        <div>

                                            <span>
                                                Payment
                                            </span>

                                            <strong>
                                                <?= e(
                                                    $order['payment_method']
                                                ) ?>
                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                Total
                                            </span>

                                            <strong>
                                                ₱<?= number_format(
                                                    (float)$order['total_amount'],
                                                    2
                                                ) ?>
                                            </strong>

                                        </div>


                                    </div>



                                    <!-- DELIVERY ADDRESS -->

                                    <div class="account-order-address">

                                        <span>
                                            Delivery Address
                                        </span>

                                        <p>
                                            <?= nl2br(
                                                e(
                                                    $order['delivery_address']
                                                )
                                            ) ?>
                                        </p>

                                    </div>


                                    <?php if (!empty($order['order_notes'])): ?>

                                        <div class="account-order-address">

                                            <span>
                                                Order Notes
                                            </span>

                                            <p>
                                                <?= nl2br(
                                                    e(
                                                        $order['order_notes']
                                                    )
                                                ) ?>
                                            </p>

                                        </div>

                                    <?php endif; ?>


                                </article>


                            <?php endforeach; ?>


                        </div>


                    <?php endif; ?>


                </section>


            </div>



            <!-- ACCOUNT ACTIONS -->

            <section class="account-actions">

                <a
                    href="collection.php"
                    class="account-action secondary"
                >
                    Continue Shopping
                </a>


                <a
                    href="logout.php"
                    class="account-action danger"
                >
                    Logout
                </a>

            </section>


        </div>

    </section>

</main>



<script src="script.js"></script>


</body>

</html>