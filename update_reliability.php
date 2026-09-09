<?php

require_once 'auth.php';
require_once 'db.php';

requireLogin();

$userId = currentUserId();


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
| UPDATE CUSTOMER RELIABILITY
|--------------------------------------------------------------------------
*/

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

if ($result) {

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

        /*
        --------------------------------------------------------------
        Reliability Formula
        --------------------------------------------------------------
        Starting score: 100
        Each cancellation: -20 points
        Minimum score: 0
        --------------------------------------------------------------
        */

        $score =
            100 - ($cancelled * 20);

        if ($score < 0) {
            $score = 0;
        }

        $updateStmt->bind_param(
            "iidi",
            $completed,
            $cancelled,
            $score,
            $row['id']
        );

        $updateStmt->execute();
    }

    $updateStmt->close();
}


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

header('Location: admin_customers.php');
exit;

?>