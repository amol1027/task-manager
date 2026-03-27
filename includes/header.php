<?php
/**
 * includes/header.php
 * - Starts session
 * - Auth guard: redirects to login if not logged in
 * - Renders Tailwind nav + flash messages
 *
 * Pages that do NOT need auth (login, register) must define
 * define('NO_AUTH', true) BEFORE including this file.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth guard
if (!defined('NO_AUTH') && empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

// CSRF helper — generate token once per session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Resolve current page for active nav link
$currentPage = basename($_SERVER['PHP_SELF']);
function navLink(string $href, string $label, string $current): string {
    $active = ($current === basename($href))
        ? 'text-indigo-600 font-semibold'
        : 'text-gray-600 hover:text-indigo-600';
    return "<a href=\"{$href}\" class=\"text-sm transition-colors {$active}\">{$label}</a>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — TaskFlow' : 'TaskFlow' ?></title>
    <meta name="description" content="TaskFlow — a clean, minimal task management app.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui; }
        .flash-msg { animation: fadeIn .3s ease; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

<?php if (!defined('NO_AUTH')): ?>
<!-- ── Navigation ──────────────────────────────────── -->
<nav class="fixed top-0 inset-x-0 z-50 bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
        <!-- Logo -->
        <a href="<?= BASE_URL ?>pages/dashboard.php" class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-600 text-white text-sm font-bold">T</span>
            <span class="text-gray-900 font-bold text-lg tracking-tight">TaskFlow</span>
        </a>

        <!-- Links (desktop) -->
        <div class="hidden sm:flex items-center gap-6">
            <?= navLink(BASE_URL . 'pages/dashboard.php', 'Dashboard',  $currentPage) ?>
            <?= navLink(BASE_URL . 'pages/add_task.php',  'Add Task',   $currentPage) ?>
            <?= navLink(BASE_URL . 'pages/profile.php',   'Profile',    $currentPage) ?>
        </div>

        <!-- Right side -->
        <div class="flex items-center gap-4">
            <span class="hidden sm:block text-sm text-gray-500">
                👋 <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>
            </span>
            <form method="POST" action="<?= BASE_URL ?>auth/logout.php">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit"
                    class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-lg transition-colors font-medium">
                    Logout
                </button>
            </form>
        </div>
    </div>
</nav>
<!-- spacer -->
<div class="h-16"></div>
<?php endif; ?>

<!-- ── Flash Messages ──────────────────────────────── -->
<?php if (!empty($_SESSION['flash'])): ?>
<div class="max-w-6xl mx-auto px-4 sm:px-6 mt-4">
    <?php foreach ($_SESSION['flash'] as $flash): ?>
    <div class="flash-msg mb-2 flex items-start gap-3 px-4 py-3 rounded-lg text-sm font-medium
        <?= $flash['type'] === 'success'
            ? 'bg-green-50 border border-green-200 text-green-800'
            : 'bg-red-50 border border-red-200 text-red-700' ?>">
        <span><?= $flash['type'] === 'success' ? '✓' : '✕' ?></span>
        <span><?= htmlspecialchars($flash['msg']) ?></span>
        <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100 text-lg leading-none">&times;</button>
    </div>
    <?php endforeach; ?>
</div>
<?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- ── Page Content ── -->
<main class="flex-1">
