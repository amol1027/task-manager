<?php
/**
 * admin/tasks.php — View all tasks across all users
 */
define('BASE_URL', '/task%20manager/');
define('ADMIN_AREA', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

// ── POST: admin delete any task ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task_id'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'][] = ['type' => 'error', 'msg' => 'Invalid CSRF token.'];
    } else {
        $tid = (int) $_POST['delete_task_id'];
        $db->prepare('DELETE FROM tasks WHERE id = ?')->execute([$tid]);
        $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Task deleted.'];
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header('Location: ' . BASE_URL . 'admin/tasks.php' . (isset($_GET['user_id']) ? '?user_id='.(int)$_GET['user_id'] : ''));
    exit;
}

// ── Filters ───────────────────────────────────────────────────
$filterUser   = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$filterStatus = in_array($_GET['status'] ?? '', ['','pending','in_progress','completed']) ? ($_GET['status'] ?? '') : '';
$search       = trim($_GET['q'] ?? '');

$sql    = "SELECT t.*, u.name AS user_name, u.email AS user_email
           FROM tasks t
           JOIN users u ON u.id = t.user_id
           WHERE 1=1";
$params = [];

if ($filterUser > 0) {
    $sql     .= " AND t.user_id = ?";
    $params[] = $filterUser;
}
if ($filterStatus !== '') {
    $sql     .= " AND t.status = ?";
    $params[] = $filterStatus;
}
if ($search !== '') {
    $sql     .= " AND (t.title LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY t.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// For filtering by user name (header)
$filterUserName = '';
if ($filterUser > 0) {
    $u = $db->prepare('SELECT name FROM users WHERE id = ?');
    $u->execute([$filterUser]);
    $filterUserName = $u->fetchColumn();
}

// Users list for filter dropdown
$allUsers = $db->query("SELECT id, name FROM users ORDER BY name")->fetchAll();

function adminPriorityBadge(string $p): string {
    $map = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-yellow-100 text-yellow-700','high'=>'bg-red-100 text-red-700'];
    $cls = $map[$p] ?? 'bg-gray-100 text-gray-600';
    return "<span class=\"inline-block text-xs font-semibold px-2 py-0.5 rounded-full $cls\">" . ucfirst($p) . "</span>";
}
function adminStatusBadge(string $s): string {
    $map = ['pending'=>'bg-gray-100 text-gray-600','in_progress'=>'bg-blue-100 text-blue-700','completed'=>'bg-green-100 text-green-700'];
    $cls = $map[$s] ?? 'bg-gray-100 text-gray-600';
    return "<span class=\"inline-block text-xs font-medium px-2 py-0.5 rounded-full $cls\">" . ucwords(str_replace('_', ' ', $s)) . "</span>";
}

$pageTitle = 'All Tasks';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    <!-- Heading -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <?= $filterUserName ? 'Tasks for ' . htmlspecialchars($filterUserName) : 'All Tasks' ?>
            </h1>
            <p class="text-sm text-gray-500 mt-0.5"><?= count($tasks) ?> task<?= count($tasks) !== 1 ? 's' : '' ?></p>
        </div>
        <?php if ($filterUser): ?>
        <a href="<?= BASE_URL ?>admin/tasks.php" class="text-sm text-indigo-600 hover:underline">← View all tasks</a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
        <form method="GET" class="flex flex-wrap gap-3 items-end w-full">

            <!-- Search -->
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs text-gray-500 mb-1 font-medium">Search</label>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                       placeholder="Task title or user name…"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- User filter -->
            <div>
                <label class="block text-xs text-gray-500 mb-1 font-medium">User</label>
                <select name="user_id" class="px-3 py-2 text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All users</option>
                    <?php foreach ($allUsers as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $filterUser == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status filter -->
            <div>
                <label class="block text-xs text-gray-500 mb-1 font-medium">Status</label>
                <select name="status" class="px-3 py-2 text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    <option value="pending"     <?= $filterStatus==='pending'     ? 'selected':'' ?>>Pending</option>
                    <option value="in_progress" <?= $filterStatus==='in_progress' ? 'selected':'' ?>>In Progress</option>
                    <option value="completed"   <?= $filterStatus==='completed'   ? 'selected':'' ?>>Completed</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition-colors font-medium">Filter</button>
                <a href="?" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm rounded-lg transition-colors">Clear</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                        <th class="px-6 py-3 text-left">Task</th>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Priority</th>
                        <th class="px-4 py-3 text-left">Due Date</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($tasks)): ?>
                    <tr><td colspan="6" class="text-center py-12 text-gray-400">No tasks found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($tasks as $task):
                        $due = $task['due_date'] ? new DateTime($task['due_date']) : null;
                        $today = new DateTime('today');
                        $overdue = $due && $due < $today && $task['status'] !== 'completed';
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-800 max-w-xs truncate"><?= htmlspecialchars($task['title']) ?></p>
                            <?php if (!empty($task['description'])): ?>
                            <p class="text-xs text-gray-400 truncate max-w-xs mt-0.5"><?= htmlspecialchars($task['description']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                    <?= strtoupper(substr($task['user_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($task['user_name']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center"><?= adminStatusBadge($task['status']) ?></td>
                        <td class="px-4 py-4 text-center"><?= adminPriorityBadge($task['priority']) ?></td>
                        <td class="px-4 py-4 text-sm <?= $overdue ? 'text-red-500 font-semibold' : 'text-gray-400' ?>">
                            <?= $due ? '📅 ' . $due->format('M j, Y') : '—' ?>
                            <?= $overdue ? ' ⚠️' : '' ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <form method="POST" class="inline">
                                <input type="hidden" name="csrf_token"     value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="delete_task_id" value="<?= $task['id'] ?>">
                                <?php if ($filterUser): ?><input type="hidden" name="user_id" value="<?= $filterUser ?>"><?php endif; ?>
                                <button onclick="return confirm('Delete task: <?= htmlspecialchars(addslashes($task['title'])) ?>?')"
                                        class="text-xs px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg font-medium transition-colors">
                                    🗑 Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
