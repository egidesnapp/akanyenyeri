<?php
// One-click admin creation script for local use only.
// Run: http://localhost/akanyenyeri/admin/php/create_admin.php
// Delete this file immediately after use.

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDB();
} catch (Exception $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "DB CONNECT ERROR: " . $e->getMessage();
    exit;
}

$username = 'admin';
$email = 'admin@example.com';
$password_plain = 'admin123';
$full_name = 'Administrator';
$role = 'admin';
$status = 'active';

header('Content-Type: text/plain; charset=utf-8');

// Check existing
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
$stmt->execute([$username, $email]);
$exists = $stmt->fetch(PDO::FETCH_ASSOC);

if ($exists) {
    echo "A user with username '{$username}' or email '{$email}' already exists (id: " . $exists['id'] . ").\n";
    echo "If login still fails, run debug_admin_user.php for details or update the existing row via phpMyAdmin.\n";
    echo "Remove this file when done.\n";
    exit;
}

// Create user
$hash = password_hash($password_plain, PASSWORD_BCRYPT);

$insert = $pdo->prepare('INSERT INTO users (username, email, password, full_name, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
$ok = $insert->execute([$username, $email, $hash, $full_name, $role, $status]);

if ($ok) {
    echo "Admin user created successfully.\n";
    echo "Username: {$username}\n";
    echo "Email: {$email}\n";
    echo "Password: {$password_plain}\n";
    echo "Role: {$role}\n";
    echo "Status: {$status}\n";
    echo "\nIMPORTANT: Delete this file now: admin/php/create_admin.php\n";
    echo "Then login at: http://localhost/akanyenyeri/admin/login.php\n";
} else {
    echo "Failed to create admin user.\n";
    $err = $pdo->errorInfo();
    echo "SQLSTATE: " . ($err[0] ?? '') . " - " . ($err[2] ?? '') . "\n";
}
