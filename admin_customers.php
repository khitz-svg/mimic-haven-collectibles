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

function reliabilityLabel(float $score): string
{
    if ($score >= 90) {
        return 'Excellent';
    }

    if ($score >= 70) {
        return 'Good';
    }

    if ($score >= 50) {
        return 'Fair';
    }

    return 'Low';
}

function reliabilityClass(float $score): string
{
    if ($score >= 90) {
        return 'excellent';
    }

    if ($score >= 70) {
        return 'good';
    }

    if ($score >= 50) {
        return 'fair';
    }

    return 'low';
}


/*
|--------------------------------------------------------------------------
| CHECK ADMIN
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
| UPDATE RELIABILITY SCORES
|--------------------------------------------------------------------------
*/

$scoreSql = "
    SELECT
        u.id,

        COUNT(
            CASE
                WHEN po.status = 'Completed'
                THEN 1
            END
        ) AS completed_count,

        COUNT(
            CASE
                WHEN po.status = 'Cancelled'
                THEN 1
            END
        ) AS cancelled_count

    FROM users u

    LEFT JOIN preorders po
        ON u.id = po.user_id

    WHERE u.role = 'customer'

    GROUP BY u.id
";

$scoreResult = $conn->query($scoreSql);

if ($scoreResult) {

    $updateScoreStmt = $conn->prepare(
        "UPDATE users
         SET
            completed_preorders = ?,
            cancelled_preorders = ?,
            reliability_score = ?
         WHERE id = ?"
    );

    while ($scoreRow = $scoreResult->fetch_assoc()) {

        $completed =
            (int)$scoreRow['completed_count'];

        $cancelled =
            (int)$scoreRow['cancelled_count'];

        $score =
            100 - ($cancelled * 20);

        if ($score < 0) {
            $score = 0;
        }

        $customerId =
            (int)$scoreRow['id'];

        $updateScoreStmt->bind_param(
            "iidi",
            $completed,
            $cancelled,
            $score,
            $customerId
        );

        $updateScoreStmt->execute();
    }

    $updateScoreStmt->close();
}


/*
|--------------------------------------------------------------------------
| GET CUSTOMERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        first_name,
        last_name,
        email,
        reliability_score,
        completed_preorders,
        cancelled_preorders,
        created_at

    FROM users

    WHERE role = 'customer'

    ORDER BY reliability_score DESC, created_at DESC
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
        Manage Customers | Mimic Haven Collectibles
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

    <style>

        body {
            background: #182637;
            color: #fff;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .admin-container {
            max-width: 1250px;
            margin: 0 auto;
            padding: 35px 20px 70px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .admin-header h1 {
            margin: 0 0 8px;
        }

        .admin-header p {
            margin: 0;
            color: #aab2ba;
        }

        .admin-nav {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .admin-nav a {
            background: #9ADCF7;
            color: #182637;
            text-decoration: none;
            padding: 9px 13px;
            border-radius: 7px;
            font-weight: 700;
            font-size: 13px;
        }

        .admin-nav a:hover {
            opacity: .85;
        }

        .reliability-guide {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }

        .guide-card {
            background: #191b1e;
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 10px;
            padding: 16px;
        }

        .guide-card strong {
            display: block;
            font-size: 22px;
            margin-bottom: 5px;
        }

        .guide-card span {
            color: #89929c;
            font-size: 12px;
        }

        .excellent {
            color: #2ca84a;
        }

        .good {
            color: #8dbd31;
        }

        .fair {
            color: #d48a16;
        }

        .low {
            color: #d54545;
        }

        .table-wrapper {
            background: #fff;
            border-radius: 12px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
            color: #182637;
        }

        th {
            background: #9ADCF7;
            padding: 15px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
            font-size: 13px;
        }

        .customer-name {
            font-weight: 700;
        }

        .muted {
            color: #666;
            font-size: 12px;
            margin-top: 4px;
        }

        .score-number {
            font-size: 20px;
            font-weight: 800;
        }

        .rating-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #eef2f5;
            font-size: 11px;
            font-weight: 700;
        }

        .activity-number {
            font-weight: 700;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .legend {
            margin-top: 20px;
            color: #89929c;
            font-size: 12px;
        }

        @media (max-width: 800px) {

            .reliability-guide {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 500px) {

            .reliability-guide {
                grid-template-columns: 1fr;
            }
        }

    </style>

</head>


<body>


<div class="admin-container">


    <!-- HEADER -->

    <div class="admin-header">

        <div>

            <h1>
                Manage Customers
            </h1>

            <p>
                Monitor customer activity and reliability.
            </p>

        </div>


        <div class="admin-nav">

            <a href="admin.php">
                Dashboard
            </a>

            <a href="admin_orders.php">
                Orders
            </a>

            <a href="admin_preorders.php">
                Pre-Orders
            </a>

            <a href="account.php">
                My Account
            </a>

            <a href="logout.php">
                Logout
            </a>

        </div>

    </div>



    <!-- RELIABILITY GUIDE -->

    <section class="reliability-guide">

        <div class="guide-card">

            <strong class="excellent">
                90–100
            </strong>

            <span>
                Excellent
            </span>

        </div>


        <div class="guide-card">

            <strong class="good">
                70–89
            </strong>

            <span>
                Good
            </span>

        </div>


        <div class="guide-card">

            <strong class="fair">
                50–69
            </strong>

            <span>
                Fair
            </span>

        </div>


        <div class="guide-card">

            <strong class="low">
                0–49
            </strong>

            <span>
                Low
            </span>

        </div>

    </section>



    <!-- CUSTOMER TABLE -->

    <div class="table-wrapper">

        <table>

            <thead>

                <tr>

                    <th>
                        Customer
                    </th>

                    <th>
                        Email
                    </th>

                    <th>
                        Completed
                    </th>

                    <th>
                        Cancelled
                    </th>

                    <th>
                        Reliability Score
                    </th>

                    <th>
                        Rating
                    </th>

                    <th>
                        Member Since
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php if ($result && $result->num_rows > 0): ?>


                <?php while ($customer = $result->fetch_assoc()): ?>

                    <?php

                    $score =
                        (float)$customer[
                            'reliability_score'
                        ];

                    $rating =
                        reliabilityLabel(
                            $score
                        );

                    $class =
                        reliabilityClass(
                            $score
                        );

                    ?>


                    <tr>


                        <!-- CUSTOMER -->

                        <td>

                            <div class="customer-name">

                                <?= e(
                                    $customer['first_name']
                                    . ' '
                                    . $customer['last_name']
                                ) ?>

                            </div>

                            <div class="muted">

                                Account #
                                <?= e(
                                    $customer['id']
                                ) ?>

                            </div>

                        </td>



                        <!-- EMAIL -->

                        <td>

                            <?= e(
                                $customer['email']
                            ) ?>

                        </td>



                        <!-- COMPLETED -->

                        <td>

                            <span class="activity-number">

                                <?= e(
                                    $customer[
                                        'completed_preorders'
                                    ]
                                ) ?>

                            </span>

                        </td>



                        <!-- CANCELLED -->

                        <td>

                            <span
                                class="
                                    activity-number
                                    <?= $customer[
                                        'cancelled_preorders'
                                    ] > 0
                                        ? 'low'
                                        : ''
                                    ?>
                                "
                            >

                                <?= e(
                                    $customer[
                                        'cancelled_preorders'
                                    ]
                                ) ?>

                            </span>

                        </td>



                        <!-- SCORE -->

                        <td>

                            <span
                                class="
                                    score-number
                                    <?= e(
                                        $class
                                    ) ?>
                                "
                            >

                                <?= number_format(
                                    $score,
                                    2
                                ) ?>

                            </span>

                        </td>



                        <!-- RATING -->

                        <td>

                            <span
                                class="
                                    rating-badge
                                    <?= e(
                                        $class
                                    ) ?>
                                "
                            >

                                <?= e(
                                    $rating
                                ) ?>

                            </span>

                        </td>



                        <!-- MEMBER SINCE -->

                        <td>

                            <?= e(
                                date(
                                    'M d, Y',
                                    strtotime(
                                        $customer[
                                            'created_at'
                                        ]
                                    )
                                )
                            ) ?>

                        </td>


                    </tr>


                <?php endwhile; ?>


            <?php else: ?>


                <tr>

                    <td
                        colspan="7"
                        class="empty"
                    >

                        No customers found.

                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>

    </div>



    <div class="legend">

        Reliability is calculated from completed and cancelled
        pre-orders. Each cancellation reduces the score by 20 points.

    </div>


</div>

</body>

</html>