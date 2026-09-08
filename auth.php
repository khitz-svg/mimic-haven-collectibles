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