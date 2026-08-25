<?php

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
| Start the session only if one is not already active.
| This prevents "session already active" warnings.
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Application Settings
|--------------------------------------------------------------------------
*/

define(
    'APP_NAME',
    'LuxeStay Hotel'
);

define(
    'BASE_URL',
    'http://localhost:8000'
);


/*
|--------------------------------------------------------------------------
| Authentication Helpers
|--------------------------------------------------------------------------
*/

/**
 * Check whether a user is currently logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user'])
        && is_array($_SESSION['user'])
        && isset($_SESSION['user']['id']);
}


/**
 * Check whether the logged-in user is an administrator.
 */
function isAdmin(): bool
{
    return isLoggedIn()
        && isset($_SESSION['user']['role'])
        && $_SESSION['user']['role'] === 'admin';
}


/**
 * Require the user to be logged in.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}


/**
 * Require the user to be an administrator.
 */
function requireAdmin(): void
{
    if (!isAdmin()) {
        header('Location: /login.php');
        exit;
    }
}