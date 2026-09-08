<?php

/* =========================================================
   MIMIC HAVEN - LOGIN
========================================================= */

require_once 'auth.php';
require_once 'db.php';


function e(string $value): string {
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


$message = '';
$messageType = '';


/* =========================================================
   ALREADY LOGGED IN
========================================================= */

if (isset($_SESSION['user_id'])) {

    header('Location: index.php');
    exit;

}


/* =========================================================
   LOGIN FORM
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email =
        trim($_POST['email'] ?? '');

    $password =
        $_POST['password'] ?? '';


    /* -----------------------------------------
       BASIC VALIDATION
    ----------------------------------------- */

    if (
        $email === '' ||
        $password === ''
    ) {

        $message =
            'Please enter your email and password.';

        $messageType =
            'error';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $message =
            'Please enter a valid email address.';

        $messageType =
            'error';

    } else {

        /* -----------------------------------------
           FIND USER
        ----------------------------------------- */

        $stmt = $conn->prepare(
            "SELECT
                id,
                first_name,
                last_name,
                email,
                password
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param(
            "s",
            $email
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        $user =
            $result->fetch_assoc();

        $stmt->close();


        /* -----------------------------------------
           VERIFY PASSWORD
        ----------------------------------------- */

        if (
            $user &&
            password_verify(
                $password,
                $user['password']
            )
        ) {

            /* -----------------------------------------
               REGENERATE SESSION ID
            ----------------------------------------- */

            session_regenerate_id(true);


            /* -----------------------------------------
               STORE USER SESSION
            ----------------------------------------- */

            $_SESSION['user_id'] =
                $user['id'];

            $_SESSION['first_name'] =
                $user['first_name'];

            $_SESSION['last_name'] =
                $user['last_name'];

            $_SESSION['email'] =
                $user['email'];


            header(
                'Location: index.php'
            );

            exit;

        } else {

            /*
             * Use one generic message so we don't
             * reveal whether an email exists.
             */

            $message =
                'Invalid email or password.';

            $messageType =
                'error';

        }

    }

}

?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Login | Mimic Haven Collectibles
    </title>

    <meta
        name="description"
        content="Login to your Mimic Haven Collectibles account."
    >

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>


<main class="auth-page">

    <div class="auth-card">


        <!-- LOGO -->

        <a
            href="index.php"
            class="auth-logo"
        >

            <?php if (file_exists(__DIR__ . '/logo.svg')): ?>

                <img
                    src="logo.svg"
                    alt="Mimic Haven Collectibles"
                >

            <?php else: ?>

                MIMIC HAVEN

            <?php endif; ?>

        </a>



        <!-- HEADER -->

        <div class="auth-header">

            <span>
                WELCOME BACK
            </span>

            <h1>
                Log
                <strong>In.</strong>
            </h1>

            <p>
                Sign in to manage your collection,
                orders, and pre-orders.
            </p>

        </div>



        <!-- MESSAGE -->

        <?php if ($message !== ''): ?>

            <div
                class="auth-message <?= e($messageType) ?>"
            >

                <?= e($message) ?>

            </div>

        <?php endif; ?>



        <!-- FORM -->

        <form
            method="POST"
            action="login.php"
            class="auth-form"
        >


            <div class="auth-field">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="your@email.com"
                    autocomplete="email"
                    required
                >

            </div>


            <div class="auth-field">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

            </div>


            <button
                type="submit"
                class="auth-submit"
            >
                Log In →
            </button>

        </form>



        <!-- REGISTER -->

        <p class="auth-switch">

            Don't have an account?

            <a href="register.php">
                Create one
            </a>

        </p>


        <a
            href="index.php"
            class="auth-back"
        >
            ← Back to Mimic Haven
        </a>

    </div>

</main>

</body>
</html>