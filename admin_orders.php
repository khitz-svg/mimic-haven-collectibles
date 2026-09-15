<?php

require_once 'auth.php';
require_once 'db.php';

requireLogin();


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

$adminStmt->bind_param(
    "i",
    $userId
);

$adminStmt->execute();

$adminResult = $adminStmt->get_result();

$adminUser = $adminResult->fetch_assoc();

$adminStmt->close();


if (
    !$adminUser ||
    $adminUser['role'] !== 'admin'
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
                    font-family: Arial, sans-serif;

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
                }

                .access-denied h2 {
                    margin: 0 0 18px;
                    font-size: 28px;
                }

                .access-denied p {
                    margin: 0 0 25px;
                    color: #aab2ba;
                    font-size: 15px;
                    line-height: 1.6;
                }

                .access-denied a {
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

                <h1>403</h1>

                <h2>Access Denied</h2>

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
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (
    empty(
        $_SESSION['admin_orders_csrf']
    )
) {

    $_SESSION['admin_orders_csrf'] =
        bin2hex(
            random_bytes(32)
        );
}

$csrfToken =
    $_SESSION['admin_orders_csrf'];


/*
|--------------------------------------------------------------------------
| ORDER STATUSES
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    'Pending',
    'Confirmed',
    'Processing',
    'Shipped',
    'Completed',
    'Cancelled'
];


$message = '';

$messageType = '';


/*
|--------------------------------------------------------------------------
| HANDLE ORDER STATUS UPDATE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $postedToken =
        $_POST['csrf_token'] ?? '';


    if (
        !is_string($postedToken) ||
        !hash_equals(
            $csrfToken,
            $postedToken
        )
    ) {

        $message =
            'Invalid security token.';

        $messageType =
            'error';

    } else {

        $orderId =
            filter_input(
                INPUT_POST,
                'order_id',
                FILTER_VALIDATE_INT
            );


        $newStatus =
            trim(
                $_POST['status'] ?? ''
            );


        if (
            !$orderId ||
            $orderId <= 0
        ) {

            $message =
                'Invalid order.';

            $messageType =
                'error';

        } elseif (
            !in_array(
                $newStatus,
                $allowedStatuses,
                true
            )
        ) {

            $message =
                'Invalid order status.';

            $messageType =
                'error';

        } else {

            $updateStmt =
                $conn->prepare(
                    "UPDATE orders
                     SET status = ?
                     WHERE id = ?"
                );


            $updateStmt->bind_param(
                "si",
                $newStatus,
                $orderId
            );


            if (
                $updateStmt->execute()
            ) {

                $message =
                    "Order #{$orderId} updated to {$newStatus}.";

                $messageType =
                    'success';

            } else {

                $message =
                    'Unable to update the order.';

                $messageType =
                    'error';
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

$orderStmt =
    $conn->prepare(
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


$orderResult =
    $orderStmt->get_result();


$orders = [];


while (
    $order =
        $orderResult->fetch_assoc()
) {

    $orders[] =
        $order;
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
        Manage Orders | Mimic Haven Collectibles
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

    font-family: Arial, sans-serif;
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
   HEADER
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

    font-family:
        Montserrat,
        sans-serif;

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
   BACK LINK
========================================================= */

.admin-back {
    margin-bottom: 24px;
}


.admin-back a {
    color: #9ADCF7;

    font-size: 13px;

    font-weight: 600;

    text-decoration: none;
}


.admin-back a:hover {
    text-decoration: underline;
}


/* =========================================================
   PAGE HEADING
========================================================= */

.admin-page-header {
    margin-bottom: 25px;
}


.admin-page-eyebrow {
    display: block;

    margin-bottom: 9px;

    color: #97DCF7;

    font-size: 10px;

    font-weight: 800;

    letter-spacing: 2px;
}


.admin-page-header h2 {
    margin: 0 0 9px;

    color: #ffffff;

    font-family:
        Montserrat,
        sans-serif;

    font-size:
        clamp(
            30px,
            5vw,
            44px
        );

    line-height: 1;

    letter-spacing: -1px;
}


.admin-page-header h2 span {
    color: #97DCF7;
}


.admin-page-header p {
    margin: 0;

    color: #aab2ba;

    font-size: 11px;

    line-height: 1.6;
}


/* =========================================================
   MESSAGES
========================================================= */

.admin-message {
    padding:
        14px 18px;

    border-radius: 8px;

    margin-bottom: 24px;

    font-size: 13px;

    line-height: 1.5;
}


.admin-message.success {
    background:
        rgba(39,174,96,.12);

    border:
        1px solid rgba(39,174,96,.35);

    color: #7ee2a8;
}


.admin-message.error {
    background:
        rgba(231,76,60,.12);

    border:
        1px solid rgba(231,76,60,.35);

    color: #ff9d91;
}


/* =========================================================
   ORDER CARD
========================================================= */

.admin-order-card {
    background: #ffffff;

    color: #182637;

    border-radius: 12px;

    padding: 24px;

    margin-bottom: 20px;
}


.admin-order-top {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 20px;

    flex-wrap: wrap;

    margin-bottom: 20px;
}


/* =========================================================
   ORDER HEADER
========================================================= */

.admin-order-number {
    color: #416b7b;

    font-size: 10px;

    font-weight: 800;

    letter-spacing: 1.5px;
}


.admin-order-name {
    margin: 6px 0;

    color: #182637;

    font-family:
        Montserrat,
        sans-serif;

    font-size: 20px;

    font-weight: 700;
}


.admin-order-email {
    color: #687681;

    font-size: 12px;
}


/* =========================================================
   STATUS BADGE
========================================================= */

.admin-order-status {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding:
        6px 10px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 700;

    white-space: nowrap;
}


.admin-order-status.pending {
    background: #e8edf2;

    color: #364552;
}


.admin-order-status.confirmed {
    background: #e5f5fb;

    color: #356173;
}


.admin-order-status.processing {
    background: #e8f3fa;

    color: #315a70;
}


.admin-order-status.shipped {
    background: #e2f5ec;

    color: #266542;
}


.admin-order-status.completed {
    background: #d9f7df;

    color: #1f5d2c;
}


.admin-order-status.cancelled {
    background: #ffdede;

    color: #8a1f1f;
}


/* =========================================================
   ORDER INFORMATION
========================================================= */

.admin-order-info {
    display: grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0, 1fr)
        );

    gap: 12px;

    margin-bottom: 20px;
}


.admin-order-info > div {
    background: #f2f7f9;

    padding: 14px;

    border-radius: 8px;
}


.admin-order-info span {
    display: block;

    margin-bottom: 6px;

    color: #6d7882;

    font-size: 9px;

    font-weight: 700;

    letter-spacing: .8px;

    text-transform: uppercase;
}


.admin-order-info strong {
    color: #182637;

    font-size: 13px;
}


/* =========================================================
   ADDRESS
========================================================= */

.admin-order-address {
    border-top:
        1px solid #e1e6e9;

    padding-top: 18px;

    margin-top: 18px;
}


.admin-order-address span {
    display: block;

    margin-bottom: 8px;

    color: #6d7882;

    font-size: 9px;

    font-weight: 700;

    letter-spacing: .8px;

    text-transform: uppercase;
}


.admin-order-address p {
    margin: 0;

    color: #364552;

    font-size: 12px;

    line-height: 1.6;
}


/* =========================================================
   STATUS FORM
========================================================= */

.admin-status-form {
    display: flex;

    align-items: center;

    gap: 10px;

    flex-wrap: wrap;

    margin-top: 20px;

    padding-top: 20px;

    border-top:
        1px solid #e1e6e9;
}


.admin-status-form label {
    color: #6d7882;

    font-size: 9px;

    font-weight: 700;

    letter-spacing: .8px;

    text-transform: uppercase;
}


.admin-status-form select {
    min-height: 36px;

    padding:
        8px 12px;

    border:
        1px solid #cfd8dd;

    border-radius: 7px;

    outline: none;

    background: #ffffff;

    color: #182637;

    font-family:
        Arial,
        sans-serif;

    font-size: 11px;
}


.admin-status-form select:focus {
    border-color:
        #97DCF7;
}


.admin-update-button {
    min-height: 36px;

    padding:
        8px 16px;

    border: none;

    border-radius: 7px;

    background: #182637;

    color: #ffffff;

    font-family:
        Arial,
        sans-serif;

    font-size: 11px;

    font-weight: 700;

    cursor: pointer;
}


.admin-update-button:hover {
    opacity: .88;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.admin-empty {
    background: #ffffff;

    color: #182637;

    border-radius: 12px;

    padding: 60px 25px;

    text-align: center;
}


.admin-empty-icon {
    width: 50px;
    height: 50px;

    margin:
        0 auto 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: #e8f8fd;

    color: #416b7b;

    font-size: 20px;
}


.admin-empty h3 {
    margin: 0 0 8px;

    font-family:
        Montserrat,
        sans-serif;

    font-size: 18px;
}


.admin-empty p {
    margin: 0;

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


@media (max-width: 800px) {

    .admin-order-info {
        grid-template-columns: 1fr;
    }

}


@media (max-width: 600px) {

    .admin-container {
        width:
            calc(100% - 30px);

        padding:
            20px 0 60px;
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


    .admin-order-card {
        padding: 18px;
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
                Manage Orders
            </h1>


            <p>
                Review customer orders and update their status.
            </p>

        </div>


        <nav
            class="admin-nav"
            aria-label="Admin navigation"
        >

            <a href="admin.php">
                Dashboard
            </a>


            <a
                href="admin_orders.php"
                class="active"
            >
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
         BACK LINK
    ====================================================== -->

    <div class="admin-back">

        <a href="admin.php">
            ← Back to Admin Dashboard
        </a>

    </div>



    <!-- =====================================================
         PAGE DESCRIPTION
    ====================================================== -->

    <section class="admin-page-header">

        <span class="admin-page-eyebrow">
            MIMIC HAVEN MANAGEMENT SYSTEM
        </span>


        <h2>
            Order <span>Management</span>
        </h2>


        <p>
            View customer orders, payment details,
            delivery addresses, and order status.
        </p>

    </section>



    <!-- =====================================================
         MESSAGE
    ====================================================== -->

    <?php if ($message): ?>

        <div
            class="
                admin-message
                <?= e($messageType) ?>
            "
        >

            <?= e($message) ?>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         ORDERS
    ====================================================== -->

    <?php if (empty($orders)): ?>


        <section class="admin-empty">


            <div class="admin-empty-icon">
                ✦
            </div>


            <h3>
                No Orders Yet
            </h3>


            <p>
                Customer orders will appear here once
                they place an order.
            </p>


        </section>


    <?php else: ?>


        <?php foreach (
            $orders
            as $order
        ): ?>


            <article
                class="admin-order-card"
            >


                <!-- =========================================
                     ORDER HEADER
                ========================================== -->

                <div
                    class="admin-order-top"
                >


                    <div>


                        <div
                            class="admin-order-number"
                        >
                            ORDER
                            #<?= (int)$order['id'] ?>
                        </div>


                        <div
                            class="admin-order-name"
                        >

                            <?= e(
                                $order['first_name']
                                . ' '
                                . $order['last_name']
                            ) ?>

                        </div>


                        <div
                            class="admin-order-email"
                        >

                            <?= e(
                                $order['email']
                            ) ?>

                        </div>


                    </div>



                    <?php
                    $statusClass = 'pending';

                    switch (
                        $order['status']
                    ) {

                        case 'Confirmed':
                            $statusClass = 'confirmed';
                            break;

                        case 'Processing':
                            $statusClass = 'processing';
                            break;

                        case 'Shipped':
                            $statusClass = 'shipped';
                            break;

                        case 'Completed':
                            $statusClass = 'completed';
                            break;

                        case 'Cancelled':
                            $statusClass = 'cancelled';
                            break;
                    }
                    ?>


                    <span
                        class="
                            admin-order-status
                            <?= e(
                                $statusClass
                            ) ?>
                        "
                    >

                        <?= e(
                            $order['status']
                        ) ?>

                    </span>


                </div>



                <!-- =========================================
                     ORDER INFORMATION
                ========================================== -->

                <div
                    class="admin-order-info"
                >


                    <div>

                        <span>
                            Order Date
                        </span>


                        <strong>

                            <?= e(
                                date(
                                    'F j, Y g:i A',
                                    strtotime(
                                        $order[
                                            'created_at'
                                        ]
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
                                $order[
                                    'payment_method'
                                ]
                            ) ?>

                        </strong>

                    </div>



                    <div>

                        <span>
                            Total
                        </span>


                        <strong>

                            ₱<?= number_format(
                                (float)$order[
                                    'total_amount'
                                ],
                                2
                            ) ?>

                        </strong>

                    </div>


                </div>



                <!-- =========================================
                     DELIVERY ADDRESS
                ========================================== -->

                <div
                    class="
                        admin-order-address
                    "
                >


                    <span>
                        Delivery Address
                    </span>


                    <p>

                        <?= nl2br(
                            e(
                                $order[
                                    'delivery_address'
                                ]
                            )
                        ) ?>

                    </p>


                </div>



                <!-- =========================================
                     STATUS UPDATE
                ========================================== -->

                <form
                    method="POST"
                    class="admin-status-form"
                >


                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(
                            $csrfToken
                        ) ?>"
                    >


                    <input
                        type="hidden"
                        name="order_id"
                        value="<?= (int)$order['id'] ?>"
                    >


                    <label
                        for="status_<?= (int)$order['id'] ?>"
                    >
                        Status
                    </label>


                    <select
                        id="status_<?= (int)$order['id'] ?>"
                        name="status"
                        required
                    >


                        <?php foreach (
                            $allowedStatuses
                            as $status
                        ): ?>


                            <option
                                value="<?= e(
                                    $status
                                ) ?>"
                                <?= $order[
                                    'status'
                                ] === $status
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= e(
                                    $status
                                ) ?>

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


</body>

</html>