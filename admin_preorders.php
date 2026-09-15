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


function peso(float $amount): string
{
    return '₱' . number_format(
        $amount,
        2
    );
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
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (
    empty(
        $_SESSION['admin_preorders_csrf']
    )
) {

    $_SESSION['admin_preorders_csrf'] =
        bin2hex(
            random_bytes(32)
        );
}

$csrfToken =
    $_SESSION['admin_preorders_csrf'];

$message = '';
$error = '';


/*
|--------------------------------------------------------------------------
| PRE-ORDER STATUSES
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
| RELEASE STATUSES
|--------------------------------------------------------------------------
*/

$allowedReleaseStatuses = [
    'Waiting for Manufacturer',
    'Manufacturing',
    'Shipped to Store',
    'Arrived',
    'Ready for Customer',
    'Released'
];


/*
|--------------------------------------------------------------------------
| UPDATE CUSTOMER RELIABILITY
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

    $result =
        $conn->query($sql);

    if (!$result) {
        return;
    }


    $updateStmt =
        $conn->prepare(
            "UPDATE users
             SET
                completed_preorders = ?,
                cancelled_preorders = ?,
                reliability_score = ?
             WHERE id = ?"
        );


    while (
        $row =
            $result->fetch_assoc()
    ) {

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
| HANDLE POST REQUESTS
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

        if (
            $action === 'update_status'
        ) {

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

                $checkStmt =
                    $conn->prepare(
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


                    $paymentCheckStmt =
                        $conn->prepare(
                            "SELECT
                                deposit_status,
                                balance_status
                             FROM preorders
                             WHERE id = ?
                             LIMIT 1"
                        );


                    $paymentCheckStmt->bind_param(
                        "i",
                        $preorderId
                    );


                    $paymentCheckStmt->execute();


                    $paymentCheckResult =
                        $paymentCheckStmt->get_result();


                    $paymentCheck =
                        $paymentCheckResult->fetch_assoc();


                    $paymentCheckStmt->close();


                    $fullyPaid = (
                        $paymentCheck
                        &&
                        $paymentCheck[
                            'deposit_status'
                        ] === 'Paid'
                        &&
                        $paymentCheck[
                            'balance_status'
                        ] === 'Paid'
                    );


                    if (
                        $fullyPaid
                        &&
                        $newStatus !== 'Completed'
                        &&
                        $newStatus !== 'Cancelled'
                    ) {

                        $error =
                            'This pre-order is fully paid and must remain Completed unless it is Cancelled.';

                    } else {

                        $updateStmt =
                            $conn->prepare(
                                "UPDATE preorders
                                 SET status = ?
                                 WHERE id = ?"
                            );


                        $updateStmt->bind_param(
                            "si",
                            $newStatus,
                            $preorderId
                        );


                        if (
                            $updateStmt->execute()
                        ) {

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
            }


        /*
        |--------------------------------------------------------------------------
        | UPDATE RELEASE STATUS
        |--------------------------------------------------------------------------
        */

        } elseif (
            $action === 'update_release'
        ) {

            $newReleaseStatus =
                trim(
                    $_POST[
                        'release_status'
                    ] ?? ''
                );


            $expectedReleaseDate =
                trim(
                    $_POST[
                        'expected_release_date'
                    ] ?? ''
                );


            if (!$preorderId) {

                $error =
                    'Invalid pre-order.';

            } elseif (
                !in_array(
                    $newReleaseStatus,
                    $allowedReleaseStatuses,
                    true
                )
            ) {

                $error =
                    'Invalid release status.';

            } else {

                $formattedDate = null;


                if (
                    $expectedReleaseDate !== ''
                ) {

                    $dateObject =
                        DateTime::createFromFormat(
                            'Y-m-d',
                            $expectedReleaseDate
                        );


                    $validDate =
                        $dateObject
                        &&
                        $dateObject->format(
                            'Y-m-d'
                        ) === $expectedReleaseDate;


                    if (!$validDate) {

                        $error =
                            'Invalid expected release date.';

                    } else {

                        $formattedDate =
                            $expectedReleaseDate;
                    }
                }


                if (!$error) {

                    $releaseStmt =
                        $conn->prepare(
                            "UPDATE preorders
                             SET
                                release_status = ?,
                                expected_release_date = ?
                             WHERE id = ?"
                        );


                    $releaseStmt->bind_param(
                        "ssi",
                        $newReleaseStatus,
                        $formattedDate,
                        $preorderId
                    );


                    if (
                        $releaseStmt->execute()
                    ) {

                        $message =
                            'Release information updated successfully.';

                    } else {

                        $error =
                            'Failed to update release information.';
                    }


                    $releaseStmt->close();
                }
            }


        /*
        |--------------------------------------------------------------------------
        | HANDLE PAYMENTS
        |--------------------------------------------------------------------------
        */

        } elseif (
            $action === 'payment'
        ) {

            $paymentType =
                $_POST[
                    'payment_type'
                ] ?? '';


            $paidAmount =
                isset(
                    $_POST['paid_amount']
                )
                    ? (float)$_POST[
                        'paid_amount'
                    ]
                    : 0;


            if (!$preorderId) {

                $error =
                    'Invalid pre-order.';

            } elseif (
                !in_array(
                    $paymentType,
                    [
                        'deposit',
                        'balance'
                    ],
                    true
                )
            ) {

                $error =
                    'Invalid payment type.';

            } elseif (
                $paidAmount < 0
            ) {

                $error =
                    'Payment amount cannot be negative.';

            } else {

                $preorderStmt =
                    $conn->prepare(
                        "SELECT
                            deposit_amount,
                            deposit_paid_amount,
                            deposit_status,

                            remaining_balance,
                            balance_paid_amount,
                            balance_status,
                            balance_paid_at

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

                    if (
                        $paymentType === 'deposit'
                    ) {

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

                        $currentRemainingBalance =
                            (float)$preorder[
                                'remaining_balance'
                            ];


                        $currentBalancePaid =
                            (float)$preorder[
                                'balance_paid_amount'
                            ];


                        if (
                            $paidAmount <= 0
                        ) {

                            $error =
                                'Balance payment must be greater than zero.';

                        } elseif (
                            $paidAmount
                            > $currentRemainingBalance
                        ) {

                            $error =
                                'Balance payment cannot be greater than the remaining balance.';

                        } else {

                            $newBalancePaid =
                                $currentBalancePaid
                                + $paidAmount;


                            $newRemainingBalance =
                                $currentRemainingBalance
                                - $paidAmount;


                            if (
                                $newRemainingBalance < 0.01
                            ) {

                                $newRemainingBalance =
                                    0.00;
                            }


                            $balanceStatus =
                                (
                                    $newRemainingBalance <= 0
                                )
                                    ? 'Paid'
                                    : 'Unpaid';


                            $balancePaidAt =
                                (
                                    $balanceStatus === 'Paid'
                                )
                                    ? date(
                                        'Y-m-d H:i:s'
                                    )
                                    : $preorder[
                                        'balance_paid_at'
                                    ];


                            $updateStmt =
                                $conn->prepare(
                                    "UPDATE preorders
                                     SET
                                        balance_paid_amount = ?,
                                        remaining_balance = ?,
                                        balance_status = ?,
                                        balance_paid_at = ?
                                     WHERE id = ?"
                                );


                            $updateStmt->bind_param(
                                "ddssi",
                                $newBalancePaid,
                                $newRemainingBalance,
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
| SYNC FULLY PAID PRE-ORDERS
|--------------------------------------------------------------------------
*/

$syncStmt =
    $conn->prepare(
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

updateReliabilityScores(
    $conn
);


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


$result =
    $conn->query($sql);


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
        Manage Pre-Orders | Mimic Haven Collectibles
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
   MESSAGES
========================================================= */

.message {
    background: #d9f7df;

    color: #1f5d2c;

    padding:
        14px 18px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-size: 13px;
}


.error {
    background: #ffdede;

    color: #8a1f1f;

    padding:
        14px 18px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-size: 13px;
}


/* =========================================================
   TABLE
========================================================= */

.table-wrapper {
    overflow-x: auto;

    background: #ffffff;

    border-radius: 12px;
}


.table-wrapper table {
    width: 100%;

    min-width: 1650px;

    border-collapse: collapse;

    color: #182637;
}


.table-wrapper th {
    background: #9ADCF7;

    padding: 14px;

    text-align: left;

    font-size: 13px;

    white-space: nowrap;
}


.table-wrapper td {
    padding: 14px;

    border-bottom:
        1px solid #dddddd;

    vertical-align: top;

    font-size: 13px;
}


.table-wrapper tr:last-child td {
    border-bottom: none;
}


/* =========================================================
   PRODUCT
========================================================= */

.product-cell {
    display: flex;

    align-items: center;

    gap: 12px;
}


.product-cell img {
    width: 65px;
    height: 65px;

    object-fit: cover;

    border-radius: 8px;

    background: #eef4f7;

    flex-shrink: 0;
}


.product-name {
    font-weight: bold;

    line-height: 1.4;
}


.muted {
    margin-top: 4px;

    color: #666666;

    font-size: 12px;

    line-height: 1.5;
}


/* =========================================================
   STATUS
========================================================= */

.status {
    display: inline-block;

    padding:
        5px 9px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: bold;

    white-space: nowrap;
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


/* =========================================================
   PAYMENT / STATUS / RELEASE
========================================================= */

.payment-box,
.release-box,
.status-box {
    margin-top: 8px;
}


.payment-box form,
.release-box form,
.status-box form {
    display: flex;

    gap: 6px;

    flex-wrap: wrap;
}


.payment-box input {
    width: 95px;

    padding: 8px;

    border:
        1px solid #cccccc;

    border-radius: 6px;

    font-family: Arial, sans-serif;

    box-sizing: border-box;
}


.release-box input[type="date"] {
    width: 145px;

    padding: 8px;

    border:
        1px solid #cccccc;

    border-radius: 6px;

    font-family: Arial, sans-serif;

    box-sizing: border-box;
}


.payment-box button,
.release-box button,
.status-box button {
    background: #182637;

    color: #ffffff;

    border: none;

    padding:
        8px 12px;

    border-radius: 6px;

    cursor: pointer;

    font-weight: 600;
}


.payment-box button:hover,
.release-box button:hover,
.status-box button:hover {
    opacity: .85;
}


.status-box select,
.release-box select {
    padding: 8px;

    border:
        1px solid #cccccc;

    border-radius: 6px;

    background: #ffffff;

    color: #182637;

    width: 180px;

    font-family: Arial, sans-serif;
}


.release-box select {
    width: 210px;
}


.payment-date {
    display: block;

    margin-top: 5px;

    font-size: 11px;

    color: #666666;
}


.release-current {
    margin-bottom: 8px;
}


/* =========================================================
   BACK LINK
========================================================= */

.back-link {
    display: inline-block;

    margin-top: 20px;

    color: #9ADCF7;

    text-decoration: none;

    font-size: 13px;
}


.back-link:hover {
    text-decoration: underline;
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


@media (max-width: 700px) {

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


    .product-cell {
        align-items: flex-start;
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
                Manage Pre-Orders
            </h1>


            <p>
                View reservations, payments, release status,
                and expected release dates.
            </p>

        </div>


        <nav
            class="admin-nav"
            aria-label="Admin navigation"
        >

            <a href="admin.php">
                Dashboard
            </a>


            <a href="admin_orders.php">
                Orders
            </a>


            <a
                href="admin_preorders.php"
                class="active"
            >
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
         MESSAGES
    ====================================================== -->

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



    <!-- =====================================================
         PRE-ORDER TABLE
    ====================================================== -->

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
                        Release Management
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php if (
                $result
                &&
                $result->num_rows > 0
            ): ?>


                <?php while (
                    $row =
                        $result->fetch_assoc()
                ): ?>


                    <tr>


                        <!-- PRE-ORDER -->

                        <td>

                            <strong>
                                #<?= e(
                                    $row['id']
                                ) ?>
                            </strong>


                            <div class="muted">

                                <?= e(
                                    date(
                                        'M d, Y',
                                        strtotime(
                                            $row[
                                                'created_at'
                                            ]
                                        )
                                    )
                                ) ?>

                            </div>


                            <div class="muted">

                                Qty:
                                <?= e(
                                    $row[
                                        'quantity'
                                    ]
                                ) ?>

                            </div>

                        </td>



                        <!-- CUSTOMER -->

                        <td>

                            <strong>

                                <?= e(
                                    $row[
                                        'first_name'
                                    ]
                                    . ' '
                                    .
                                    $row[
                                        'last_name'
                                    ]
                                ) ?>

                            </strong>


                            <div class="muted">

                                <?= e(
                                    $row[
                                        'email'
                                    ]
                                ) ?>

                            </div>

                        </td>



                        <!-- PRODUCT -->

                        <td>

                            <div class="product-cell">


                                <?php

                                $img =
                                    productImage(
                                        $row[
                                            'image'
                                        ]
                                    );

                                ?>


                                <?php if ($img): ?>

                                    <img
                                        src="<?= e(
                                            $img
                                        ) ?>"
                                        alt="<?= e(
                                            $row[
                                                'product_name'
                                            ]
                                        ) ?>"
                                    >

                                <?php endif; ?>


                                <div>

                                    <div class="product-name">

                                        <?= e(
                                            $row[
                                                'product_name'
                                            ]
                                        ) ?>

                                    </div>


                                    <div class="muted">

                                        <?= e(
                                            $row[
                                                'series'
                                            ]
                                        ) ?>

                                    </div>


                                    <div class="muted">

                                        Unit Price:
                                        <?= peso(
                                            (float)$row[
                                                'unit_price'
                                            ]
                                        ) ?>

                                    </div>

                                </div>

                            </div>

                        </td>



                        <!-- TOTAL -->

                        <td>

                            <strong>

                                <?= peso(
                                    (float)$row[
                                        'total_amount'
                                    ]
                                ) ?>

                            </strong>


                            <div class="muted">

                                Deposit:
                                <?= peso(
                                    (float)$row[
                                        'deposit_amount'
                                    ]
                                ) ?>

                            </div>


                            <div class="muted">

                                Remaining:
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
                                $row[
                                    'deposit_status'
                                ] === 'Paid'
                            ): ?>


                                <span class="status paid">
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
                                    $row[
                                        'deposit_paid_at'
                                    ]
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


                                <span class="status unpaid">
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


                                        <button type="submit">
                                            Record
                                        </button>


                                    </form>

                                </div>


                            <?php endif; ?>


                        </td>



                        <!-- BALANCE -->

                        <td>


                            <?php if (
                                $row[
                                    'balance_status'
                                ] === 'Paid'
                            ): ?>


                                <span class="status paid">
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
                                    $row[
                                        'balance_paid_at'
                                    ]
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


                                <span class="status unpaid">
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


                                        <button type="submit">
                                            Record
                                        </button>


                                    </form>

                                </div>


                            <?php endif; ?>


                        </td>



                        <!-- PRE-ORDER STATUS -->

                        <td>


                            <?php if (
                                $row[
                                    'status'
                                ] === 'Cancelled'
                            ): ?>

                                <span class="status cancelled">
                                    Cancelled
                                </span>


                            <?php elseif (
                                $row[
                                    'status'
                                ] === 'Completed'
                            ): ?>

                                <span class="status completed">
                                    Completed
                                </span>


                            <?php else: ?>

                                <span class="status pending">

                                    <?= e(
                                        $row[
                                            'status'
                                        ]
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
                                                <?= $row[
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
                                    >
                                        Update
                                    </button>

                                </form>

                            </div>

                        </td>



                        <!-- RELEASE MANAGEMENT -->

                        <td>


                            <div
                                class="release-current"
                            >

                                <span class="status pending">

                                    <?= e(
                                        $row[
                                            'release_status'
                                        ]
                                    ) ?>

                                </span>


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

                            </div>



                            <div class="release-box">

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
                                        value="update_release"
                                    >


                                    <input
                                        type="hidden"
                                        name="preorder_id"
                                        value="<?= e(
                                            $row['id']
                                        ) ?>"
                                    >


                                    <select
                                        name="release_status"
                                        required
                                    >

                                        <?php foreach (
                                            $allowedReleaseStatuses
                                            as $releaseStatus
                                        ): ?>

                                            <option
                                                value="<?= e(
                                                    $releaseStatus
                                                ) ?>"
                                                <?= $row[
                                                    'release_status'
                                                ]
                                                    ===
                                                    $releaseStatus
                                                    ? 'selected'
                                                    : '' ?>
                                            >

                                                <?= e(
                                                    $releaseStatus
                                                ) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>


                                    <input
                                        type="date"
                                        name="expected_release_date"
                                        value="<?= e(
                                            $row[
                                                'expected_release_date'
                                            ] ?? ''
                                        ) ?>"
                                    >


                                    <button
                                        type="submit"
                                    >
                                        Update Release
                                    </button>

                                </form>

                            </div>


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



    <!-- =====================================================
         BACK LINK
    ====================================================== -->

    <a
        href="admin.php"
        class="back-link"
    >
        ← Back to Admin Dashboard
    </a>


</div>


</body>

</html>