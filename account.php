<?php

require_once 'auth.php';
require_once 'db.php';

requireLogin();

$userId = currentUserId();

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

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$fullName = $user['first_name'] . ' ' . $user['last_name'];

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
            <?php
            $logo = '';

            foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {
                $file = __DIR__ . "/assets/logo.$ext";

                if (file_exists($file)) {
                    $logo = "assets/logo.$ext";
                    break;
                }
            }
            ?>

            <?php if ($logo): ?>

                <img
                    src="<?= e($logo) ?>"
                    alt="Mimic Haven Collectibles"
                    class="brand-logo"
                >

            <?php else: ?>

                <div class="brand-fallback">
                    MIMIC HAVEN
                    <span>COLLECTIBLES</span>
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

            <span class="nav-user">
                Hi, <?= e($user['first_name']) ?>
            </span>

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
                <span id="cart-count">0</span>
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
                                <?= e(date('F j, Y', strtotime($user['created_at']))) ?>
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


                    <div class="account-empty">

                        <div class="account-empty-icon">
                            ✦
                        </div>

                        <h3>
                            Your orders will appear here
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