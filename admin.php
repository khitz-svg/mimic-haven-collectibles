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
| CHECK ADMIN ROLE
|--------------------------------------------------------------------------
*/

$roleStmt = $conn->prepare(
    "SELECT role
     FROM users
     WHERE id = ?
     LIMIT 1"
);

$roleStmt->bind_param("i", $userId);
$roleStmt->execute();

$roleResult = $roleStmt->get_result();
$currentUser = $roleResult->fetch_assoc();

$roleStmt->close();

if (!$currentUser || $currentUser['role'] !== 'admin') {

    http_response_code(403);

    die(
        '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Access Denied</title>

            <style>
                body {
                    background: #182637;
                    color: #fff;
                    font-family: Arial, sans-serif;
                    text-align: center;
                    padding: 100px 20px;
                }

                a {
                    color: #97DCF7;
                }
            </style>

        </head>

        <body>

            <h1>403</h1>

            <h2>Access Denied</h2>

            <p>
                You do not have administrator permission.
            </p>

            <a href="account.php">
                Back to My Account
            </a>

        </body>
        </html>'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

$productCount = 0;
$customerCount = 0;
$orderCount = 0;
$preorderCount = 0;


/*
|--------------------------------------------------------------------------
| PRODUCTS
|--------------------------------------------------------------------------
*/

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM products"
);

if ($result) {

    $row = $result->fetch_assoc();

    $productCount = (int)$row['total'];
}


/*
|--------------------------------------------------------------------------
| CUSTOMERS
|--------------------------------------------------------------------------
*/

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'customer'"
);

if ($result) {

    $row = $result->fetch_assoc();

    $customerCount = (int)$row['total'];
}


/*
|--------------------------------------------------------------------------
| ORDERS
|--------------------------------------------------------------------------
*/

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM orders"
);

if ($result) {

    $row = $result->fetch_assoc();

    $orderCount = (int)$row['total'];
}


/*
|--------------------------------------------------------------------------
| PRE-ORDERS
|--------------------------------------------------------------------------
|
| Count all active pre-orders.
| Cancelled pre-orders are excluded.
|
|--------------------------------------------------------------------------
*/

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM preorders
     WHERE status != 'Cancelled'"
);

if ($result) {

    $row = $result->fetch_assoc();

    $preorderCount = (int)$row['total'];
}


/*
|--------------------------------------------------------------------------
| RECENT ORDERS
|--------------------------------------------------------------------------
*/

$recentOrders = [];

$recentStmt = $conn->prepare(
    "SELECT
        o.id,
        o.total_amount,
        o.status,
        o.created_at,
        u.first_name,
        u.last_name
     FROM orders o
     INNER JOIN users u
        ON o.user_id = u.id
     ORDER BY o.created_at DESC
     LIMIT 5"
);

$recentStmt->execute();

$recentResult = $recentStmt->get_result();

while ($order = $recentResult->fetch_assoc()) {

    $recentOrders[] = $order;
}

$recentStmt->close();


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
        Admin Dashboard | Mimic Haven Collectibles
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

    <style>

        .admin-dashboard {
            padding: 70px 20px 100px;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .admin-welcome {
            margin-bottom: 40px;
        }

        .admin-eyebrow {
            display: block;
            color: var(--blue);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .admin-welcome h1 {
            margin: 0 0 10px;
            color: #fff;
        }

        .admin-welcome h1 span {
            color: var(--blue);
        }

        .admin-welcome p {
            color: #aab2ba;
            margin: 0;
        }

        .admin-stat-grid {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 35px;
        }

        .admin-stat {
            background: #191b1e;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 12px;
            padding: 24px;
        }

        .admin-stat-label {
            color: #89929c;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .admin-stat-number {
            display: block;
            margin-top: 10px;
            color: var(--blue);
            font-size: 32px;
            font-weight: 700;
        }

        .admin-menu {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 35px;
        }

        .admin-menu-card {
            display: block;
            background: #191b1e;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 12px;
            padding: 24px;
            text-decoration: none;
            transition:
                transform 0.2s ease,
                border-color 0.2s ease;
        }

        .admin-menu-card:hover {
            transform: translateY(-3px);
            border-color: var(--blue);
        }

        .admin-menu-icon {
            font-size: 26px;
            margin-bottom: 14px;
        }

        .admin-menu-card h3 {
            color: #fff;
            margin: 0 0 8px;
        }

        .admin-menu-card p {
            color: #89929c;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
        }

        .admin-recent {
            background: #191b1e;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 12px;
            padding: 26px;
        }

        .admin-recent-header {
            margin-bottom: 22px;
        }

        .admin-recent-header span {
            color: var(--blue);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .admin-recent-header h2 {
            margin: 6px 0 0;
            color: #fff;
        }

        .admin-recent-order {
            padding: 18px 0;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .admin-recent-order:first-child {
            border-top: none;
        }

        .admin-recent-order-top {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        .admin-recent-order strong {
            color: #fff;
        }

        .admin-recent-order p {
            color: #89929c;
            margin: 6px 0 0;
            font-size: 13px;
        }

        .admin-recent-status {
            color: var(--blue);
            font-size: 12px;
            font-weight: 700;
        }

        .admin-empty {
            color: #89929c;
            padding: 20px 0;
        }

        @media (max-width: 900px) {

            .admin-stat-grid,
            .admin-menu {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {

            .admin-stat-grid,
            .admin-menu {

                grid-template-columns: 1fr;
            }
        }

    </style>

</head>


<body>


<header class="site-header">

    <div class="header-inner">


        <!-- BRAND -->

        <a
            href="admin.php"
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



        <!-- NAVIGATION -->

        <nav class="nav">

            <a href="admin.php">
                Dashboard
            </a>

            <a href="admin_orders.php">
                Orders
            </a>

            <a href="admin_preorders.php">
                Pre-Orders
            </a>

            <a href="admin_customers.php">
                Customers
            </a>

            <a href="account.php">
                My Account
            </a>

            <a
                class="nav-account"
                href="logout.php"
            >
                Logout
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



<main class="admin-dashboard">

    <div class="admin-container">


        <!-- =====================================================
             WELCOME
        ====================================================== -->

        <section class="admin-welcome">

            <span class="admin-eyebrow">
                MIMIC HAVEN MANAGEMENT SYSTEM
            </span>

            <h1>
                Admin <span>Dashboard</span>
            </h1>

            <p>
                Welcome, <?= e(currentFirstName()) ?>.
                Manage your store from one place.
            </p>

        </section>



        <!-- =====================================================
             STATISTICS
        ====================================================== -->

        <section class="admin-stat-grid">


            <!-- PRODUCTS -->

            <div class="admin-stat">

                <span class="admin-stat-label">
                    Products
                </span>

                <strong class="admin-stat-number">
                    <?= $productCount ?>
                </strong>

            </div>



            <!-- CUSTOMERS -->

            <div class="admin-stat">

                <span class="admin-stat-label">
                    Customers
                </span>

                <strong class="admin-stat-number">
                    <?= $customerCount ?>
                </strong>

            </div>



            <!-- ORDERS -->

            <div class="admin-stat">

                <span class="admin-stat-label">
                    Orders
                </span>

                <strong class="admin-stat-number">
                    <?= $orderCount ?>
                </strong>

            </div>



            <!-- PRE-ORDERS -->

            <div class="admin-stat">

                <span class="admin-stat-label">
                    Pre-Orders
                </span>

                <strong class="admin-stat-number">
                    <?= $preorderCount ?>
                </strong>

            </div>


        </section>



        <!-- =====================================================
             MANAGEMENT MENU
        ====================================================== -->

        <section class="admin-menu">


            <!-- ORDERS -->

            <a
                href="admin_orders.php"
                class="admin-menu-card"
            >

                <div class="admin-menu-icon">
                    📦
                </div>

                <h3>
                    Manage Orders
                </h3>

                <p>
                    Review customer orders and update
                    their order status.
                </p>

            </a>



            <!-- PRODUCTS -->

            <a
    href="admin_products.php"
    class="admin-menu-card"
>
                <div class="admin-menu-icon">
                    🛍️
                </div>

                <h3>
                    Manage Products
                </h3>

                <p>
    Add, edit, classify, and manage collectible
    products and inventory.
</p>

            </a>



            <!-- CUSTOMERS -->

            <a
                href="admin_customers.php"
                class="admin-menu-card"
            >

                <div class="admin-menu-icon">
                    👥
                </div>

                <h3>
                    Manage Customers
                </h3>

                <p>
                    View customer profiles, pre-order
                    history, and reliability information.
                </p>

            </a>



            <!-- PRE-ORDERS -->

            <a
                href="admin_preorders.php"
                class="admin-menu-card"
            >

                <div class="admin-menu-icon">
                    ✦
                </div>

                <h3>
                    Pre-Orders
                </h3>

                <p>
                    Manage reservations, deposits,
                    balances, payments, and release progress.
                </p>

            </a>


        </section>



        <!-- =====================================================
             RECENT ORDERS
        ====================================================== -->

        <section class="admin-recent">


            <div class="admin-recent-header">

                <span>
                    RECENT ACTIVITY
                </span>

                <h2>
                    Recent Orders
                </h2>

            </div>



            <?php if (empty($recentOrders)): ?>

                <div class="admin-empty">

                    No orders have been placed yet.

                </div>

            <?php else: ?>


                <?php foreach ($recentOrders as $order): ?>


                    <div class="admin-recent-order">


                        <div class="admin-recent-order-top">


                            <strong>

                                Order #<?= (int)$order['id'] ?>

                            </strong>


                            <span class="admin-recent-status">

                                <?= e(
                                    $order['status']
                                ) ?>

                            </span>


                        </div>



                        <p>

                            <?= e(
                                $order['first_name']
                                . ' '
                                . $order['last_name']
                            ) ?>

                            &nbsp;•&nbsp;

                            ₱<?= number_format(
                                (float)$order['total_amount'],
                                2
                            ) ?>

                            &nbsp;•&nbsp;

                            <?= e(
                                date(
                                    'M j, Y',
                                    strtotime(
                                        $order['created_at']
                                    )
                                )
                            ) ?>

                        </p>


                    </div>


                <?php endforeach; ?>


            <?php endif; ?>


        </section>


    </div>

</main>



<script src="script.js"></script>

</body>

</html>