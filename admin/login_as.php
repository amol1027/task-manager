<?php
/**
 * admin/login_as.php
 * Allows an admin to impersonate a user account.
 * Saves original admin session data and switches to the target user.
 */
define('BASE_URL', '/task%20manager/');
define('ADMIN_AREA', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_header.php';

$targetId = (int) ($_GET['user_id'] ?? 0);

if ($targetId <= 0) {
    $_SESSION['flash'][] = ['type' => 'error', 'msg' => 'Invalid user.'];
    header('Location: ' . BASE_URL . 'admin/users.php');
    exit;
}

$db   = getDB();
$stmt = $db->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
$stmt->execute([$targetId]);
$target = $stmt->fetch();

if (!$target) {
    $_SESSION['flash'][] = ['type' => 'error', 'msg' => 'User not found.'];
    header('Location: ' . BASE_URL . 'admin/users.php');
    exit;
}

// Store original admin info if not already impersonating
if (empty($_SESSION['admin_original_id'])) {
    $_SESSION['admin_original_id']   = $_SESSION['user_id'];
    $_SESSION['admin_original_name'] = $_SESSION['user_name'];
    $_SESSION['admin_name']          = $_SESSION['user_name']; // keep admin name visible in banner
}

// Switch session to target user
session_regenerate_id(true);
$_SESSION['user_id']   = $target['id'];
$_SESSION['user_name'] = $target['name'];
// Keep is_admin so admin bar stays visible
$_SESSION['is_admin']  = true;

$_SESSION['flash'][] = [
    'type' => 'success',
    'msg'  => 'Now viewing as ' . $target['name'] . '. Click "Back to Admin" to revert.'
];

header('Location: ' . BASE_URL . 'pages/dashboard.php');
exit;
