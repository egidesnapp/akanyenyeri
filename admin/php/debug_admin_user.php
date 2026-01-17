<?php
// Debug helper — run locally only and delete after use.
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../database/config/database.php';

$pdo = null;
try {
    $pdo = getDB();
} catch (Exception $e) {
    echo "DB CONNECT ERROR: " . htmlspecialchars($e->getMessage());
    exit;
}

// Adjust these if you used different values when creating the admin
$checkUsername = 'admin';
$checkEmail = 'admin@example.com';
$testPassword = 'admin123';

$stmt = $pdo->prepare("SELECT id, username, email, password, full_name, role, status, created_at FROM users WHERE username = ? OR email = ? LIMIT 1");
$stmt->execute([$checkUsername, $checkEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: text/plain; charset=utf-8');
echo "--- ADMIN USER DEBUG OUTPUT ---\n";

if (!$user) {
    echo "RESULT: No user found with username '{$checkUsername}' or email '{$checkEmail}'.\n";
    echo "Next steps:\n - Run setup_admin.php to create an admin\n - Or check phpMyAdmin for users table\n";
    exit;
}

echo "User found:\n";
echo "id: " . $user['id'] . "\n";
echo "username: " . $user['username'] . "\n";
echo "email: " . $user['email'] . "\n";
echo "full_name: " . $user['full_name'] . "\n";
echo "role: " . $user['role'] . "\n";
echo "status: " . $user['status'] . "\n";
echo "created_at: " . $user['created_at'] . "\n";

echo "\nPassword hash: (hidden) - length " . strlen($user['password']) . " chars\n";
$verify = password_verify($testPassword, $user['password']) ? 'MATCH' : 'NO_MATCH';

echo "password_verify('{$testPassword}') => {$verify}\n";

// Login code requirements (mirrors admin/php/login.php expectations)
$roleAllowed = in_array($user['role'], ['admin','editor']);
$statusOk = ($user['status'] === 'active');

echo "role allowed (admin/editor): " . ($roleAllowed ? 'YES' : 'NO') . "\n";
echo "status is 'active': " . ($statusOk ? 'YES' : 'NO') . "\n";

if ($verify === 'MATCH' && $roleAllowed && $statusOk) {
    echo "\nDiagnosis: Credentials should work. If login still fails, check sessions/cookies or login handler path.\n";
} else {
    echo "\nDiagnosis: One or more conditions prevent login:\n";
    if ($verify !== 'MATCH') echo " - Password does not match the test password ('{$testPassword}').\n";
    if (!$roleAllowed) echo " - Role is not permitted to log in (needs 'admin' or 'editor').\n";
    if (!$statusOk) echo " - User status is not 'active'.\n";
    echo "Next steps:\n - If password mismatch: recreate admin or reset password via phpMyAdmin using password_hash().\n - If role/status wrong: update `role='admin'` and `status='active'` in users table.\n";
}

echo "\nSECURITY: Delete this file after use: admin/php/debug_admin_user.php\n";
