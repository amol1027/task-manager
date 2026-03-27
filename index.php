<?php
/**
 * index.php — Entry point
 * Routes to dashboard if logged in, otherwise to login page.
 */

define('BASE_URL', '/task%20manager/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'pages/dashboard.php');
} else {
    header('Location: ' . BASE_URL . 'auth/login.php');
}
exit;
