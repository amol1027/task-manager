<?php
/**
 * includes/admin_header.php
 * Admin-specific header: requires admin role, renders dark sidebar nav.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Must be logged in
if (empty($_SESSION['user_id']) && empty($_SESSION['admin_original_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

// Must be admin (or impersonating admin)
if (empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . 'pages/dashboard.php');
    exit;
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$adminName   = htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Admin');
$currentPage = basename($_SERVER['PHP_SELF']);

function adminNavLink(string $href, string $icon, string $label, string $current): string {
    $active = ($current === basename($href))
        ? 'bg-indigo-700 text-white'
        : 'text-indigo-100 hover:bg-indigo-700 hover:text-white';
    return "<a href=\"{$href}\" class=\"flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors {$active}\">
                <span class=\"text-lg leading-none\">{$icon}</span>
                <span>{$label}</span>
            </a>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — Admin Panel' : 'Admin Panel' ?></title>
    <meta name="description" content="Goal Manager Admin Panel">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] } } }
        }
    </script>
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui; }
        .flash-msg { animation: fadeIn .3s ease; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }
        /* Sidebar transitions */
        #sidebar { transition: transform .25s ease; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex">

<!-- ── SIDEBAR ───────────────────────────────────────────────── -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-indigo-800 flex flex-col shadow-xl
                            -translate-x-full lg:translate-x-0 transition-transform">

    <!-- Logo -->
    <div class="flex items-center gap-3 px-5 py-5 border-b border-indigo-700">
        <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white text-indigo-700 font-bold text-sm">A</span>
        <div>
            <p class="text-white font-bold text-sm leading-tight">Admin Panel</p>
            <p class="text-indigo-300 text-xs">Goal Manager</p>
        </div>
        <!-- Close button (mobile) -->
        <button onclick="toggleSidebar()" class="ml-auto text-indigo-300 hover:text-white lg:hidden">✕</button>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <?= adminNavLink(BASE_URL . 'admin/index.php',  '🏠', 'Dashboard',    $currentPage) ?>
        <?= adminNavLink(BASE_URL . 'admin/users.php',  '👥', 'Users',        $currentPage) ?>
        <?= adminNavLink(BASE_URL . 'admin/tasks.php',  '📋', 'All Tasks',    $currentPage) ?>

        <div class="pt-4 border-t border-indigo-700 mt-4">
            <?php if (!empty($_SESSION['admin_original_id'])): ?>
            <a href="<?= BASE_URL ?>admin/revert.php"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-indigo-100 hover:bg-indigo-700 hover:text-white transition-colors">
                <span class="text-lg leading-none">↩️</span>
                <span>Back to Admin</span>
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>pages/dashboard.php"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-indigo-100 hover:bg-indigo-700 hover:text-white transition-colors">
                <span class="text-lg leading-none">👤</span>
                <span>My Tasks</span>
            </a>
        </div>
    </nav>

    <!-- User info + logout -->
    <div class="px-3 py-4 border-t border-indigo-700">
        <div class="flex items-center gap-3 px-3 py-2 mb-2">
            <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-sm">
                <?= strtoupper(substr($_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="min-w-0">
                <p class="text-white text-xs font-semibold truncate"><?= $adminName ?></p>
                <p class="text-indigo-300 text-xs">Administrator</p>
            </div>
        </div>
        <form method="POST" action="<?= BASE_URL ?>auth/logout.php">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button type="submit" class="w-full text-left flex items-center gap-3 px-4 py-2 text-sm text-indigo-200 hover:text-white hover:bg-indigo-700 rounded-lg transition-colors font-medium">
                <span>🚪</span> Logout
            </button>
        </form>
    </div>
</aside>

<!-- Sidebar overlay (mobile) -->
<div id="sidebar-overlay" onclick="toggleSidebar()"
     class="fixed inset-0 z-40 bg-black bg-opacity-50 hidden lg:hidden"></div>

<!-- ── MAIN CONTENT ──────────────────────────────────────────── -->
<div class="flex-1 flex flex-col lg:ml-64 min-h-screen">

    <!-- Top bar -->
    <header class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">
            <!-- Mobile hamburger -->
            <button onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-gray-700 p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="hidden sm:flex items-center gap-2 text-sm text-gray-600">
                <span class="text-indigo-600 font-semibold">Admin</span>
                <span class="text-gray-400">/</span>
                <span><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Panel' ?></span>
            </div>

            <?php if (!empty($_SESSION['admin_original_id'])): ?>
            <div class="flex items-center gap-2 text-xs bg-amber-50 border border-amber-200 text-amber-700 px-3 py-1.5 rounded-full font-medium">
                <span>👁️</span>
                Viewing as <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                <a href="<?= BASE_URL ?>admin/revert.php" class="ml-2 underline hover:no-underline">Revert</a>
            </div>
            <?php endif; ?>

            <div class="ml-auto flex items-center gap-3">
                <a href="<?= BASE_URL ?>pages/dashboard.php" class="text-xs text-indigo-600 hover:underline">My Tasks</a>
                <span class="text-sm text-gray-500">👋 <?= $adminName ?></span>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if (!empty($_SESSION['flash'])): ?>
    <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 mt-4">
        <?php foreach ($_SESSION['flash'] as $flash): ?>
        <div class="flash-msg mb-2 flex items-start gap-3 px-4 py-3 rounded-lg text-sm font-medium <?= $flash['type']==='success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-700' ?>">
            <span><?= $flash['type']==='success' ? '✓' : '✕' ?></span>
            <span><?= htmlspecialchars($flash['msg']) ?></span>
            <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100 text-lg leading-none">&times;</button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Page Content -->
    <main class="flex-1">

<script>
function toggleSidebar() {
    const sb  = document.getElementById('sidebar');
    const ov  = document.getElementById('sidebar-overlay');
    const open = !sb.classList.contains('-translate-x-full');
    sb.classList.toggle('-translate-x-full', open);
    ov.classList.toggle('hidden', open);
}
</script>
