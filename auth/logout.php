<?php
/**
 * auth/logout.php
 * POST-only logout with CSRF verification.
 */

define('BASE_URL', '/task%20manager/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only process POST requests with valid CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {

    // Clear all session data
    $_SESSION = [];

    // Destroy the session cookie
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }
    session_destroy();
}

header('Location: ' . BASE_URL . 'auth/login.php');
exit;
