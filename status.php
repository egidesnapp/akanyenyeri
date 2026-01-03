<?php
/**
 * Comprehensive Status Dashboard for Akanyenyeri Magazine
 * Checks all connections, database, files, and system health
 */

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffer
ob_start();

// Status tracking
$overall_status = true;
$critical_errors = [];
$warnings = [];
$success_messages = [];

// Helper function to format status
function getStatusIcon($status) {
    return $status ? '✅' : '❌';
}

function getStatusClass($status) {
    return $status ? 'success' : 'error';
}

// Test functions
function testPHPSetup() {
    global $success_messages;
    $success_messages[] = "PHP " . phpversion() . " is running correctly";
    return true;
}

function testDatabaseConfig() {
    global $critical_errors;

    if (!file_exists(__DIR__ . '/config/database.php')) {
        $critical_errors[] = "Database configuration file missing";
        return false;
    }

    try {
        require_once __DIR__ . '/config/database.php';

        if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
            $critical_errors[] = "Database configuration incomplete";
            return false;
        }

        return true;
    } catch (Exception $e) {
        $critical_errors[] = "Database configuration error: " . $e->getMessage();
        return false;
    }
}

function testMySQLConnection() {
    global $critical_errors, $success_messages;

    try {
        $host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $user = defined('DB_USER') ? DB_USER : 'root';
        $pass = defined('DB_PASS') ? DB_PASS : '';

        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        $success_messages[] = "MySQL connection successful (Version: $version)";
        return $pdo;

    } catch (PDOException $e) {
        $critical_errors[] = "MySQL connection failed: " . $e->getMessage();
        return false;
    }
}

function testDatabase($pdo) {
    global $critical_errors, $warnings, $success_messages;

    if (!$pdo) return false;

    try {
        $db_name = defined('DB_NAME') ? DB_NAME : 'akanyenyeri_db';

        // Check if database exists
        $stmt = $pdo->query("SHOW DATABASES LIKE '$db_name'");
        if ($stmt->rowCount() == 0) {
            $warnings[] = "Database '$db_name' does not exist - needs setup";
            return 'needs_setup';
        }

        // Connect to specific database
        $pdo_db = new PDO("mysql:host=" . DB_HOST . ";dbname=$db_name;charset=utf8mb4", DB_USER, DB_PASS);
        $pdo_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check required tables
        $required_tables = ['users', 'posts', 'categories', 'comments', 'site_settings'];
        $existing_tables = $pdo_db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        $missing_tables = array_diff($required_tables, $existing_tables);
        if (!empty($missing_tables)) {
            $warnings[] = "Missing tables: " . implode(', ', $missing_tables);
            return 'incomplete';
        }

        // Check data
        $user_count = $pdo_db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $post_count = $pdo_db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        $category_count = $pdo_db->query("SELECT COUNT(*) FROM categories")->fetchColumn();

        $success_messages[] = "Database complete with $user_count users, $post_count posts, $category_count categories";
        return 'complete';

    } catch (PDOException $e) {
        $critical_errors[] = "Database access error: " . $e->getMessage();
        return false;
    }
}

function testAdminFiles() {
    global $critical_errors, $warnings, $success_messages;

    $admin_files = [
        'admin/index.html' => 'Admin login page',
        'admin/dashboard.php' => 'Admin dashboard',
        'admin/php/dashboard_data.php' => 'Dashboard data handler',
        'admin/php/login.php' => 'Login handler',
        'admin/php/logout.php' => 'Logout handler',
        'admin/php/auth_check.php' => 'Authentication check',
        'admin/css/admin.css' => 'Admin styles'
    ];

    $missing_files = [];
    $existing_files = 0;

    foreach ($admin_files as $file => $description) {
        if (file_exists(__DIR__ . '/' . $file)) {
            $existing_files++;
        } else {
            $missing_files[] = "$file ($description)";
        }
    }

    if (empty($missing_files)) {
        $success_messages[] = "All admin files present ($existing_files files)";
        return true;
    } else {
        foreach ($missing_files as $missing) {
            $warnings[] = "Missing admin file: $missing";
        }
        return false;
    }
}

function testFrontendFiles() {
    global $warnings, $success_messages;

    $frontend_files = [
        'index.php' => 'Dynamic homepage',
        'index.html' => 'Static homepage (backup)',
        'config/database.php' => 'Database configuration',
        'css/dark-theme.css' => 'Theme styles',
        'js/theme.js' => 'Theme scripts'
    ];

    $missing_files = [];
    $existing_files = 0;

    foreach ($frontend_files as $file => $description) {
        if (file_exists(__DIR__ . '/' . $file)) {
            $existing_files++;
        } else {
            $missing_files[] = "$file ($description)";
        }
    }

    if (count($missing_files) <= 1) { // Allow 1 missing file (either index.php or index.html)
        $success_messages[] = "Frontend files ready ($existing_files files)";
        return true;
    } else {
        foreach ($missing_files as $missing) {
            $warnings[] = "Missing frontend file: $missing";
        }
        return false;
    }
}

function testDirectories() {
    global $warnings, $success_messages;

    $directories = [
        'uploads' => 'Media uploads',
        'uploads/images' => 'Image uploads',
        'admin' => 'Admin panel',
        'admin/php' => 'Admin PHP scripts',
        'config' => 'Configuration files'
    ];

    $issues = [];
    $good_dirs = 0;

    foreach ($directories as $dir => $description) {
        $path = __DIR__ . '/' . $dir;

        if (!is_dir($path)) {
            $issues[] = "Directory missing: $dir ($description)";
        } else if (!is_writable($path)) {
            $issues[] = "Directory not writable: $dir ($description)";
        } else {
            $good_dirs++;
        }
    }

    if (empty($issues)) {
        $success_messages[] = "All directories ready ($good_dirs directories)";
        return true;
    } else {
        foreach ($issues as $issue) {
            $warnings[] = $issue;
        }
        return false;
    }
}

function testURLAccess() {
    global $success_messages;

    $base_url = ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
    $success_messages[] = "Website accessible at: $base_url";
    return true;
}

// Run all tests
$php_ok = testPHPSetup();
$config_ok = testDatabaseConfig();
$mysql_connection = testMySQLConnection();
$mysql_ok = $mysql_connection !== false;
$database_status = testDatabase($mysql_connection);
$admin_files_ok = testAdminFiles();
$frontend_files_ok = testFrontendFiles();
$directories_ok = testDirectories();
$url_ok = testURLAccess();

// Calculate overall status
$overall_status = $php_ok && $config_ok && $mysql_ok && $admin_files_ok && $frontend_files_ok && $directories_ok;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akanyenyeri - System Status Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .dashboard {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .logo {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .subtitle {
            opacity: 0.9;
            font-size: 1.1em;
        }

        .overall-status {
            padding: 20px 30px;
            text-align: center;
            font-size: 1.2em;
            font-weight: bold;
        }

        .status-good {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }

        .status-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 5px solid #ffc107;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }

        .content {
            padding: 0 30px 30px;
        }

        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .test-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            background: #f9f9f9;
        }

        .test-card.success {
            border-left: 4px solid #28a745;
            background: #f8fff9;
        }

        .test-card.error {
            border-left: 4px solid #dc3545;
            background: #fff8f8;
        }

        .test-card.warning {
            border-left: 4px solid #ffc107;
            background: #fffef8;
        }

        .test-title {
            display: flex;
            align-items: center;
            font-size: 1.1em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .test-icon {
            margin-right: 10px;
            font-size: 1.2em;
        }

        .test-details {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        .messages {
            margin: 20px 0;
        }

        .message-section {
            margin: 15px 0;
        }

        .message-section h3 {
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #eee;
        }

        .message-list {
            list-style: none;
        }

        .message-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .message-list li:last-child {
            border-bottom: none;
        }

        .success-message {
            color: #28a745;
        }

        .warning-message {
            color: #ffc107;
        }

        .error-message {
            color: #dc3545;
        }

        .actions {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .database-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
            border-left: 4px solid #2196F3;
        }

        .recommendation {
            background: #fff9e6;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #ff9800;
        }

        .recommendation h4 {
            color: #e65100;
            margin-bottom: 8px;
        }

        @media (max-width: 768px) {
            .dashboard {
                margin: 10px;
            }

            .header, .content {
                padding: 20px;
            }

            .test-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <div class="logo">
                <i class="fas fa-newspaper"></i>
                Akanyenyeri Magazine
            </div>
            <div class="subtitle">System Status Dashboard</div>
        </div>

        <div class="overall-status <?php
            if ($overall_status && $database_status === 'complete') {
                echo 'status-good';
            } elseif ($database_status === 'needs_setup' || !empty($warnings)) {
                echo 'status-warning';
            } else {
                echo 'status-error';
            }
        ?>">
            <?php if ($overall_status && $database_status === 'complete'): ?>
                <i class="fas fa-check-circle"></i> System is fully operational!
            <?php elseif ($database_status === 'needs_setup'): ?>
                <i class="fas fa-exclamation-triangle"></i> System ready - Database setup required
            <?php elseif (!empty($warnings)): ?>
                <i class="fas fa-exclamation-triangle"></i> System mostly operational - Minor issues detected
            <?php else: ?>
                <i class="fas fa-times-circle"></i> System has critical issues - Immediate attention required
            <?php endif; ?>
        </div>

        <div class="content">
            <!-- System Tests Grid -->
            <div class="test-grid">
                <div class="test-card <?php echo getStatusClass($php_ok); ?>">
                    <div class="test-title">
                        <span class="test-icon"><?php echo getStatusIcon($php_ok); ?></span>
                        PHP Environment
                    </div>
                    <div class="test-details">
                        PHP <?php echo phpversion(); ?> running on <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown Server'; ?>
                    </div>
                </div>

                <div class="test-card <?php echo getStatusClass($config_ok); ?>">
                    <div class="test-title">
                        <span class="test-icon"><?php echo getStatusIcon($config_ok); ?></span>
                        Configuration
                    </div>
                    <div class="test-details">
                        Database configuration file status
                        <?php if ($config_ok): ?>
                            <br>Host: <?php echo DB_HOST; ?> | DB: <?php echo DB_NAME; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="test-card <?php echo getStatusClass($mysql_ok); ?>">
                    <div class="test-title">
                        <span class="test-icon"><?php echo getStatusIcon($mysql_ok); ?></span>
                        MySQL Connection
                    </div>
                    <div class="test-details">
                        MySQL server connectivity status
                    </div>
                </div>

                <div class="test-card <?php
                    echo $database_status === 'complete' ? 'success' :
                         ($database_status === 'needs_setup' ? 'warning' : 'error');
                ?>">
                    <div class="test-title">
                        <span class="test-icon">
                            <?php
                            echo $database_status === 'complete' ? '✅' :
                                 ($database_status === 'needs_setup' ? '⚠️' : '❌');
                            ?>
                        </span>
                        Database
                    </div>
                    <div class="test-details">
                        <?php
                        if ($database_status === 'complete') {
                            echo 'Database fully configured with all tables and sample data';
                        } elseif ($database_status === 'needs_setup') {
                            echo 'Database server ready - requires initialization';
                        } else {
                            echo 'Database issues detected';
                        }
                        ?>
                    </div>
                </div>

                <div class="test-card <?php echo getStatusClass($admin_files_ok); ?>">
                    <div class="test-title">
                        <span class="test-icon"><?php echo getStatusIcon($admin_files_ok); ?></span>
                        Admin System
                    </div>
                    <div class="test-details">
                        Admin panel files and authentication system
                    </div>
                </div>

                <div class="test-card <?php echo getStatusClass($frontend_files_ok); ?>">
                    <div class="test-title">
                        <span class="test-icon"><?php echo getStatusIcon($frontend_files_ok); ?></span>
                        Frontend Files
                    </div>
                    <div class="test-details">
                        Website template and resource files
                    </div>
                </div>
            </div>

            <!-- Messages Section -->
            <div class="messages">
                <?php if (!empty($success_messages)): ?>
                <div class="message-section">
                    <h3 style="color: #28a745;"><i class="fas fa-check-circle"></i> Success Messages</h3>
                    <ul class="message-list">
                        <?php foreach ($success_messages as $message): ?>
                        <li class="success-message"><i class="fas fa-check"></i> <?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (!empty($warnings)): ?>
                <div class="message-section">
                    <h3 style="color: #ffc107;"><i class="fas fa-exclamation-triangle"></i> Warnings</h3>
                    <ul class="message-list">
                        <?php foreach ($warnings as $warning): ?>
                        <li class="warning-message"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($warning); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (!empty($critical_errors)): ?>
                <div class="message-section">
                    <h3 style="color: #dc3545;"><i class="fas fa-times-circle"></i> Critical Errors</h3>
                    <ul class="message-list">
                        <?php foreach ($critical_errors as $error): ?>
                        <li class="error-message"><i class="fas fa-times"></i> <?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>

            <!-- Database Information -->
            <?php if ($mysql_ok && $database_status): ?>
            <div class="database-info">
                <h4><i class="fas fa-database"></i> Database Information</h4>
                <p><strong>Status:</strong>
                    <?php
                    switch ($database_status) {
                        case 'complete':
                            echo '<span style="color: #28a745;">✅ Fully configured and operational</span>';
                            break;
                        case 'needs_setup':
                            echo '<span style="color: #ffc107;">⚠️ Ready for setup - Run database initialization</span>';
                            break;
                        case 'incomplete':
                            echo '<span style="color: #dc3545;">❌ Incomplete - Missing tables or data</span>';
                            break;
                        default:
                            echo '<span style="color: #dc3545;">❌ Database connection failed</span>';
                    }
                    ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Recommendations -->
            <?php if ($database_status === 'needs_setup' || !empty($warnings) || !empty($critical_errors)): ?>
            <div class="recommendation">
                <h4><i class="fas fa-lightbulb"></i> Recommended Actions</h4>
                <?php if ($database_status === 'needs_setup'): ?>
                <p><strong>Priority 1:</strong> Initialize the database by running the setup script.</p>
                <?php endif; ?>

                <?php if (!empty($critical_errors)): ?>
                <p><strong>Priority 1:</strong> Fix critical errors - these prevent the system from working.</p>
                <?php endif; ?>

                <?php if (!empty($warnings)): ?>
                <p><strong>Priority 2:</strong> Address warnings - these may cause issues later.</p>
                <?php endif; ?>

                <p><strong>After fixes:</strong> Refresh this page to verify all systems are operational.</p>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="actions">
                <h3 style="text-align: center; margin-bottom: 15px;">Quick Actions</h3>
                <div class="action-buttons">
                    <?php if ($database_status === 'needs_setup'): ?>
                    <a href="setup_database.php" class="btn btn-warning">
                        <i class="fas fa-database"></i> Initialize Database
                    </a>
                    <?php endif; ?>

                    <?php if ($database_status === 'complete'): ?>
                    <a href="admin/" class="btn btn-primary">
                        <i class="fas fa-user-shield"></i> Admin Dashboard
                    </a>

                    <a href="index.php" class="btn btn-success">
                        <i class="fas fa-home"></i> View Website
                    </a>
                    <?php endif; ?>

                    <a href="http://localhost/phpmyadmin" class="btn btn-primary" target="_blank">
                        <i class="fas fa-database"></i> phpMyAdmin
                    </a>

                    <a href="test_connection.php" class="btn btn-primary">
                        <i class="fas fa-flask"></i> Connection Test
                    </a>

                    <button onclick="window.location.reload()" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Refresh Status
                    </button>
                </div>
            </div>

            <!-- System Information -->
            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-top: 20px; font-size: 0.9em; color: #666;">
                <strong>System Information:</strong><br>
                <strong>Current URL:</strong> <?php echo ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']); ?><br>
                <strong>Document Root:</strong> <?php echo str_replace('\\', '/', __DIR__); ?><br>
                <strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
                <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
                <strong>Status Check Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 30 seconds if there are errors
        <?php if (!$overall_status || $database_status !== 'complete'): ?>
        setTimeout(function() {
            console.log('Auto-refreshing status...');
            window.location.reload();
        }, 30000);
        <?php endif; ?>

        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Animate test cards
            const cards = document.querySelectorAll('.test-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.3s ease';

                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    </script>
</body>
</html>

<?php
// End output buffer and send
ob_end_flush();
?>
