<?php

/* =========================================================
   MIMIC HAVEN - REGISTRATION
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

/* =========================================================
   CSRF TOKEN
========================================================= */

if (
    empty($_SESSION['register_csrf'])
) {
    $_SESSION['register_csrf'] =
        bin2hex(
            random_bytes(32)
        );
}

$csrfToken =
    $_SESSION['register_csrf'];

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =====================================================
       VERIFY CSRF TOKEN
    ===================================================== */

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
            'Security validation failed. Please try again.';

        $messageType =
            'error';

    } else {

        $firstName =
            trim(
                $_POST['first_name'] ?? ''
            );

        $lastName =
            trim(
                $_POST['last_name'] ?? ''
            );

        $email =
            trim(
                $_POST['email'] ?? ''
            );

        $password =
            $_POST['password'] ?? '';

        $confirmPassword =
            $_POST['confirm_password'] ?? '';

        /* =================================================
           BASIC VALIDATION
        ================================================= */

        if (
            $firstName === '' ||
            $lastName === '' ||
            $email === '' ||
            $password === '' ||
            $confirmPassword === ''
        ) {

            $message =
                'Please complete all required fields.';

            $messageType =
                'error';

        } elseif (
            strlen($firstName) > 50 ||
            strlen($lastName) > 50
        ) {

            $message =
                'First name and last name must not exceed 50 characters.';

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

        } elseif (
            strlen($email) > 100
        ) {

            $message =
                'Email address must not exceed 100 characters.';

            $messageType =
                'error';

        } elseif (
            strlen($password) < 8
        ) {

            $message =
                'Password must be at least 8 characters long.';

            $messageType =
                'error';

        } elseif (
            !preg_match(
                '/[A-Z]/',
                $password
            )
        ) {

            $message =
                'Password must contain at least one uppercase letter.';

            $messageType =
                'error';

        } elseif (
            !preg_match(
                '/[a-z]/',
                $password
            )
        ) {

            $message =
                'Password must contain at least one lowercase letter.';

            $messageType =
                'error';

        } elseif (
            !preg_match(
                '/[0-9]/',
                $password
            )
        ) {

            $message =
                'Password must contain at least one number.';

            $messageType =
                'error';

        } elseif (
            !preg_match(
                '/[^A-Za-z0-9\s]/',
                $password
            )
        ) {

            $message =
                'Password must contain at least one special character.';

            $messageType =
                'error';

        } elseif (
            preg_match(
                '/\s/',
                $password
            )
        ) {

            $message =
                'Password must not contain spaces.';

            $messageType =
                'error';

        } elseif (
            $password !== $confirmPassword
        ) {

            $message =
                'Passwords do not match.';

            $messageType =
                'error';

        } else {

            /* =================================================
               CHECK IF EMAIL ALREADY EXISTS
            ================================================= */

            $checkStmt =
                $conn->prepare(
                    "SELECT id
                     FROM users
                     WHERE email = ?
                     LIMIT 1"
                );

            if (!$checkStmt) {

                $message =
                    'Unable to verify the account details. Please try again.';

                $messageType =
                    'error';

            } else {

                $checkStmt->bind_param(
                    "s",
                    $email
                );

                if (
                    !$checkStmt->execute()
                ) {

                    $message =
                        'Unable to verify the account details. Please try again.';

                    $messageType =
                        'error';

                    $checkStmt->close();

                } else {

                    $checkResult =
                        $checkStmt->get_result();

                    $checkStmt->close();

                    if (
                        $checkResult->num_rows > 0
                    ) {

                        $message =
                            'An account with this email already exists.';

                        $messageType =
                            'error';

                    } else {

                        /* =================================================
                           HASH PASSWORD
                        ================================================= */

                        $hashedPassword =
                            password_hash(
                                $password,
                                PASSWORD_DEFAULT
                            );

                        if (
                            $hashedPassword === false
                        ) {

                            $message =
                                'Unable to secure your password. Please try again.';

                            $messageType =
                                'error';

                        } else {

                            /* =================================================
                               INSERT USER
                            ================================================= */

                            $stmt =
                                $conn->prepare(
                                    "INSERT INTO users
                                    (
                                        first_name,
                                        last_name,
                                        email,
                                        password
                                    )
                                    VALUES (?, ?, ?, ?)"
                                );

                            if (!$stmt) {

                                $message =
                                    'Unable to create your account. Please try again.';

                                $messageType =
                                    'error';

                            } else {

                                $stmt->bind_param(
                                    "ssss",
                                    $firstName,
                                    $lastName,
                                    $email,
                                    $hashedPassword
                                );

                                if (
                                    $stmt->execute()
                                ) {

                                    $newUserId =
                                        $stmt->insert_id;

                                    $stmt->close();

                                    /* =================================================
                                       CREATE SESSION
                                    ================================================= */

                                    session_regenerate_id(
                                        true
                                    );

                                    $_SESSION['user_id'] =
                                        $newUserId;

                                    $_SESSION['first_name'] =
                                        $firstName;

                                    $_SESSION['last_name'] =
                                        $lastName;

                                    $_SESSION['email'] =
                                        $email;

                                    $_SESSION['role'] =
                                        'customer';

                                    $_SESSION['register_csrf'] =
                                        bin2hex(
                                            random_bytes(32)
                                        );

                                    header(
                                        'Location: index.php'
                                    );

                                    exit;

                                } else {

                                    $message =
                                        'Unable to create your account. Please try again.';

                                    $messageType =
                                        'error';

                                    $stmt->close();

                                }

                            }

                        }

                    }

                }

            }

        }

    }

}

?>

<!doctype html>

<html lang="en">

<head>

```
<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Create Account | Mimic Haven Collectibles
</title>

<link
    rel="stylesheet"
    href="style.css"
>
```

</head>

<body>

<main class="auth-page">

```
<div class="auth-card">

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


    <div class="auth-header">

        <span>
            JOIN THE HAVEN
        </span>

        <h1>
            Create
            <strong>Account.</strong>
        </h1>

        <p>
            Create your collector account to manage your
            orders and pre-orders.
        </p>

    </div>


    <?php if ($message !== ''): ?>

        <div
            class="auth-message <?= e($messageType) ?>"
        >
            <?= e($message) ?>
        </div>

    <?php endif; ?>


    <form
        method="POST"
        action="register.php"
        class="auth-form"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($csrfToken) ?>"
        >


        <div class="auth-form-row">

            <div class="auth-field">

                <label for="first_name">
                    First Name
                </label>

                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    maxlength="50"
                    required
                >

            </div>


            <div class="auth-field">

                <label for="last_name">
                    Last Name
                </label>

                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    maxlength="50"
                    required
                >

            </div>

        </div>


        <div class="auth-field">

            <label for="email">
                Email Address
            </label>

            <input
                type="email"
                id="email"
                name="email"
                maxlength="100"
                required
                autocomplete="email"
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
                minlength="8"
                required
                autocomplete="new-password"
                title="Password must contain at least 8 characters, one uppercase letter, one lowercase letter, one number, and one special character. Spaces are not allowed."
            >

            <small>
                Use at least 8 characters with uppercase,
                lowercase, number, and special character.
            </small>

        </div>


        <div class="auth-field">

            <label for="confirm_password">
                Confirm Password
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                required
                autocomplete="new-password"
            >

        </div>


        <button
            type="submit"
            class="auth-submit"
        >
            Create Account →
        </button>

    </form>


    <p class="auth-switch">

        Already have an account?

        <a href="login.php">
            Log in
        </a>

    </p>


    <a
        href="index.php"
        class="auth-back"
    >
        ← Back to Mimic Haven
    </a>

</div>
```

</main>

</body>
</html>
