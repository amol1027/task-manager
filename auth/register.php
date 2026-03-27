<?php
/**
 * auth/register.php
 */

// Base URL helper — adjust if your folder name differs
define('BASE_URL', '/task%20manager/');
define('NO_AUTH', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$errors = [];
$old    = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token. Please try again.';
    } else {
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $confirm  = $_POST['confirm']       ?? '';
        $old      = ['name' => $name, 'email' => $email];

        // Validation
        if ($name === '')                              $errors[] = 'Full name is required.';
        if ($email === '')                             $errors[] = 'Email address is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if ($password === '')                          $errors[] = 'Password is required.';
        elseif (strlen($password) < 8)                 $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm)                    $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $db = getDB();
            // Duplicate email check
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with that email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $ins  = $db->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
                $ins->execute([$name, $email, $hash]);

                $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Account created! Please log in.'];
                header('Location: ' . BASE_URL . 'auth/login.php');
                exit;
            }
        }
    }
    // Regenerate CSRF token after failed attempt
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$pageTitle = 'Register';
?>

<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-md border border-gray-100 p-8">

        <!-- Header -->
        <div class="mb-8 text-center">
            <span class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-600 text-white text-xl font-bold mb-4">T</span>
            <h1 class="text-2xl font-bold text-gray-900">Create your account</h1>
            <p class="text-sm text-gray-500 mt-1">Start managing your tasks today.</p>
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

            <!-- Full Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input id="name" type="text" name="name"
                    value="<?= htmlspecialchars($old['name']) ?>"
                    placeholder="Alice Johnson"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                           placeholder-gray-400 transition">
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input id="email" type="email" name="email"
                    value="<?= htmlspecialchars($old['email']) ?>"
                    placeholder="alice@example.com"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                           placeholder-gray-400 transition">
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input id="password" type="password" name="password"
                    placeholder="Min. 8 characters"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                           placeholder-gray-400 transition">
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="confirm" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input id="confirm" type="password" name="confirm"
                    placeholder="Repeat password"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                           placeholder-gray-400 transition">
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg
                       transition-colors text-sm mt-2">
                Create Account
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Already have an account?
            <a href="<?= BASE_URL ?>auth/login.php" class="text-indigo-600 hover:underline font-medium">Sign in</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
