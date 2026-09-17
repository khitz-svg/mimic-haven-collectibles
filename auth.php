<?php

/* =========================================================
   MIMIC HAVEN - AUTHENTICATION HELPER
========================================================= */

/*
 * Start the session only if one has not already been started.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   CART STORAGE KEY
========================================================= */

/*
 * Give JavaScript the correct cart storage key.
 *
 * Logged-in users get:
 * mimicHavenCart_1
 * mimicHavenCart_2
 * mimicHavenCart_3
 *
 * Guests get:
 * mimicHavenGuestCart
 */

$cartStorageKey =
    isset($_SESSION['user_id'])
        ? 'mimicHavenCart_' . (int) $_SESSION['user_id']
        : 'mimicHavenGuestCart';


/*
 * Store the cart key in a normal browser cookie
 * so script.js can read it.
 */

if (!headers_sent()) {

    setcookie(
        'mimicHavenCartStorageKey',
        $cartStorageKey,
        [
            'expires' => time() + (60 * 60 * 24 * 30),
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS'])
                && $_SERVER['HTTPS'] !== 'off',
            'httponly' => false,
            'samesite' => 'Lax'
        ]
    );

}


/* =========================================================
   CHECK LOGIN
========================================================= */

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}


/* =========================================================
   GET CURRENT USER ID
========================================================= */

function currentUserId(): ?int
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    return (int) $_SESSION['user_id'];
}


/* =========================================================
   GET CURRENT USER NAME
========================================================= */

function currentUserName(): string
{
    return trim(
        ($_SESSION['first_name'] ?? '') .
        ' ' .
        ($_SESSION['last_name'] ?? '')
    );
}


/* =========================================================
   GET CURRENT FIRST NAME
========================================================= */

function currentFirstName(): string
{
    return $_SESSION['first_name'] ?? '';
}


/* =========================================================
   REQUIRE LOGIN
========================================================= */

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}


/* =========================================================
   LOGOUT
========================================================= */

function logoutUser(): void
{
    $_SESSION = [];

    /*
     * Clear the cart-key cookie when the user logs out.
     */

    if (!headers_sent()) {

        setcookie(
            'mimicHavenCartStorageKey',
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => !empty($_SERVER['HTTPS'])
                    && $_SERVER['HTTPS'] !== 'off',
                'httponly' => false,
                'samesite' => 'Lax'
            ]
        );

    }


    if (ini_get('session.use_cookies')) {

        $params =
            session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );

    }

    session_destroy();
}