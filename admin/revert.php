<?php
/**
 * admin/revert.php
 * Restores the original admin session after impersonating a user.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

define('BASE_URL', '/task%20manager/');

if (empty($_SESSION['admin_original_id'])) {
    // Not impersonating — just go to admin panel
    header('Location: ' . BASE_URL . 'admin/index.php');
    exit;
}

// Restore admin session
session_regenerate_id(true);
$_SESSION['user_id']   = $_SESSION['admin_original_id'];
$_SESSION['user_name'] = $_SESSION['admin_original_name'];
$_SESSION['is_admin']  = true;

// Clear impersonation data
unset($_SESSION['admin_original_id'], $_SESSION['admin_original_name']);

$_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Reverted to your admin account.'];
header('Location: ' . BASE_URL . 'admin/index.php');
exit;
