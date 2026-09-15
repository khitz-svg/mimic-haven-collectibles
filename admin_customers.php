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


$scoreResult =
    $conn->query($scoreSql);


if ($scoreResult) {

    $updateScoreStmt =
        $conn->prepare(
            "UPDATE users
             SET
                completed_preorders = ?,
                cancelled_preorders = ?,
                reliability_score = ?
             WHERE id = ?"
        );


    while (
        $scoreRow =
            $scoreResult->fetch_assoc()
    ) {

        $completed =
            (int)$scoreRow[
                'completed_count'
            ];

        $cancelled =
            (int)$scoreRow[
                'cancelled_count'
            ];

        $score =
            100 -
            ($cancelled * 20);


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

    ORDER BY
        reliability_score DESC,
        created_at DESC
";


$result =
    $conn->query($sql);

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

/* =========================================================
   ADMIN CONTAINER
========================================================= */

.admin-container {
    max-width: 1550px;

    margin: 0 auto;

    padding: 30px;

    box-sizing: border-box;
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

    padding: 10px 16px;

    border-radius: 8px;

    background: #9ADCF7;

    color: #182637;

    font-family: Arial, sans-serif;

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

    transform: translateY(-1px);
}


.admin-nav a.active {
    background: #ffffff;

    color: #182637;
}


/* =========================================================
   PAGE HEADER
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
   RELIABILITY GUIDE
========================================================= */

.reliability-guide {
    display: grid;

    grid-template-columns:
        repeat(
            4,
            minmax(0, 1fr)
        );

    gap: 12px;

    margin-bottom: 25px;
}


.guide-card {
    background: #191b1e;

    border:
        1px solid
        rgba(255,255,255,.07);

    border-radius: 10px;

    padding: 16px;
}


.guide-card strong {
    display: block;

    margin-bottom: 5px;

    font-size: 22px;
}


.guide-card span {
    color: #89929c;

    font-size: 12px;
}


/* =========================================================
   RELIABILITY COLORS
========================================================= */

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


/* =========================================================
   CUSTOMER TABLE
========================================================= */

.table-wrapper {
    width: 100%;

    background: #ffffff;

    border-radius: 12px;

    overflow-x: auto;
}


.table-wrapper table {
    width: 100%;

    min-width: 1000px;

    border-collapse: collapse;

    color: #182637;
}


.table-wrapper th {
    background: #9ADCF7;

    padding: 15px;

    text-align: left;

    font-size: 12px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .5px;

    white-space: nowrap;
}


.table-wrapper td {
    padding: 15px;

    border-bottom:
        1px solid #dddddd;

    vertical-align: middle;

    font-size: 13px;
}


.table-wrapper tr:last-child td {
    border-bottom: none;
}


/* =========================================================
   CUSTOMER
========================================================= */

.customer-name {
    color: #182637;

    font-weight: 700;
}


.muted {
    margin-top: 4px;

    color: #666666;

    font-size: 12px;

    line-height: 1.5;
}


/* =========================================================
   SCORE
========================================================= */

.score-number {
    font-size: 20px;

    font-weight: 800;
}


/* =========================================================
   RATING
========================================================= */

.rating-badge {
    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    background: #eef2f5;

    font-size: 11px;

    font-weight: 700;
}


/* =========================================================
   ACTIVITY
========================================================= */

.activity-number {
    font-weight: 700;
}


/* =========================================================
   EMPTY
========================================================= */

.empty {
    padding: 40px;

    color: #666666;

    text-align: center;
}


/* =========================================================
   LEGEND
========================================================= */

.legend {
    margin-top: 20px;

    color: #aab2ba;

    font-size: 12px;

    line-height: 1.6;
}


/* =========================================================
   BACK LINK
========================================================= */

.admin-back {
    display: inline-block;

    margin-top: 20px;

    color: #9ADCF7;

    font-size: 13px;

    text-decoration: none;
}


.admin-back:hover {
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


@media (max-width: 800px) {

    .reliability-guide {
        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }

}


@media (max-width: 600px) {

    .admin-container {
        width: 100%;

        padding:
            20px 15px 60px;
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


    .reliability-guide {
        grid-template-columns: 1fr;
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
                Manage Customers
            </h1>


            <p>
                Monitor customer activity and reliability.
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


            <a href="admin_preorders.php">
                Pre-Orders
            </a>


            <a
                href="admin_customers.php"
                class="active"
            >
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
         PAGE HEADER
    ====================================================== -->

    <section class="admin-page-header">


        <span class="admin-page-eyebrow">
            MIMIC HAVEN MANAGEMENT SYSTEM
        </span>


        <h2>
            Customer <span>Reliability</span>
        </h2>


        <p>
            Monitor customer activity, completed pre-orders,
            cancellations, and reliability scores.
        </p>


    </section>



    <!-- =====================================================
         RELIABILITY GUIDE
    ====================================================== -->

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



    <!-- =====================================================
         CUSTOMER TABLE
    ====================================================== -->

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


            <?php if (
                $result
                &&
                $result->num_rows > 0
            ): ?>


                <?php while (
                    $customer =
                        $result->fetch_assoc()
                ): ?>


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

                            <div
                                class="customer-name"
                            >

                                <?= e(
                                    $customer[
                                        'first_name'
                                    ]
                                    . ' '
                                    .
                                    $customer[
                                        'last_name'
                                    ]
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
                                $customer[
                                    'email'
                                ]
                            ) ?>

                        </td>



                        <!-- COMPLETED -->

                        <td>

                            <span
                                class="activity-number"
                            >

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



                        <!-- RELIABILITY SCORE -->

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



    <!-- =====================================================
         LEGEND
    ====================================================== -->

    <div class="legend">

        Reliability is calculated from completed and
        cancelled pre-orders. Each cancellation reduces
        the score by 20 points.

    </div>



    <!-- =====================================================
         BACK LINK
    ====================================================== -->

    <a
        href="admin.php"
        class="admin-back"
    >
        ← Back to Admin Dashboard
    </a>


</div>


</body>

</html>