<?php
/**
 * pages/dashboard.php
 * Protected: shows task stats, filterable task grid, and delete handler.
 */

define('BASE_URL', '/task%20manager/');
$pageTitle = 'Dashboard';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$db     = getDB();
$userId = (int) $_SESSION['user_id'];

// ── DELETE (POST) ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task_id'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'][] = ['type' => 'error', 'msg' => 'Invalid CSRF token.'];
    } else {
        $tid  = (int) $_POST['delete_task_id'];
        $stmt = $db->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
        $stmt->execute([$tid, $userId]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Task deleted successfully.'];
        } else {
            $_SESSION['flash'][] = ['type' => 'error', 'msg' => 'Task not found or permission denied.'];
        }
    }
    header('Location: ' . BASE_URL . 'pages/dashboard.php');
    exit;
}

// ── FILTER ───────────────────────────────────────────────────────
$allowed  = ['', 'pending', 'in_progress', 'completed'];
$filter   = in_array($_GET['status'] ?? '', $allowed) ? ($_GET['status'] ?? '') : '';

$sql    = 'SELECT * FROM tasks WHERE user_id = ?';
$params = [$userId];
if ($filter !== '') {
    $sql     .= ' AND status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY
    CASE priority WHEN "high" THEN 1 WHEN "medium" THEN 2 ELSE 3 END,
    FIELD(status,"in_progress","pending","completed"),
    due_date ASC';

$taskStmt = $db->prepare($sql);
$taskStmt->execute($params);
$tasks = $taskStmt->fetchAll();

// ── STATS ────────────────────────────────────────────────────────
$statsStmt = $db->prepare(
    'SELECT
        COUNT(*) AS total,
        SUM(status="pending")     AS pending,
        SUM(status="in_progress") AS in_progress,
        SUM(status="completed")   AS completed
     FROM tasks WHERE user_id = ?'
);
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch();

// ── HELPERS ──────────────────────────────────────────────────────
function priorityBadge(string $p): string {
    $map = [
        'low'    => 'bg-green-100 text-green-700',
        'medium' => 'bg-yellow-100 text-yellow-700',
        'high'   => 'bg-red-100 text-red-700',
    ];
    $cls = $map[$p] ?? 'bg-gray-100 text-gray-600';
    return "<span class=\"inline-block text-xs font-semibold px-2 py-0.5 rounded-full {$cls}\">" . ucfirst($p) . "</span>";
}

function statusBadge(string $s): string {
    $map = [
        'pending'     => 'bg-gray-100 text-gray-600',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'completed'   => 'bg-green-100 text-green-700',
    ];
    $cls   = $map[$s] ?? 'bg-gray-100 text-gray-600';
    $label = ucwords(str_replace('_', ' ', $s));
    return "<span class=\"inline-block text-xs font-medium px-2 py-0.5 rounded-full {$cls}\">{$label}</span>";
}

function filterTab(string $label, string $value, string $current): string {
    $active = ($current === $value)
        ? 'bg-indigo-600 text-white shadow-sm'
        : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200';
    $qs = $value ? '?status=' . urlencode($value) : '?';
    return "<a href=\"{$qs}\" class=\"px-4 py-1.5 text-sm rounded-lg transition-colors {$active}\">{$label}</a>";
}
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

    <!-- Page heading -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">My Tasks</h1>
            <p class="text-sm text-gray-500 mt-0.5">Organize, track, and conquer your work.</p>
        </div>
        <a href="<?= BASE_URL ?>pages/add_task.php"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold
                   px-5 py-2.5 rounded-lg transition-colors">
            <span class="text-lg leading-none">+</span> Add Task
        </a>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        <?php
        $statCards = [
            ['label' => 'Total Tasks',  'value' => $stats['total'],       'color' => 'indigo'],
            ['label' => 'Pending',      'value' => $stats['pending'],     'color' => 'gray'],
            ['label' => 'In Progress',  'value' => $stats['in_progress'], 'color' => 'blue'],
            ['label' => 'Completed',    'value' => $stats['completed'],   'color' => 'green'],
        ];
        foreach ($statCards as $card):
        ?>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col gap-1">
            <span class="text-3xl font-bold text-<?= $card['color'] ?>-600"><?= (int)$card['value'] ?></span>
            <span class="text-xs text-gray-500 font-medium uppercase tracking-wide"><?= $card['label'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filter Tabs -->
    <div class="flex flex-wrap gap-2 mb-6">
        <?= filterTab('All',         '',            $filter) ?>
        <?= filterTab('Pending',     'pending',     $filter) ?>
        <?= filterTab('In Progress', 'in_progress', $filter) ?>
        <?= filterTab('Completed',   'completed',   $filter) ?>
    </div>

    <!-- Task Grid -->
    <?php if (empty($tasks)): ?>
    <div class="text-center py-20 text-gray-400">
        <div class="text-5xl mb-4">📋</div>
        <p class="text-lg font-medium text-gray-500">No tasks found</p>
        <p class="text-sm mt-1">
            <?= $filter ? 'No tasks match this filter.' : 'Click <strong>Add Task</strong> to get started.' ?>
        </p>
    </div>
    <?php else: ?>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($tasks as $task): ?>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-shadow">

            <!-- Title + priority -->
            <div class="flex items-start justify-between gap-2">
                <h2 class="text-sm font-semibold text-gray-900 leading-snug flex-1">
                    <?= htmlspecialchars($task['title']) ?>
                </h2>
                <?= priorityBadge($task['priority']) ?>
            </div>

            <!-- Description -->
            <?php if (!empty($task['description'])): ?>
            <p class="text-xs text-gray-500 line-clamp-2 leading-relaxed">
                <?= htmlspecialchars($task['description']) ?>
            </p>
            <?php endif; ?>

            <!-- Status + due date -->
            <div class="flex items-center gap-2 flex-wrap">
                <?= statusBadge($task['status']) ?>
                <?php if ($task['due_date']): ?>
                <?php
                $due   = new DateTime($task['due_date']);
                $today = new DateTime('today');
                $diff  = (int)$today->diff($due)->format('%R%a');
                $dueCls = ($diff < 0 && $task['status'] !== 'completed')
                    ? 'text-red-500 font-semibold'
                    : 'text-gray-400';
                ?>
                <span class="text-xs <?= $dueCls ?> ml-auto">
                    📅 <?= htmlspecialchars($due->format('M j, Y')) ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 pt-1 border-t border-gray-100 mt-auto">
                <a href="<?= BASE_URL ?>pages/edit_task.php?id=<?= $task['id'] ?>"
                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
                    ✏️ Edit
                </a>

                <!-- Delete -->
                <form method="POST" action="<?= BASE_URL ?>pages/dashboard.php" class="ml-auto"
                    onsubmit="return confirm('Delete this task? This cannot be undone.')">
                    <input type="hidden" name="csrf_token"     value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="delete_task_id" value="<?= $task['id'] ?>">
                    <button type="submit"
                        class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors">
                        🗑 Delete
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
