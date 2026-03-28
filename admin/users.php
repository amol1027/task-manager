<?php
/**
 * admin/users.php — Manage all users
 */
define('BASE_URL', '/task%20manager/');
define('ADMIN_AREA', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_header.php';

$db     = getDB();
$errors = [];

// ── POST actions ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'][] = ['type' => 'error', 'msg' => 'Invalid CSRF token.'];
        header('Location: ' . BASE_URL . 'admin/users.php');
        exit;
    }

    $action  = $_POST['action'] ?? '';
    $targetId = (int) ($_POST['target_id'] ?? 0);
    $selfId   = (int) $_SESSION['user_id'];

    if ($targetId <= 0) {
        $_SESSION['flash'][] = ['type' => 'error', 'msg' => 'Invalid user.'];
        header('Location: ' . BASE_URL . 'admin/users.php');
        exit;
    }

    if ($action === 'delete') {
        if ($targetId === $selfId) {
            $_SESSION['flash'][] = ['type' => 'error', 'msg' => 'You cannot delete your own account.'];
        } else {
            $db->prepare('DELETE FROM users WHERE id = ?')->execute([$targetId]);
            $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'User deleted successfully.'];
        }
    } elseif ($action === 'promote') {
        $db->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$targetId]);
        $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'User promoted to admin.'];
    } elseif ($action === 'demote') {
        if ($targetId === $selfId) {
            $_SESSION['flash'][] = ['type' => 'error', 'msg' => 'You cannot demote yourself.'];
        } else {
            $db->prepare("UPDATE users SET role = 'user' WHERE id = ?")->execute([$targetId]);
            $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'User demoted to regular user.'];
        }
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header('Location: ' . BASE_URL . 'admin/users.php');
    exit;
}

// ── Search / list ────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$sql    = "SELECT u.id, u.name, u.email, u.role, u.created_at,
                  COUNT(t.id) AS task_count
           FROM users u
           LEFT JOIN tasks t ON t.user_id = u.id";
$params = [];

if ($search !== '') {
    $sql .= " WHERE (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    <!-- Heading -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manage Users</h1>
            <p class="text-sm text-gray-500 mt-0.5"><?= count($users) ?> user<?= count($users) !== 1 ? 's' : '' ?> found</p>
        </div>
        <!-- Search -->
        <form method="GET" class="flex gap-2">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Search name or email…"
                   class="px-4 py-2 text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
            <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition-colors font-medium">Search</button>
            <?php if ($search !== ''): ?>
            <a href="?" class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-600 transition-colors">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                        <th class="px-6 py-3 text-left">User</th>
                        <th class="px-6 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-center">Role</th>
                        <th class="px-4 py-3 text-center">Tasks</th>
                        <th class="px-6 py-3 text-left">Joined</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="text-center py-12 text-gray-400">No users found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($users as $u):
                        $isSelf = ((int)$u['id'] === (int)$_SESSION['user_id']);
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <!-- Avatar + Name -->
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full <?= $u['role']==='admin' ? 'bg-purple-100 text-purple-700' : 'bg-indigo-100 text-indigo-700' ?> flex items-center justify-center font-bold text-sm flex-shrink-0">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars($u['name']) ?></p>
                                    <?php if ($isSelf): ?><p class="text-xs text-indigo-500">(you)</p><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars($u['email']) ?></td>
                        <!-- Role badge -->
                        <td class="px-4 py-4 text-center">
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $u['role']==='admin' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600' ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center font-semibold text-gray-700"><?= (int)$u['task_count'] ?></td>
                        <td class="px-6 py-4 text-gray-400"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        <!-- Actions -->
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2 flex-wrap">

                                <!-- Login As -->
                                <a href="<?= BASE_URL ?>admin/login_as.php?user_id=<?= $u['id'] ?>"
                                   onclick="return confirm('Login as <?= htmlspecialchars(addslashes($u['name'])) ?>?')"
                                   class="text-xs px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg font-medium transition-colors">
                                    🔑 Login As
                                </a>

                                <!-- View Tasks -->
                                <a href="<?= BASE_URL ?>admin/tasks.php?user_id=<?= $u['id'] ?>"
                                   class="text-xs px-3 py-1.5 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                                    📋 Tasks
                                </a>

                                <!-- Promote / Demote -->
                                <?php if (!$isSelf): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="target_id"  value="<?= $u['id'] ?>">
                                    <?php if ($u['role'] === 'user'): ?>
                                    <input type="hidden" name="action" value="promote">
                                    <button onclick="return confirm('Promote <?= htmlspecialchars(addslashes($u['name'])) ?> to admin?')"
                                            class="text-xs px-3 py-1.5 bg-green-50 text-green-700 hover:bg-green-100 rounded-lg font-medium transition-colors">
                                        ⬆ Promote
                                    </button>
                                    <?php else: ?>
                                    <input type="hidden" name="action" value="demote">
                                    <button onclick="return confirm('Demote <?= htmlspecialchars(addslashes($u['name'])) ?> to regular user?')"
                                            class="text-xs px-3 py-1.5 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 rounded-lg font-medium transition-colors">
                                        ⬇ Demote
                                    </button>
                                    <?php endif; ?>
                                </form>

                                <!-- Delete -->
                                <form method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="target_id"  value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action"     value="delete">
                                    <button onclick="return confirm('Permanently delete <?= htmlspecialchars(addslashes($u['name'])) ?> and all their tasks?')"
                                            class="text-xs px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg font-medium transition-colors">
                                        🗑 Delete
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span class="text-xs text-gray-300 italic">—</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
