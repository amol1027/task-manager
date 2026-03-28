<?php
/**
 * admin/index.php  — Admin Dashboard
 */
define('BASE_URL', '/task%20manager/');
define('ADMIN_AREA', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

// Site-wide stats
$stats = $db->query("
    SELECT
        (SELECT COUNT(*) FROM users)                                   AS total_users,
        (SELECT COUNT(*) FROM users WHERE role='admin')                AS total_admins,
        (SELECT COUNT(*) FROM tasks)                                   AS total_tasks,
        (SELECT COUNT(*) FROM tasks WHERE status='pending')           AS pending_tasks,
        (SELECT COUNT(*) FROM tasks WHERE status='in_progress')       AS inprogress_tasks,
        (SELECT COUNT(*) FROM tasks WHERE status='completed')         AS completed_tasks,
        (SELECT COUNT(*) FROM tasks WHERE due_date < CURDATE()
            AND status != 'completed')                                 AS overdue_tasks
")->fetch();

// Recent registrations
$recentUsers = $db->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Most active users (by task count)
$topUsers = $db->query("
    SELECT u.id, u.name, u.email, COUNT(t.id) AS task_count
    FROM users u
    LEFT JOIN tasks t ON t.user_id = u.id
    GROUP BY u.id
    ORDER BY task_count DESC
    LIMIT 5
")->fetchAll();

$pageTitle = 'Admin Dashboard';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    <!-- Page heading -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
        <p class="text-sm text-gray-500 mt-1">Site-wide overview and quick actions.</p>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-10">
        <?php
        $cards = [
            ['icon'=>'👥','label'=>'Total Users',   'value'=>$stats['total_users'],      'color'=>'violet'],
            ['icon'=>'📋','label'=>'Total Tasks',   'value'=>$stats['total_tasks'],      'color'=>'indigo'],
            ['icon'=>'⏳','label'=>'In Progress',   'value'=>$stats['inprogress_tasks'], 'color'=>'blue'],
            ['icon'=>'✅','label'=>'Completed',     'value'=>$stats['completed_tasks'],  'color'=>'green'],
            ['icon'=>'🔴','label'=>'Overdue',       'value'=>$stats['overdue_tasks'],    'color'=>'red'],
            ['icon'=>'⏱','label'=>'Pending',        'value'=>$stats['pending_tasks'],    'color'=>'yellow'],
            ['icon'=>'🛡','label'=>'Admins',         'value'=>$stats['total_admins'],     'color'=>'purple'],
            ['icon'=>'👤','label'=>'Regular Users', 'value'=>$stats['total_users']-$stats['total_admins'], 'color'=>'gray'],
        ];
        foreach ($cards as $c):
        ?>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
            <span class="text-3xl"><?= $c['icon'] ?></span>
            <div>
                <p class="text-2xl font-bold text-<?= $c['color'] ?>-600"><?= (int)$c['value'] ?></p>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide"><?= $c['label'] ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="grid lg:grid-cols-2 gap-8">

        <!-- Recent Registrations -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Recent Registrations</h2>
                <a href="<?= BASE_URL ?>admin/users.php" class="text-xs text-indigo-600 hover:underline font-medium">View all →</a>
            </div>
            <div class="divide-y divide-gray-50">
                <?php foreach ($recentUsers as $u): ?>
                <div class="flex items-center gap-3 px-6 py-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm">
                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($u['name']) ?></p>
                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($u['email']) ?></p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full <?= $u['role']==='admin' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600' ?>">
                        <?= ucfirst($u['role']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Top Users by Task Count -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Most Active Users</h2>
                <a href="<?= BASE_URL ?>admin/tasks.php" class="text-xs text-indigo-600 hover:underline font-medium">View tasks →</a>
            </div>
            <div class="divide-y divide-gray-50">
                <?php foreach ($topUsers as $idx => $u): ?>
                <div class="flex items-center gap-3 px-6 py-3">
                    <span class="w-6 text-center text-sm font-bold text-gray-400">#<?= $idx+1 ?></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($u['name']) ?></p>
                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($u['email']) ?></p>
                    </div>
                    <span class="text-sm font-bold text-indigo-600"><?= (int)$u['task_count'] ?> tasks</span>
                    <a href="<?= BASE_URL ?>admin/login_as.php?user_id=<?= $u['id'] ?>"
                       onclick="return confirm('Login as <?= htmlspecialchars(addslashes($u['name'])) ?>?')"
                       class="text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-2 py-1 rounded-lg transition-colors font-medium">
                        Login As
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
