<?php

require_once 'auth.php';
require_once 'db.php';

requireLogin();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}


/*
|--------------------------------------------------------------------------
| CHECK ADMIN ACCESS
|--------------------------------------------------------------------------
*/

$userId = currentUserId();

$adminStmt = $conn->prepare(
    "SELECT role
     FROM users
     WHERE id = ?
     LIMIT 1"
);

$adminStmt->bind_param("i", $userId);
$adminStmt->execute();

$adminResult = $adminStmt->get_result();
$adminUser = $adminResult->fetch_assoc();

$adminStmt->close();

if (!$adminUser || $adminUser['role'] !== 'admin') {
    http_response_code(403);

    die(
        '<h1>403 Forbidden</h1>
         <p>You do not have permission to access this page.</p>
         <p><a href="account.php">Back to My Account</a></p>'
    );
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['admin_orders_csrf'])) {
    $_SESSION['admin_orders_csrf'] = bin2hex(
        random_bytes(32)
    );
}

$csrfToken = $_SESSION['admin_orders_csrf'];


/*
|--------------------------------------------------------------------------
| UPDATE ORDER STATUS
|--------------------------------------------------------------------------
*/

$message = '';
$messageType = '';

$allowedStatuses = [
    'Pending',
    'Confirmed',
    'Processing',
    'Shipped',
    'Completed',
    'Cancelled'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedToken = $_POST['csrf_token'] ?? '';

    if (
        !hash_equals(
            $csrfToken,
            $postedToken
        )
    ) {
        $message = 'Invalid security token.';
        $messageType = 'error';

    } else {

        $orderId = filter_input(
            INPUT_POST,
            'order_id',
            FILTER_VALIDATE_INT
        );

        $newStatus = trim(
            $_POST['status'] ?? ''
        );

        if (!$orderId || $orderId <= 0) {

            $message = 'Invalid order.';
            $messageType = 'error';

        } elseif (!in_array($newStatus, $allowedStatuses, true)) {

            $message = 'Invalid order status.';
            $messageType = 'error';

        } else {

            $updateStmt = $conn->prepare(
                "UPDATE orders
                 SET status = ?
                 WHERE id = ?"
            );

            $updateStmt->bind_param(
                "si",
                $newStatus,
                $orderId
            );

            if ($updateStmt->execute()) {

                $message = "Order #{$orderId} updated to {$newStatus}.";
                $messageType = 'success';

            } else {

                $message = 'Unable to update the order.';
                $messageType = 'error';
            }

            $updateStmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET ALL ORDERS
|--------------------------------------------------------------------------
*/

$orderStmt = $conn->prepare(
    "SELECT
        o.id,
        o.total_amount,
        o.payment_method,
        o.status,
        o.delivery_address,
        o.created_at,
        u.first_name,
        u.last_name,
        u.email
     FROM orders o
     INNER JOIN users u
        ON o.user_id = u.id
     ORDER BY o.created_at DESC"
);

$orderStmt->execute();

$orderResult = $orderStmt->get_result();

$orders = [];

while ($order = $orderResult->fetch_assoc()) {
    $orders[] = $order;
}

$orderStmt->close();


/*
|--------------------------------------------------------------------------
| LOGO
|--------------------------------------------------------------------------
*/

$logo = '';

foreach (
    ['png', 'jpg', 'jpeg', 'webp', 'svg']
    as $ext
) {

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
        Manage Orders | Mimic Haven Collectibles
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

    <style>

        .admin-page {
            padding: 60px 20px 100px;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .admin-header {
            margin-bottom: 30px;
        }

        .admin-header h1 {
            margin: 8px 0;
        }

        .admin-header h1 span {
            color: var(--blue);
        }

        .admin-header p {
            color: #aab2ba;
        }

        .admin-message {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .admin-message.success {
            background: rgba(39, 174, 96, 0.12);
            border: 1px solid rgba(39, 174, 96, 0.35);
            color: #7ee2a8;
        }

        .admin-message.error {
            background: rgba(231, 76, 60, 0.12);
            border: 1px solid rgba(231, 76, 60, 0.35);
            color: #ff9d91;
        }

        .admin-order-card {
            background: #191b1e;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .admin-order-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .admin-order-number {
            color: var(--blue);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
        }

        .admin-order-name {
            color: #fff;
            font-size: 20px;
            font-weight: 700;
            margin: 6px 0;
        }

        .admin-order-email {
            color: #aab2ba;
            font-size: 13px;
        }

        .admin-order-info {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .admin-order-info div {
            background: rgba(255,255,255,0.025);
            padding: 14px;
            border-radius: 8px;
        }

        .admin-order-info span {
            display: block;
            color: #89929c;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }

        .admin-order-info strong {
            color: #fff;
            font-size: 14px;
        }

        .admin-order-address {
            border-top: 1px solid rgba(255,255,255,0.06);
            padding-top: 18px;
            margin-top: 18px;
        }

        .admin-order-address span {
            display: block;
            color: #89929c;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }

        .admin-order-address p {
            margin: 0;
            color: #d8dde2;
            line-height: 1.6;
        }

        .admin-status-form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .admin-status-form label {
            color: #89929c;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .admin-status-form select {
            background: #101214;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 7px;
            padding: 10px 12px;
            outline: none;
        }

        .admin-status-form select:focus {
            border-color: var(--blue);
        }

        .admin-update-button {
            background: var(--blue);
            color: #101214;
            border: none;
            border-radius: 7px;
            padding: 10px 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .admin-update-button:hover {
            opacity: 0.9;
        }

        .admin-back {
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {

            .admin-order-info {
                grid-template-columns: 1fr;
            }

        }

    </style>

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



<main class="admin-page">

    <div class="admin-container">


        <div class="admin-back">

            <a
                href="account.php"
                class="account-action secondary"
            >
                ← My Account
            </a>

        </div>



        <div class="admin-header">

            <span class="account-eyebrow">
                MIMIC HAVEN COLLECTIBLES
            </span>

            <h1>
                Manage <span>Orders</span>
            </h1>

            <p>
                Review customer orders and update their status.
            </p>

        </div>



        <?php if ($message): ?>

            <div
                class="admin-message <?= e($messageType) ?>"
            >
                <?= e($message) ?>
            </div>

        <?php endif; ?>



        <?php if (empty($orders)): ?>

            <section class="account-card">

                <div class="account-empty">

                    <div class="account-empty-icon">
                        ✦
                    </div>

                    <h3>
                        No orders yet
                    </h3>

                    <p>
                        Customer orders will appear here once
                        they place an order.
                    </p>

                </div>

            </section>

        <?php else: ?>


            <?php foreach ($orders as $order): ?>


                <article class="admin-order-card">


                    <div class="admin-order-top">

                        <div>

                            <div class="admin-order-number">
                                ORDER #<?= (int)$order['id'] ?>
                            </div>

                            <div class="admin-order-name">

                                <?= e(
                                    $order['first_name']
                                    . ' '
                                    . $order['last_name']
                                ) ?>

                            </div>

                            <div class="admin-order-email">

                                <?= e($order['email']) ?>

                            </div>

                        </div>

                    </div>



                    <div class="admin-order-info">


                        <div>

                            <span>
                                Order Date
                            </span>

                            <strong>

                                <?= e(
                                    date(
                                        'F j, Y g:i A',
                                        strtotime(
                                            $order['created_at']
                                        )
                                    )
                                ) ?>

                            </strong>

                        </div>


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



                    <div class="admin-order-address">

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



                    <form
                        method="POST"
                        class="admin-status-form"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($csrfToken) ?>"
                        >

                        <input
                            type="hidden"
                            name="order_id"
                            value="<?= (int)$order['id'] ?>"
                        >


                        <label for="status-<?= (int)$order['id'] ?>">
                            Status
                        </label>


                        <select
                            id="status-<?= (int)$order['id'] ?>"
                            name="status"
                        >

                            <?php foreach (
                                $allowedStatuses
                                as $status
                            ): ?>

                                <option
                                    value="<?= e($status) ?>"
                                    <?= $order['status'] === $status
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= e($status) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>


                        <button
                            type="submit"
                            class="admin-update-button"
                        >
                            Update Status
                        </button>

                    </form>


                </article>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>

</main>



<script src="script.js"></script>

</body>

</html>