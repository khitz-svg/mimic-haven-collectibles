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
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function peso(float $amount): string
{
    return '₱' . number_format($amount, 2);
}

function productImage(?string $image): string
{
    if (!$image) {
        return '';
    }

    $file = trim($image);

    $file = preg_replace(
        '/^.*?assets[\\\\\/]collections[\\\\\/]/i',
        '',
        $file
    );

    return 'assets/collections/' . rawurlencode($file);
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

    die('Access denied.');
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['admin_preorders_csrf'])) {
    $_SESSION['admin_preorders_csrf'] =
        bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['admin_preorders_csrf'];

$message = '';
$error = '';


/*
|--------------------------------------------------------------------------
| AUTOMATICALLY UPDATE RELIABILITY SCORES
|--------------------------------------------------------------------------
|
| Score starts at 100.
| Every cancelled pre-order removes 20 points.
| Minimum score is 0.
|
|--------------------------------------------------------------------------
*/

function updateReliabilityScores(mysqli $conn): void
{
    $sql = "
        SELECT
            u.id,

            COALESCE(
                SUM(
                    CASE
                        WHEN po.status = 'Completed'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS completed_count,

            COALESCE(
                SUM(
                    CASE
                        WHEN po.status = 'Cancelled'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS cancelled_count

        FROM users u

        LEFT JOIN preorders po
            ON u.id = po.user_id

        WHERE u.role = 'customer'

        GROUP BY u.id
    ";

    $result = $conn->query($sql);

    if (!$result) {
        return;
    }

    $updateStmt = $conn->prepare(
        "UPDATE users
         SET
            completed_preorders = ?,
            cancelled_preorders = ?,
            reliability_score = ?
         WHERE id = ?"
    );

    while ($row = $result->fetch_assoc()) {

        $completed =
            (int)$row['completed_count'];

        $cancelled =
            (int)$row['cancelled_count'];

        $score =
            100 - ($cancelled * 20);

        if ($score < 0) {
            $score = 0;
        }

        $customerId =
            (int)$row['id'];

        $updateStmt->bind_param(
            "iidi",
            $completed,
            $cancelled,
            $score,
            $customerId
        );

        $updateStmt->execute();
    }

    $updateStmt->close();
}


/*
|--------------------------------------------------------------------------
| ALLOWED PRE-ORDER STATUSES
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    'Pending',
    'Confirmed',
    'Processing',
    'Completed',
    'Cancelled'
];


/*
|--------------------------------------------------------------------------
| HANDLE POST REQUESTS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedToken =
        $_POST['csrf_token'] ?? '';

    if (!hash_equals(
        $csrfToken,
        $postedToken
    )) {

        $error =
            'Invalid security token. Please try again.';

    } else {

        $action =
            $_POST['action'] ?? '';

        $preorderId =
            filter_input(
                INPUT_POST,
                'preorder_id',
                FILTER_VALIDATE_INT
            );


        /*
        |--------------------------------------------------------------------------
        | UPDATE PRE-ORDER STATUS
        |--------------------------------------------------------------------------
        */

        if ($action === 'update_status') {

            $newStatus =
                trim(
                    $_POST['status'] ?? ''
                );


            if (!$preorderId) {

                $error =
                    'Invalid pre-order.';

            } elseif (
                !in_array(
                    $newStatus,
                    $allowedStatuses,
                    true
                )
            ) {

                $error =
                    'Invalid pre-order status.';

            } else {

                /*
                | Get current status first
                */

                $checkStmt = $conn->prepare(
                    "SELECT status
                     FROM preorders
                     WHERE id = ?
                     LIMIT 1"
                );

                $checkStmt->bind_param(
                    "i",
                    $preorderId
                );

                $checkStmt->execute();

                $checkResult =
                    $checkStmt->get_result();

                $existingPreorder =
                    $checkResult->fetch_assoc();

                $checkStmt->close();


                if (!$existingPreorder) {

                    $error =
                        'Pre-order not found.';

                } else {

                    $oldStatus =
                        $existingPreorder['status'];


                    /*
                    |--------------------------------------------------------------------------
                    | Update status
                    |--------------------------------------------------------------------------
                    */

                    $updateStmt = $conn->prepare(
                        "UPDATE preorders
                         SET status = ?
                         WHERE id = ?"
                    );

                    $updateStmt->bind_param(
                        "si",
                        $newStatus,
                        $preorderId
                    );


                    if ($updateStmt->execute()) {

                        /*
                        |--------------------------------------------------------------------------
                        | Update reliability immediately
                        |--------------------------------------------------------------------------
                        */

                        updateReliabilityScores(
                            $conn
                        );


                        if (
                            $newStatus === 'Cancelled'
                            &&
                            $oldStatus !== 'Cancelled'
                        ) {

                            $message =
                                'Pre-order cancelled. Customer reliability score updated.';

                        } elseif (
                            $oldStatus === 'Cancelled'
                            &&
                            $newStatus !== 'Cancelled'
                        ) {

                            $message =
                                'Pre-order status restored. Customer reliability score updated.';

                        } else {

                            $message =
                                'Pre-order status updated successfully.';
                        }

                    } else {

                        $error =
                            'Failed to update pre-order status.';
                    }


                    $updateStmt->close();
                }
            }


        /*
        |--------------------------------------------------------------------------
        | HANDLE PAYMENTS
        |--------------------------------------------------------------------------
        */

        } elseif ($action === 'payment') {

            $paymentType =
                $_POST['payment_type'] ?? '';

            $paidAmount =
                isset($_POST['paid_amount'])
                    ? (float)$_POST['paid_amount']
                    : 0;


            if (!$preorderId) {

                $error =
                    'Invalid pre-order.';

            } elseif (
                !in_array(
                    $paymentType,
                    ['deposit', 'balance'],
                    true
                )
            ) {

                $error =
                    'Invalid payment type.';

            } elseif ($paidAmount < 0) {

                $error =
                    'Payment amount cannot be negative.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | GET CURRENT PRE-ORDER
                |--------------------------------------------------------------------------
                */

                $preorderStmt = $conn->prepare(
                    "SELECT
                        deposit_amount,
                        deposit_paid_amount,
                        deposit_status,

                        remaining_balance,
                        balance_paid_amount,
                        balance_status

                     FROM preorders

                     WHERE id = ?

                     LIMIT 1"
                );

                $preorderStmt->bind_param(
                    "i",
                    $preorderId
                );

                $preorderStmt->execute();

                $preorderResult =
                    $preorderStmt->get_result();

                $preorder =
                    $preorderResult->fetch_assoc();

                $preorderStmt->close();


                if (!$preorder) {

                    $error =
                        'Pre-order not found.';

                } else {


                    /*
                    |--------------------------------------------------------------------------
                    | DEPOSIT
                    |--------------------------------------------------------------------------
                    */

                    if ($paymentType === 'deposit') {

                        $requiredDeposit =
                            (float)$preorder[
                                'deposit_amount'
                            ];


                        if (
                            $paidAmount
                            > $requiredDeposit
                        ) {

                            $error =
                                'Deposit payment cannot be greater than the required deposit.';

                        } else {

                            $depositStatus =
                                (
                                    $paidAmount
                                    >= $requiredDeposit
                                )
                                    ? 'Paid'
                                    : 'Unpaid';


                            $depositPaidAt =
                                (
                                    $depositStatus
                                    === 'Paid'
                                )
                                    ? date(
                                        'Y-m-d H:i:s'
                                    )
                                    : null;


                            $updateStmt =
                                $conn->prepare(
                                    "UPDATE preorders
                                     SET
                                        deposit_paid_amount = ?,
                                        deposit_status = ?,
                                        deposit_paid_at = ?
                                     WHERE id = ?"
                                );


                            $updateStmt->bind_param(
                                "dssi",
                                $paidAmount,
                                $depositStatus,
                                $depositPaidAt,
                                $preorderId
                            );


                            if (
                                $updateStmt->execute()
                            ) {

                                $message =
                                    'Deposit payment updated successfully.';

                            } else {

                                $error =
                                    'Failed to update deposit payment.';
                            }


                            $updateStmt->close();
                        }


                    /*
                    |--------------------------------------------------------------------------
                    | BALANCE
                    |--------------------------------------------------------------------------
                    */

                    } else {

                        $remainingBalance =
                            (float)$preorder[
                                'remaining_balance'
                            ];


                        if (
                            $paidAmount
                            > $remainingBalance
                        ) {

                            $error =
                                'Balance payment cannot be greater than the remaining balance.';

                        } else {

                            $balanceStatus =
                                (
                                    $paidAmount
                                    >= $remainingBalance
                                )
                                    ? 'Paid'
                                    : 'Unpaid';


                            $balancePaidAt =
                                (
                                    $balanceStatus
                                    === 'Paid'
                                )
                                    ? date(
                                        'Y-m-d H:i:s'
                                    )
                                    : null;


                            $updateStmt =
                                $conn->prepare(
                                    "UPDATE preorders
                                     SET
                                        balance_paid_amount = ?,
                                        balance_status = ?,
                                        balance_paid_at = ?
                                     WHERE id = ?"
                                );


                            $updateStmt->bind_param(
                                "dssi",
                                $paidAmount,
                                $balanceStatus,
                                $balancePaidAt,
                                $preorderId
                            );


                            if (
                                $updateStmt->execute()
                            ) {

                                $message =
                                    'Balance payment updated successfully.';

                            } else {

                                $error =
                                    'Failed to update balance payment.';
                            }


                            $updateStmt->close();
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | AUTO COMPLETE WHEN FULLY PAID
                    |--------------------------------------------------------------------------
                    */

                    if (!$error) {

                        $statusStmt =
                            $conn->prepare(
                                "SELECT
                                    deposit_status,
                                    balance_status

                                 FROM preorders

                                 WHERE id = ?

                                 LIMIT 1"
                            );

                        $statusStmt->bind_param(
                            "i",
                            $preorderId
                        );

                        $statusStmt->execute();

                        $statusResult =
                            $statusStmt->get_result();

                        $paymentStatus =
                            $statusResult->fetch_assoc();

                        $statusStmt->close();


                        if (
                            $paymentStatus
                            &&
                            $paymentStatus[
                                'deposit_status'
                            ] === 'Paid'
                            &&
                            $paymentStatus[
                                'balance_status'
                            ] === 'Paid'
                        ) {

                            $completeStmt =
                                $conn->prepare(
                                    "UPDATE preorders
                                     SET status = 'Completed'
                                     WHERE id = ?"
                                );

                            $completeStmt->bind_param(
                                "i",
                                $preorderId
                            );

                            $completeStmt->execute();

                            $completeStmt->close();


                            /*
                            |--------------------------------------------------------------------------
                            | Recalculate reliability
                            |--------------------------------------------------------------------------
                            */

                            updateReliabilityScores(
                                $conn
                            );


                            $message =
                                'Payment completed. Pre-order automatically marked as Completed.';
                        }
                    }
                }
            }

        } else {

            $error =
                'Invalid request.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| SYNC FULLY PAID PRE-ORDERS ON PAGE LOAD
|--------------------------------------------------------------------------
*/

$syncStmt = $conn->prepare(
    "UPDATE preorders
     SET status = 'Completed'
     WHERE deposit_status = 'Paid'
       AND balance_status = 'Paid'
       AND status != 'Cancelled'"
);

$syncStmt->execute();
$syncStmt->close();


/*
|--------------------------------------------------------------------------
| UPDATE RELIABILITY
|--------------------------------------------------------------------------
*/

updateReliabilityScores($conn);


/*
|--------------------------------------------------------------------------
| GET ALL PRE-ORDERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        po.id,
        po.quantity,
        po.unit_price,
        po.total_amount,

        po.deposit_amount,
        po.deposit_paid_amount,
        po.deposit_status,
        po.deposit_paid_at,

        po.remaining_balance,
        po.balance_paid_amount,
        po.balance_status,
        po.balance_paid_at,

        po.deposit_due_date,
        po.balance_due_date,
        po.expected_release_date,

        po.status,
        po.release_status,
        po.notes,
        po.created_at,

        u.first_name,
        u.last_name,
        u.email,

        p.name AS product_name,
        p.series,
        p.image

    FROM preorders po

    INNER JOIN users u
        ON po.user_id = u.id

    INNER JOIN products p
        ON po.product_id = p.id

    ORDER BY po.created_at DESC
";

$result = $conn->query($sql);

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
        Manage Pre-Orders | Mimic Haven Collectibles
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

    <style>

        body {
            background: #182637;
            color: #ffffff;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .admin-container {
            max-width: 1500px;
            margin: 0 auto;
            padding: 30px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .admin-header h1 {
            margin: 0;
        }

        .admin-header p {
            color: #aab2ba;
        }

        .admin-nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .admin-nav a {
            text-decoration: none;
            color: #182637;
            background: #9ADCF7;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: bold;
        }

        .admin-nav a:hover {
            opacity: .85;
        }

        .message {
            background: #d9f7df;
            color: #1f5d2c;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #ffdede;
            color: #8a1f1f;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .table-wrapper {
            overflow-x: auto;
            background: #ffffff;
            border-radius: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: #182637;
            min-width: 1450px;
        }

        th {
            background: #9ADCF7;
            padding: 14px;
            text-align: left;
            font-size: 13px;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
            font-size: 13px;
        }

        .product-cell {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .product-cell img {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 8px;
            background: #eef4f7;
        }

        .product-name {
            font-weight: bold;
        }

        .muted {
            color: #666;
            font-size: 12px;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .paid {
            background: #d9f7df;
            color: #1f5d2c;
        }

        .unpaid {
            background: #ffe5c2;
            color: #8a4b00;
        }

        .pending {
            background: #e8edf2;
            color: #364552;
        }

        .completed {
            background: #d9f7df;
            color: #1f5d2c;
        }

        .cancelled {
            background: #ffdede;
            color: #8a1f1f;
        }

        .payment-box {
            margin-top: 8px;
        }

        .payment-box form {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .payment-box input {
            width: 100px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .payment-box button,
        .status-box button {
            background: #182637;
            color: #ffffff;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
        }

        .payment-box button:hover,
        .status-box button:hover {
            opacity: .85;
        }

        .payment-date {
            display: block;
            margin-top: 5px;
            font-size: 11px;
            color: #666;
        }

        .status-box {
            margin-top: 8px;
        }

        .status-box select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            width: 150px;
            margin-bottom: 6px;
        }

        .back-link {
            margin-top: 20px;
            display: inline-block;
            color: #9ADCF7;
            text-decoration: none;
        }

    </style>

</head>


<body>


<div class="admin-container">


    <!-- HEADER -->

    <div class="admin-header">

        <div>

            <h1>
                Manage Pre-Orders
            </h1>

            <p>
                View reservations, payments, status, and release progress.
            </p>

        </div>


        <div class="admin-nav">

            <a href="admin.php">
                Dashboard
            </a>

            <a href="admin_orders.php">
                Orders
            </a>

            <a href="admin_customers.php">
                Customers
            </a>

            <a href="account.php">
                My Account
            </a>

            <a href="logout.php">
                Logout
            </a>

        </div>

    </div>



    <!-- MESSAGES -->

    <?php if ($message): ?>

        <div class="message">
            <?= e($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>



    <!-- TABLE -->

    <div class="table-wrapper">

        <table>

            <thead>

                <tr>

                    <th>
                        Pre-Order
                    </th>

                    <th>
                        Customer
                    </th>

                    <th>
                        Product
                    </th>

                    <th>
                        Total
                    </th>

                    <th>
                        Deposit
                    </th>

                    <th>
                        Balance
                    </th>

                    <th>
                        Pre-Order Status
                    </th>

                    <th>
                        Release
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php if ($result && $result->num_rows > 0): ?>


                <?php while ($row = $result->fetch_assoc()): ?>


                    <tr>


                        <!-- PRE-ORDER -->

                        <td>

                            <strong>
                                #<?= e($row['id']) ?>
                            </strong>

                            <div class="muted">

                                <?= e(
                                    date(
                                        'M d, Y',
                                        strtotime(
                                            $row['created_at']
                                        )
                                    )
                                ) ?>

                            </div>

                            <div class="muted">

                                Qty:
                                <?= e($row['quantity']) ?>

                            </div>

                        </td>



                        <!-- CUSTOMER -->

                        <td>

                            <strong>

                                <?= e(
                                    $row['first_name']
                                    . ' '
                                    . $row['last_name']
                                ) ?>

                            </strong>

                            <div class="muted">

                                <?= e(
                                    $row['email']
                                ) ?>

                            </div>

                        </td>



                        <!-- PRODUCT -->

                        <td>

                            <div class="product-cell">


                                <?php
                                $img = productImage(
                                    $row['image']
                                );
                                ?>


                                <?php if ($img): ?>

                                    <img
                                        src="<?= e($img) ?>"
                                        alt="<?= e(
                                            $row['product_name']
                                        ) ?>"
                                    >

                                <?php endif; ?>


                                <div>

                                    <div class="product-name">

                                        <?= e(
                                            $row['product_name']
                                        ) ?>

                                    </div>


                                    <div class="muted">

                                        <?= e(
                                            $row['series']
                                        ) ?>

                                    </div>


                                    <div class="muted">

                                        Unit Price:
                                        <?= peso(
                                            (float)$row['unit_price']
                                        ) ?>

                                    </div>

                                </div>

                            </div>

                        </td>



                        <!-- TOTAL -->

                        <td>

                            <strong>

                                <?= peso(
                                    (float)$row['total_amount']
                                ) ?>

                            </strong>


                            <div class="muted">

                                Required Deposit:
                                <?= peso(
                                    (float)$row[
                                        'deposit_amount'
                                    ]
                                ) ?>

                            </div>


                            <div class="muted">

                                Remaining Balance:
                                <?= peso(
                                    (float)$row[
                                        'remaining_balance'
                                    ]
                                ) ?>

                            </div>

                        </td>



                        <!-- DEPOSIT -->

                        <td>


                            <?php if (
                                $row['deposit_status']
                                === 'Paid'
                            ): ?>


                                <span
                                    class="
                                        status
                                        paid
                                    "
                                >
                                    Paid
                                </span>


                                <div>

                                    <?= peso(
                                        (float)$row[
                                            'deposit_paid_amount'
                                        ]
                                    ) ?>

                                </div>


                                <?php if (
                                    $row['deposit_paid_at']
                                ): ?>

                                    <span
                                        class="payment-date"
                                    >

                                        <?= e(
                                            date(
                                                'M d, Y h:i A',
                                                strtotime(
                                                    $row[
                                                        'deposit_paid_at'
                                                    ]
                                                )
                                            )
                                        ) ?>

                                    </span>

                                <?php endif; ?>


                            <?php else: ?>


                                <span
                                    class="
                                        status
                                        unpaid
                                    "
                                >
                                    Unpaid
                                </span>


                                <div class="muted">

                                    Paid:
                                    <?= peso(
                                        (float)$row[
                                            'deposit_paid_amount'
                                        ]
                                    ) ?>

                                </div>


                                <div class="payment-box">

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(
                                                $csrfToken
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="payment"
                                        >

                                        <input
                                            type="hidden"
                                            name="preorder_id"
                                            value="<?= e(
                                                $row['id']
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="payment_type"
                                            value="deposit"
                                        >

                                        <input
                                            type="number"
                                            name="paid_amount"
                                            min="0"
                                            max="<?= e(
                                                $row[
                                                    'deposit_amount'
                                                ]
                                            ) ?>"
                                            step="0.01"
                                            value="<?= e(
                                                $row[
                                                    'deposit_amount'
                                                ]
                                            ) ?>"
                                            required
                                        >

                                        <button
                                            type="submit"
                                        >
                                            Record
                                        </button>

                                    </form>

                                </div>


                            <?php endif; ?>


                        </td>



                        <!-- BALANCE -->

                        <td>


                            <?php if (
                                $row['balance_status']
                                === 'Paid'
                            ): ?>


                                <span
                                    class="
                                        status
                                        paid
                                    "
                                >
                                    Paid
                                </span>


                                <div>

                                    <?= peso(
                                        (float)$row[
                                            'balance_paid_amount'
                                        ]
                                    ) ?>

                                </div>


                                <?php if (
                                    $row['balance_paid_at']
                                ): ?>

                                    <span
                                        class="payment-date"
                                    >

                                        <?= e(
                                            date(
                                                'M d, Y h:i A',
                                                strtotime(
                                                    $row[
                                                        'balance_paid_at'
                                                    ]
                                                )
                                            )
                                        ) ?>

                                    </span>

                                <?php endif; ?>


                            <?php else: ?>


                                <span
                                    class="
                                        status
                                        unpaid
                                    "
                                >
                                    Unpaid
                                </span>


                                <div class="muted">

                                    Paid:
                                    <?= peso(
                                        (float)$row[
                                            'balance_paid_amount'
                                        ]
                                    ) ?>

                                </div>


                                <div class="payment-box">

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(
                                                $csrfToken
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="payment"
                                        >

                                        <input
                                            type="hidden"
                                            name="preorder_id"
                                            value="<?= e(
                                                $row['id']
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="payment_type"
                                            value="balance"
                                        >

                                        <input
                                            type="number"
                                            name="paid_amount"
                                            min="0"
                                            max="<?= e(
                                                $row[
                                                    'remaining_balance'
                                                ]
                                            ) ?>"
                                            step="0.01"
                                            value="<?= e(
                                                $row[
                                                    'remaining_balance'
                                                ]
                                            ) ?>"
                                            required
                                        >

                                        <button
                                            type="submit"
                                        >
                                            Record
                                        </button>

                                    </form>

                                </div>


                            <?php endif; ?>


                        </td>



                        <!-- PRE-ORDER STATUS -->

                        <td>

                            <?php if (
                                $row['status']
                                === 'Cancelled'
                            ): ?>

                                <span
                                    class="
                                        status
                                        cancelled
                                    "
                                >
                                    Cancelled
                                </span>

                            <?php elseif (
                                $row['status']
                                === 'Completed'
                            ): ?>

                                <span
                                    class="
                                        status
                                        completed
                                    "
                                >
                                    Completed
                                </span>

                            <?php else: ?>

                                <span
                                    class="
                                        status
                                        pending
                                    "
                                >
                                    <?= e(
                                        $row['status']
                                    ) ?>
                                </span>

                            <?php endif; ?>


                            <div class="status-box">

                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(
                                            $csrfToken
                                        ) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="update_status"
                                    >

                                    <input
                                        type="hidden"
                                        name="preorder_id"
                                        value="<?= e(
                                            $row['id']
                                        ) ?>"
                                    >


                                    <select
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
                                                <?= $row['status']
                                                    === $status
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                <?= e(
                                                    $status
                                                ) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>


                                    <br>


                                    <button
                                        type="submit"
                                    >
                                        Update Status
                                    </button>

                                </form>

                            </div>

                        </td>



                        <!-- RELEASE -->

                        <td>

                            <strong>

                                <?= e(
                                    $row[
                                        'release_status'
                                    ]
                                ) ?>

                            </strong>


                            <?php if (
                                $row[
                                    'expected_release_date'
                                ]
                            ): ?>

                                <div class="muted">

                                    Expected:
                                    <?= e(
                                        date(
                                            'M d, Y',
                                            strtotime(
                                                $row[
                                                    'expected_release_date'
                                                ]
                                            )
                                        )
                                    ) ?>

                                </div>

                            <?php else: ?>

                                <div class="muted">

                                    Release date TBA

                                </div>

                            <?php endif; ?>

                        </td>


                    </tr>


                <?php endwhile; ?>


            <?php else: ?>


                <tr>

                    <td colspan="8">

                        No pre-orders found.

                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>

    </div>



    <a
        href="admin.php"
        class="back-link"
    >
        ← Back to Admin Dashboard
    </a>


</div>

</body>

</html>