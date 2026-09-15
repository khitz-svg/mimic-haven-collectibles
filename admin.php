<?php

require_once 'auth.php';
require_once 'db.php';

requireLogin();

$userId = currentUserId();


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
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

$roleStmt->bind_param(
    "i",
    $userId
);

$roleStmt->execute();

$roleResult =
    $roleStmt->get_result();

$currentUser =
    $roleResult->fetch_assoc();

$roleStmt->close();


if (
    !$currentUser ||
    $currentUser['role'] !== 'admin'
) {

    http_response_code(403);

    die(
        '<!DOCTYPE html>
        <html lang="en">

        <head>

            <meta charset="UTF-8">

            <meta
                name="viewport"
                content="width=device-width, initial-scale=1.0"
            >

            <title>
                Access Denied | Mimic Haven Collectibles
            </title>

            <style>

                * {
                    box-sizing: border-box;
                }

                body {
                    margin: 0;
                    min-height: 100vh;

                    background: #182637;
                    color: #ffffff;

                    font-family:
                        Arial,
                        sans-serif;

                    display: flex;
                    align-items: center;
                    justify-content: center;

                    text-align: center;

                    padding: 30px;
                }

                .access-denied {
                    width: 100%;
                    max-width: 600px;
                }

                .access-denied h1 {
                    margin: 0 0 10px;

                    font-size: 64px;

                    color: #ffffff;
                }

                .access-denied h2 {
                    margin: 0 0 18px;

                    font-size: 28px;

                    color: #ffffff;
                }

                .access-denied p {
                    margin: 0 0 25px;

                    color: #aab2ba;

                    font-size: 15px;

                    line-height: 1.6;
                }

                .access-denied a {
                    display: inline-block;

                    color: #97DCF7;

                    text-decoration: none;

                    font-weight: 600;

                    font-size: 14px;
                }

                .access-denied a:hover {
                    text-decoration: underline;
                }

            </style>

        </head>

        <body>

            <div class="access-denied">

                <h1>
                    403
                </h1>

                <h2>
                    Access Denied
                </h2>

                <p>
                    You do not have administrator permission.
                </p>

                <a href="admin.php">
                    Back to Admin Dashboard
                </a>

            </div>

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

$result =
    $conn->query(
        "SELECT COUNT(*) AS total
         FROM products"
    );

if ($result) {

    $row =
        $result->fetch_assoc();

    $productCount =
        (int)$row['total'];
}


/*
|--------------------------------------------------------------------------
| CUSTOMERS
|--------------------------------------------------------------------------
*/

$result =
    $conn->query(
        "SELECT COUNT(*) AS total
         FROM users
         WHERE role = 'customer'"
    );

if ($result) {

    $row =
        $result->fetch_assoc();

    $customerCount =
        (int)$row['total'];
}


/*
|--------------------------------------------------------------------------
| ORDERS
|--------------------------------------------------------------------------
*/

$result =
    $conn->query(
        "SELECT COUNT(*) AS total
         FROM orders"
    );

if ($result) {

    $row =
        $result->fetch_assoc();

    $orderCount =
        (int)$row['total'];
}


/*
|--------------------------------------------------------------------------
| PRE-ORDERS
|--------------------------------------------------------------------------
|
| Cancelled pre-orders are excluded.
|
*/

$result =
    $conn->query(
        "SELECT COUNT(*) AS total
         FROM preorders
         WHERE status != 'Cancelled'"
    );

if ($result) {

    $row =
        $result->fetch_assoc();

    $preorderCount =
        (int)$row['total'];
}


/*
|--------------------------------------------------------------------------
| RECENT ORDERS
|--------------------------------------------------------------------------
*/

$recentOrders = [];

$recentStmt =
    $conn->prepare(
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

$recentResult =
    $recentStmt->get_result();

while (
    $order =
        $recentResult->fetch_assoc()
) {

    $recentOrders[] =
        $order;
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

    $file =
        __DIR__ . "/assets/logo.$ext";

    if (
        file_exists($file)
    ) {

        $logo =
            "assets/logo.$ext";

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

/* =========================================================
   ADMIN PAGE
========================================================= */

body {
    margin: 0;

    background: #182637;

    color: #ffffff;

    font-family:
        Arial,
        sans-serif;
}


.admin-container {
    width:
        min(
            1550px,
            calc(100% - 60px)
        );

    margin: 0 auto;

    padding:
        30px 0 80px;
}


/* =========================================================
   ADMIN HEADER
========================================================= */

.admin-header {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        auto;

    align-items: center;

    gap: 30px;

    margin-bottom: 30px;
}


.admin-header-title {
    min-width: 0;
}


.admin-header-title h1 {
    margin: 0 0 8px;

    color: #ffffff;

    font-size: 30px;

    line-height: 1.15;
}


.admin-header-title p {
    margin: 0;

    color: #aab2ba;

    font-size: 13px;

    line-height: 1.6;
}


/* =========================================================
   ADMIN NAVIGATION
========================================================= */

.admin-nav {
    display: flex;

    align-items: center;

    justify-content: flex-end;

    gap: 10px;

    flex-wrap: nowrap;
}


.admin-nav a {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 38px;

    padding:
        10px 16px;

    border-radius: 8px;

    background: #9ADCF7;

    color: #182637;

    font-family:
        Arial,
        sans-serif;

    font-size: 13px;

    font-weight: bold;

    text-decoration: none;

    white-space: nowrap;

    transition:
        opacity .2s ease,
        transform .2s ease;
}


.admin-nav a:hover {
    opacity: .85;

    transform:
        translateY(-1px);
}


.admin-nav a.active {
    background: #ffffff;

    color: #182637;
}


/* =========================================================
   STATISTICS
========================================================= */

.admin-stat-grid {
    display: grid;

    grid-template-columns:
        repeat(
            4,
            minmax(0, 1fr)
        );

    gap: 18px;

    margin-bottom: 30px;
}


.admin-stat {
    background: #ffffff;

    border-radius: 10px;

    padding: 22px;
}


.admin-stat-label {
    display: block;

    color: #697681;

    font-size: 10px;

    font-weight: bold;

    letter-spacing: 1px;

    text-transform: uppercase;
}


.admin-stat-number {
    display: block;

    margin-top: 9px;

    color: #182637;

    font-size: 32px;

    font-weight: bold;
}


/* =========================================================
   MANAGEMENT MENU
========================================================= */

.admin-menu {
    display: grid;

    grid-template-columns:
        repeat(
            4,
            minmax(0, 1fr)
        );

    gap: 18px;

    margin-bottom: 30px;
}


.admin-menu-card {
    display: block;

    background: #ffffff;

    border-radius: 10px;

    padding: 22px;

    text-decoration: none;

    transition:
        transform .2s ease,
        box-shadow .2s ease;
}


.admin-menu-card:hover {
    transform:
        translateY(-3px);

    box-shadow:
        0 10px 25px
        rgba(0,0,0,.18);
}


.admin-menu-icon {
    width: 46px;
    height: 46px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 14px;

    border-radius: 8px;

    background: #e8f8fd;

    font-size: 20px;
}


.admin-menu-card h3 {
    margin: 0 0 8px;

    color: #182637;

    font-size: 14px;
}


.admin-menu-card p {
    margin: 0;

    color: #697681;

    font-size: 11px;

    line-height: 1.6;
}


/* =========================================================
   RECENT ORDERS
========================================================= */

.admin-recent {
    background: #ffffff;

    border-radius: 10px;

    padding: 25px;
}


.admin-recent-header {
    margin-bottom: 18px;
}


.admin-recent-header span {
    display: block;

    margin-bottom: 6px;

    color: #6c7882;

    font-size: 9px;

    font-weight: bold;

    letter-spacing: 1.5px;
}


.admin-recent-header h2 {
    margin: 0;

    color: #182637;

    font-size: 20px;
}


.admin-recent-order {
    padding:
        16px 0;

    border-top:
        1px solid #e1e5e8;
}


.admin-recent-order:first-child {
    border-top: none;
}


.admin-recent-order-top {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;
}


.admin-recent-order strong {
    color: #182637;

    font-size: 12px;
}


.admin-recent-order p {
    margin: 6px 0 0;

    color: #697681;

    font-size: 10px;

    line-height: 1.5;
}


.admin-recent-status {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding:
        5px 9px;

    border-radius: 20px;

    background: #e8f8fd;

    color: #365d6c;

    font-size: 9px;

    font-weight: bold;

    white-space: nowrap;
}


.admin-empty {
    padding:
        20px 0;

    color: #697681;

    font-size: 11px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {

    .admin-header {
        grid-template-columns: 1fr;
    }

    .admin-nav {
        justify-content: flex-start;

        flex-wrap: wrap;
    }

}


@media (max-width: 900px) {

    .admin-stat-grid,
    .admin-menu {

        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }

}


@media (max-width: 600px) {

    .admin-container {
        width:
            calc(100% - 30px);

        padding:
            20px 0 60px;
    }


    .admin-stat-grid,
    .admin-menu {

        grid-template-columns: 1fr;
    }


    .admin-nav {
        gap: 7px;
    }


    .admin-nav a {
        min-height: 36px;

        padding:
            9px 12px;

        font-size: 11px;
    }


    .admin-recent-order-top {
        align-items: flex-start;

        flex-direction: column;
    }

}

    </style>

</head>


<body>


<div class="admin-container">


    <!-- =====================================================
         ADMIN HEADER
    ====================================================== -->

    <div class="admin-header">


        <div class="admin-header-title">

            <h1>
                Admin Dashboard
            </h1>


            <p>
                Welcome,
                <?= e(currentFirstName()) ?>.
                Manage your store from one place.
            </p>

        </div>


        <nav
            class="admin-nav"
            aria-label="Admin navigation"
        >

            <a
                href="admin.php"
                class="active"
            >
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


            <a href="admin_products.php">
                Products
            </a>


            <a href="logout.php">
                Logout
            </a>

        </nav>


    </div>



    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <section class="admin-stat-grid">


        <div class="admin-stat">

            <span class="admin-stat-label">
                Products
            </span>


            <strong class="admin-stat-number">
                <?= $productCount ?>
            </strong>

        </div>


        <div class="admin-stat">

            <span class="admin-stat-label">
                Customers
            </span>


            <strong class="admin-stat-number">
                <?= $customerCount ?>
            </strong>

        </div>


        <div class="admin-stat">

            <span class="admin-stat-label">
                Orders
            </span>


            <strong class="admin-stat-number">
                <?= $orderCount ?>
            </strong>

        </div>


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
                Add, edit, classify, and manage
                collectible products and inventory.
            </p>

        </a>



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



        <?php if (
            empty($recentOrders)
        ): ?>

            <div class="admin-empty">

                No orders have been placed yet.

            </div>

        <?php else: ?>


            <?php foreach (
                $recentOrders
                as $order
            ): ?>


                <div class="admin-recent-order">


                    <div
                        class="admin-recent-order-top"
                    >

                        <strong>
                            Order
                            #<?= (int)$order['id'] ?>
                        </strong>


                        <span
                            class="
                                admin-recent-status
                            "
                        >
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
                            (float)$order[
                                'total_amount'
                            ],
                            2
                        ) ?>


                        &nbsp;•&nbsp;


                        <?= e(
                            date(
                                'M j, Y',
                                strtotime(
                                    $order[
                                        'created_at'
                                    ]
                                )
                            )
                        ) ?>

                    </p>


                </div>


            <?php endforeach; ?>


        <?php endif; ?>


    </section>


</div>


</body>

</html>