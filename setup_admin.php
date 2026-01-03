<?php
/**
 * Admin User Creation Script
 * Run this once to create an admin user, then delete this file
 * 
 * Access: http://localhost/akanyenyeri/setup_admin.php
 */

session_start();
require_once 'config/database.php';

$success = false;
$message = '';
$admin_created = false;

// Check if admin user already exists
try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role='admin'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        $message = '⚠️ An admin user already exists! Check the database for login credentials.';
    }
} catch (Exception $e) {
    $message = '❌ Database error: ' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    try {
        $pdo = getDB();
        
        $username = trim($_POST['username'] ?? 'admin');
        $email = trim($_POST['email'] ?? 'admin@example.com');
        $password = trim($_POST['password'] ?? 'admin123');
        $full_name = trim($_POST['full_name'] ?? 'Administrator');
        
        // Validate inputs
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception('All fields are required');
        }
        
        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters');
        }
        
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception('Username already exists');
        }
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists');
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert admin user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, role, status, created_at)
            VALUES (?, ?, ?, ?, 'admin', 'active', NOW())
        ");
        
        $stmt->execute([$username, $email, $hashed_password, $full_name]);
        
        $success = true;
        $admin_created = true;
        $message = '✅ Admin user created successfully!';
        
    } catch (Exception $e) {
        $message = '❌ Error: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User - Akanyenyeri Magazine</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .container {
            width: 100%;
            max-width: 500px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
            padding: 2.5rem;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .header h1 {
            font-size: 1.875rem;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: #718096;
            font-size: 0.95rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #86efac;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-warning {
            background: #fefce8;
            color: #854d0e;
            border: 1px solid #fde047;
        }
        
        .alert i {
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2d3748;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-control::placeholder {
            color: #a0aec0;
        }
        
        .btn {
            width: 100%;
            padding: 0.875rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .info-section {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.25rem;
            margin-top: 2rem;
            font-size: 0.9rem;
            color: #4a5568;
            line-height: 1.6;
        }
        
        .info-section h3 {
            color: #2d3748;
            margin-bottom: 0.75rem;
            font-size: 1rem;
        }
        
        .info-section code {
            background: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #e53e3e;
        }
        
        .success-details {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .success-details h3 {
            color: #166534;
            margin-bottom: 1rem;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dcfce7;
            color: #15803d;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
        }
        
        .detail-value {
            font-family: 'Courier New', monospace;
            background: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        
        .delete-warning {
            background: #fff5f5;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1.5rem;
            color: #991b1b;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <i class="fas fa-shield-alt"></i>
                <h1>Create Admin User</h1>
                <p>Set up your first administrator account</p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $success ? 'alert-success' : (strpos($message, '⚠️') === 0 ? 'alert-warning' : 'alert-error'); ?>">
                    <i class="fas <?php echo $success ? 'fa-check-circle' : (strpos($message, '⚠️') === 0 ? 'fa-exclamation-triangle' : 'fa-times-circle'); ?>"></i>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!$admin_created && strpos($message, 'already exists') === false): ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="admin" placeholder="Enter username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="admin@example.com" placeholder="admin@example.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               value="admin123" placeholder="At least 6 characters" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="Administrator" placeholder="Administrator" required>
                    </div>
                    
                    <button type="submit" name="create_admin" class="btn btn-primary">
                        <i class="fas fa-plus" style="margin-right: 0.5rem;"></i>Create Admin User
                    </button>
                </form>
                
                <div class="info-section">
                    <h3><i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>Default Credentials</h3>
                    <p><strong>Username:</strong> admin</p>
                    <p><strong>Password:</strong> admin123</p>
                    <p style="margin-top: 0.75rem; color: #666;">⚠️ Change these after first login for security!</p>
                </div>
            <?php endif; ?>
            
            <?php if ($admin_created): ?>
                <div class="success-details">
                    <h3><i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i>Admin Account Created Successfully!</h3>
                    
                    <div class="detail-item">
                        <span class="detail-label">Username:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($_POST['username']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($_POST['email']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Password:</span>
                        <span class="detail-value">••••••••</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Role:</span>
                        <span class="detail-value">admin</span>
                    </div>
                </div>
                
                <div class="delete-warning">
                    <i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i>
                    <strong>⚠️ IMPORTANT:</strong> Delete this file (setup_admin.php) for security!
                </div>
                
                <div class="link">
                    <a href="admin/login.php" style="display: inline-block; padding: 0.75rem 1.5rem; background: #667eea; color: white; border-radius: 6px; text-decoration: none; transition: all 0.2s;">
                        <i class="fas fa-sign-in-alt" style="margin-right: 0.5rem;"></i>Go to Admin Login
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="link">
                <a href="index.php"><i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i>Back to Website</a>
            </div>
        </div>
    </div>
</body>
</html>
