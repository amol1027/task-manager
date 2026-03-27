<?php
/**
 * pages/profile.php
 */

define('BASE_URL', '/task%20manager/');
$pageTitle = 'Profile';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$db     = getDB();
$userId = (int) $_SESSION['user_id'];

// Load current user
$stmt = $db->prepare('SELECT id, name, email FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    // Shouldn't happen, but safety net
    session_destroy();
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$profileErrors   = [];
$passwordErrors  = [];
$profileSuccess  = false;
$passwordSuccess = false;

// ── Update Name ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $profileErrors[] = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $profileErrors[] = 'Name cannot be empty.';
        } else {
            $upd = $db->prepare('UPDATE users SET name = ? WHERE id = ?');
            $upd->execute([$name, $userId]);
            $_SESSION['user_name'] = $name;
            $user['name']          = $name;
            $profileSuccess        = true;
            $_SESSION['flash'][]   = ['type' => 'success', 'msg' => 'Profile updated successfully.'];
        }
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Change Password ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $passwordErrors[] = 'Invalid CSRF token.';
    } else {
        $current    = $_POST['current_password'] ?? '';
        $newPass    = $_POST['new_password']     ?? '';
        $confirmNew = $_POST['confirm_password'] ?? '';

        // Load current hash
        $hashStmt = $db->prepare('SELECT password FROM users WHERE id = ?');
        $hashStmt->execute([$userId]);
        $hashRow  = $hashStmt->fetch();

        if (!password_verify($current, $hashRow['password'])) {
            $passwordErrors[] = 'Current password is incorrect.';
        }
        if (strlen($newPass) < 8) {
            $passwordErrors[] = 'New password must be at least 8 characters.';
        }
        if ($newPass !== $confirmNew) {
            $passwordErrors[] = 'New passwords do not match.';
        }

        if (empty($passwordErrors)) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $upd  = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
            $upd->execute([$hash, $userId]);
            $passwordSuccess       = true;
            $_SESSION['flash'][]   = ['type' => 'success', 'msg' => 'Password changed successfully.'];
        }
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 py-10 space-y-6">

    <!-- Page header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Account Profile</h1>
        <p class="text-sm text-gray-500 mt-0.5">Manage your personal details and password.</p>
    </div>

    <!-- ── Profile Info ─────────────────────────────────────────── -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-4">Personal Information</h2>

        <!-- Read-only email -->
        <div class="mb-5">
            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Email Address</label>
            <p class="text-sm text-gray-700 bg-gray-50 px-4 py-2.5 rounded-lg border border-gray-200">
                <?= htmlspecialchars($user['email']) ?>
            </p>
            <p class="text-xs text-gray-400 mt-1">Email address cannot be changed.</p>
        </div>

        <!-- Profile errors -->
        <?php if (!empty($profileErrors)): ?>
        <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700 space-y-1">
            <?php foreach ($profileErrors as $e): ?>
            <p>✕ <?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input id="name" type="text" name="name"
                    value="<?= htmlspecialchars($user['name']) ?>"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>

            <button type="submit" name="update_profile" value="1"
                class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors">
                Update Profile
            </button>
        </form>
    </div>

    <!-- ── Change Password ──────────────────────────────────────── -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-4">Change Password</h2>

        <!-- Password errors -->
        <?php if (!empty($passwordErrors)): ?>
        <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700 space-y-1">
            <?php foreach ($passwordErrors as $e): ?>
            <p>✕ <?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                <input id="current_password" type="password" name="current_password"
                    placeholder="Your current password"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                           placeholder-gray-400 transition">
            </div>

            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input id="new_password" type="password" name="new_password"
                    placeholder="Min. 8 characters"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                           placeholder-gray-400 transition">
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input id="confirm_password" type="password" name="confirm_password"
                    placeholder="Repeat new password"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                           placeholder-gray-400 transition">
            </div>

            <button type="submit" name="change_password" value="1"
                class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors">
                Change Password
            </button>
        </form>
    </div>

    <!-- Danger zone -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-1">Session</h2>
        <p class="text-sm text-gray-500 mb-4">Sign out of your account on this device.</p>
        <form method="POST" action="<?= BASE_URL ?>auth/logout.php">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button type="submit"
                class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                Sign Out
            </button>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
