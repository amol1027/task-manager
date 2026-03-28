<?php

define('BASE_URL', '/task%20manager/');
$pageTitle = 'Add Task';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$errors = [];
$old    = ['title' => '', 'description' => '', 'priority' => 'medium', 'status' => 'pending', 'due_date' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token. Please try again.';
    } else {
        $title       = trim($_POST['title']       ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority    = $_POST['priority']         ?? 'medium';
        $status      = $_POST['status']           ?? 'pending';
        $due_date    = trim($_POST['due_date']     ?? '');

        $old = compact('title', 'description', 'priority', 'status', 'due_date');

        $validPriorities = ['low', 'medium', 'high'];
        $validStatuses   = ['pending', 'in_progress', 'completed'];

        if ($title === '')                               $errors[] = 'Task title is required.';
        if (!in_array($priority, $validPriorities, true)) $errors[] = 'Invalid priority value.';
        if (!in_array($status, $validStatuses, true))     $errors[] = 'Invalid status value.';
        if ($due_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) $errors[] = 'Invalid due date format.';

        if (empty($errors)) {
            $db   = getDB();
            $stmt = $db->prepare(
                'INSERT INTO tasks (user_id, title, description, priority, status, due_date)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                (int) $_SESSION['user_id'],
                $title,
                $description ?: null,
                $priority,
                $status,
                $due_date ?: null,
            ]);
            $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Task "' . htmlspecialchars($title) . '" added successfully!'];
            header('Location: ' . BASE_URL . 'pages/dashboard.php');
            exit;
        }
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
    <!-- Back link -->
    <a href="<?= BASE_URL ?>pages/dashboard.php" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-indigo-600 mb-6 transition-colors">
        ← Back to Dashboard
    </a>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
        <h1 class="text-xl font-bold text-gray-900 mb-1">Add New Task</h1>
        <p class="text-sm text-gray-500 mb-6">Fill in the details below to create a new task.</p>

        <!-- Errors -->
        <?php if (!empty($errors)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700 space-y-1">
            <?php foreach ($errors as $e): ?>
            <p>✕ <?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                    Title <span class="text-red-500">*</span>
                </label>
                <input id="title" type="text" name="title"
                    value="<?= htmlspecialchars($old['title']) ?>"
                    placeholder="e.g. Finish project report"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                           placeholder-gray-400 transition">
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="description" name="description" rows="3"
                    placeholder="Optional notes or details..."
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                           placeholder-gray-400 transition resize-none"><?= htmlspecialchars($old['description']) ?></textarea>
            </div>

            <!-- Priority + Status (side by side) -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select id="priority" name="priority"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition bg-white">
                        <?php foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= $old['priority'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status" name="status"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition bg-white">
                        <?php foreach (['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed'] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= $old['status'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Due Date -->
            <div>
                <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                <input id="due_date" type="date" name="due_date"
                    value="<?= htmlspecialchars($old['due_date']) ?>"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">
                    Add Task
                </button>
                <a href="<?= BASE_URL ?>pages/dashboard.php"
                    class="flex-1 text-center border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium py-2.5 rounded-lg transition-colors text-sm">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
