<?php
/**
 * auth/login.php
 */

define('BASE_URL', '/task%20manager/');
define('NO_AUTH', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$errors = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token. Please try again.';
    } else {
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $oldEmail = $email;

        if ($email === '')    $errors[] = 'Email is required.';
        if ($password === '') $errors[] = 'Password is required.';

        if (empty($errors)) {
            $db   = getDB();
            $stmt = $db->prepare('SELECT id, name, password, role FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Harden session
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['is_admin']  = ($user['role'] === 'admin');
                $_SESSION['admin_name'] = $user['name'];
                // Regenerate CSRF for this new session
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Welcome back, ' . $user['name'] . '!'];

                // Redirect admins to admin panel
                if ($user['role'] === 'admin') {
                    header('Location: ' . BASE_URL . 'admin/index.php');
                } else {
                    header('Location: ' . BASE_URL . 'pages/dashboard.php');
                }
                exit;
            } else {
                $errors[] = 'Invalid email or password.';
            }
        }
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$pageTitle = 'Login';
?>

<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-md border border-gray-100 p-8">

        <!-- Header -->
        <div class="mb-8 text-center">
            <span class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-600 text-white text-xl font-bold mb-4">T</span>
            <h1 class="text-2xl font-bold text-gray-900">Sign in to Goal Manager</h1>
            <p class="text-sm text-gray-500 mt-1">Manage your tasks efficiently.</p>
        </div>

        <!-- Errors -->
        <?php if (!empty($errors)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700 space-y-1">
            <?php foreach ($errors as $e): ?>
            <p>✕ <?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="" novalidate class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input id="email" type="email" name="email"
                    value="<?= htmlspecialchars($oldEmail) ?>"
                    placeholder="alice@example.com"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                           placeholder-gray-400 transition">
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input id="password" type="password" name="password"
                    placeholder="Your password"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                           placeholder-gray-400 transition">
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg
                       transition-colors text-sm mt-2">
                Sign In
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Don't have an account?
            <a href="<?= BASE_URL ?>auth/register.php" class="text-indigo-600 hover:underline font-medium">Create one</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
