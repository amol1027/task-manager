<?php
// Run this script once to add the role column and admin user to your existing database
require_once __DIR__ . '/config/db.php';

$db = getDB();

// 1. Add role column if not exists
try {
    $db->exec("ALTER TABLE users ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user'");
    echo "✅ Added 'role' column to users table.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "ℹ️  'role' column already exists. Skipping.\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

// 2. Create admin user
$adminEmail    = 'admin@example.com';
$adminPassword = 'Admin@123';
$adminName     = 'Super Admin';

$check = $db->prepare('SELECT id FROM users WHERE email = ?');
$check->execute([$adminEmail]);

if ($check->fetch()) {
    // Already exists — ensure role is admin
    $db->prepare("UPDATE users SET role = 'admin' WHERE email = ?")->execute([$adminEmail]);
    echo "ℹ️  Admin user already exists. Ensured role is 'admin'.\n";
} else {
    $hash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')")
       ->execute([$adminName, $adminEmail, $hash]);
    echo "✅ Admin user created: {$adminEmail} / {$adminPassword}\n";
}

echo "\n🎉 Migration complete!\n";
echo "Admin login: admin@example.com  |  Password: Admin@123\n";
