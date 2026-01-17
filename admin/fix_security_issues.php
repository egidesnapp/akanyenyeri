<?php
/**
 * Security Issues Fixer for Akanyenyeri Magazine
 * Automatically fixes common security vulnerabilities found by the audit
 */

session_start();
require_once __DIR__ . "/php/auth_check.php";
require_once __DIR__ . "/php/rate_limiter.php";

// Require admin access
requireRole('admin');

$message = '';
$fixed = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['fix_php_errors'])) {
        // Fix PHP display_errors
        if (ini_set('display_errors', '0')) {
            $fixed[] = "Disabled PHP display_errors";
        } else {
            $message = "Could not disable PHP display_errors via ini_set. Please update php.ini manually.";
        }
    }

    if (isset($_POST['fix_file_permissions'])) {
        // Fix file permissions for critical files
        $critical_files = [
            'admin/php/auth_check.php',
            'admin/php/login.php',
            'setup_database.php'
        ];

        foreach ($critical_files as $file) {
            $filepath = __DIR__ . '/../' . $file;
            if (file_exists($filepath)) {
                // Set permissions to 644 (readable by owner/group, writable by owner only)
                if (chmod($filepath, 0644)) {
                    $fixed[] = "Fixed permissions for $file";
                } else {
                    $message .= "Could not fix permissions for $file. ";
                }
            }
        }
    }

    if (isset($_POST['move_backup_files'])) {
        // Move backup files to a secure location
        $backup_dir = __DIR__ . '/../backups';
        $secure_backup_dir = __DIR__ . '/../secure_backups';

        if (!is_dir($secure_backup_dir)) {
            if (mkdir($secure_backup_dir, 0700, true)) {
                $fixed[] = "Created secure backup directory";

                // Move backup files
                $backup_files = glob($backup_dir . '/*');
                foreach ($backup_files as $file) {
                    $filename = basename($file);
                    if (rename($file, $secure_backup_dir . '/' . $filename)) {
                        $fixed[] = "Moved backup file: $filename";
                    }
                }

                // Create .htaccess to deny access to backups directory
                $htaccess_content = "Order Deny,Allow\nDeny from all\n";
                file_put_contents($backup_dir . '/.htaccess', $htaccess_content);
                $fixed[] = "Added .htaccess protection to backups directory";

            } else {
                $message .= "Could not create secure backup directory. ";
            }
        } else {
            $message .= "Secure backup directory already exists. ";
        }
    }

    if (isset($_POST['fix_htaccess'])) {
        // Add security headers via .htaccess
        $htaccess_path = __DIR__ . '/../.htaccess';
        $security_headers = "
# Security Headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection \"1; mode=block\"
    Header always set X-Content-Type-Options nosniff
    Header always set Referrer-Policy \"strict-origin-when-cross-origin\"
    Header always set Permissions-Policy \"geolocation=(), microphone=(), camera=()\"
</IfModule>

# Hide sensitive files
<FilesMatch \"\\.(env|git|sql|bak)$\">
    Order Deny,Allow
    Deny from all
</FilesMatch>
";

        if (file_exists($htaccess_path)) {
            $existing_content = file_get_contents($htaccess_path);
            if (strpos($existing_content, 'Security Headers') === false) {
                file_put_contents($htaccess_path, $existing_content . $security_headers);
                $fixed[] = "Added security headers to .htaccess";
            } else {
                $fixed[] = "Security headers already exist in .htaccess";
            }
        } else {
            file_put_contents($htaccess_path, $security_headers);
            $fixed[] = "Created .htaccess with security headers";
        }
    }
}

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Security Issues - Akanyenyeri Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin-layout.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .security-issue { margin-bottom: 1.5rem; padding: 1rem; border-radius: 8px; border-left: 4px solid; }
        .severity-HIGH { border-left-color: #dc3545; background: rgba(220, 53, 69, 0.1); }
        .severity-MEDIUM { border-left-color: #fd7e14; background: rgba(253, 126, 20, 0.1); }
        .severity-LOW { border-left-color: #ffc107; background: rgba(255, 193, 7, 0.1); }
        .severity-INFO { border-left-color: #17a2b8; background: rgba(23, 162, 184, 0.1); }

        .fix-form { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 1rem; }
        .fix-option { margin-bottom: 1rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 6px; }
        .fix-option label { font-weight: 600; margin-bottom: 0.5rem; display: block; }
        .fix-description { color: #718096; font-size: 0.9rem; }

        .success-message { background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .warning-message { background: #fff3cd; color: #856404; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Include Sidebar -->
        <?php include "includes/sidebar.php"; ?>

        <div class="main-content">
            <!-- Include Header -->
            <?php include "includes/header.php"; ?>

            <div class="content-area">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="page-title">
                        <i class="fas fa-shield-alt"></i>
                        Fix Security Issues
                    </div>
                    <a href="security_audit.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Audit
                    </a>
                </div>

                <?php if (!empty($message)): ?>
                <div class="warning-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($fixed)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <strong>Successfully Fixed:</strong>
                    <ul style="margin-top: 0.5rem;">
                        <?php foreach ($fixed as $item): ?>
                        <li><?php echo htmlspecialchars($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Current Security Issues -->
                <div class="dashboard-section">
                    <h3><i class="fas fa-exclamation-triangle"></i> Current Security Issues</h3>
                    <p style="color: #718096; margin-bottom: 1rem;">These are the critical security issues that can be automatically fixed:</p>

                    <div class="security-issue severity-HIGH">
                        <h5><i class="fas fa-lock"></i> PHP Error Display</h5>
                        <p>PHP display_errors is enabled, which can leak sensitive information in production.</p>
                        <small class="text-muted">Severity: HIGH | Category: System</small>
                    </div>

                    <div class="security-issue severity-HIGH">
                        <h5><i class="fas fa-file"></i> File Permissions</h5>
                        <p>Critical authentication files are world-writable, allowing unauthorized modifications.</p>
                        <small class="text-muted">Severity: HIGH | Category: Files</small>
                    </div>

                    <div class="security-issue severity-HIGH">
                        <h5><i class="fas fa-database"></i> Backup File Exposure</h5>
                        <p>Database backup files are accessible from the web, potentially exposing sensitive data.</p>
                        <small class="text-muted">Severity: HIGH | Category: Backups</small>
                    </div>

                    <div class="security-issue severity-MEDIUM">
                        <h5><i class="fas fa-globe"></i> Security Headers</h5>
                        <p>Missing security headers that protect against common web vulnerabilities.</p>
                        <small class="text-muted">Severity: MEDIUM | Category: Server</small>
                    </div>
                </div>

                <!-- Fix Options -->
                <div class="fix-form">
                    <h4><i class="fas fa-wrench"></i> Automated Fixes</h4>
                    <p style="color: #718096; margin-bottom: 1.5rem;">Select the security issues you'd like to fix automatically:</p>

                    <form method="POST">
                        <div class="fix-option">
                            <label>
                                <input type="checkbox" name="fix_php_errors" checked>
                                <i class="fas fa-code"></i> Disable PHP Error Display
                            </label>
                            <div class="fix-description">
                                Prevents PHP errors from being displayed to users, which could leak sensitive information.
                            </div>
                        </div>

                        <div class="fix-option">
                            <label>
                                <input type="checkbox" name="fix_file_permissions" checked>
                                <i class="fas fa-file-shield"></i> Fix Critical File Permissions
                            </label>
                            <div class="fix-description">
                                Changes permissions on authentication files to prevent unauthorized modifications.
                            </div>
                        </div>

                        <div class="fix-option">
                            <label>
                                <input type="checkbox" name="move_backup_files" checked>
                                <i class="fas fa-archive"></i> Secure Backup Files
                            </label>
                            <div class="fix-description">
                                Moves backup files to a secure location outside the web root and adds access protection.
                            </div>
                        </div>

                        <div class="fix-option">
                            <label>
                                <input type="checkbox" name="fix_htaccess" checked>
                                <i class="fas fa-shield"></i> Add Security Headers
                            </label>
                            <div class="fix-description">
                                Adds security headers to protect against XSS, clickjacking, and other attacks.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-play"></i> Apply Security Fixes
                        </button>
                    </form>
                </div>

                <!-- Manual Fixes Required -->
                <div class="dashboard-section">
                    <h3><i class="fas fa-user-cog"></i> Manual Fixes Required</h3>
                    <p style="color: #718096; margin-bottom: 1rem;">These issues require manual configuration:</p>

                    <div class="security-issue severity-HIGH">
                        <h5><i class="fas fa-lock"></i> Database Credentials</h5>
                        <p><strong>Action Required:</strong> Change database password from default 'root' with no password.</p>
                        <div style="background: #f8f9fa; padding: 0.5rem; border-radius: 4px; margin-top: 0.5rem;">
                            <code>UPDATE mysql.user SET authentication_string=PASSWORD('your_secure_password') WHERE User='root';</code>
                        </div>
                    </div>

                    <div class="security-issue severity-HIGH">
                        <h5><i class="fas fa-globe-americas"></i> Enable HTTPS</h5>
                        <p><strong>Action Required:</strong> Install SSL certificate and configure HTTPS.</p>
                        <p style="color: #718096;">Contact your hosting provider or use Let's Encrypt for free SSL certificates.</p>
                    </div>

                    <div class="security-issue severity-MEDIUM">
                        <h5><i class="fas fa-database"></i> Database SSL & Privileges</h5>
                        <p><strong>Action Required:</strong> Enable SSL for database connections and reduce user privileges.</p>
                        <p style="color: #718096;">Configure MySQL SSL and create a dedicated database user with minimal privileges.</p>
                    </div>

                    <div class="security-issue severity-MEDIUM">
                        <h5><i class="fas fa-folder-minus"></i> Remove Sensitive Files</h5>
                        <p><strong>Action Required:</strong> Remove or protect .git directory and other sensitive files.</p>
                        <div style="background: #f8f9fa; padding: 0.5rem; border-radius: 4px; margin-top: 0.5rem;">
                            <code>rm -rf .git</code> (if not needed) or add to .htaccess deny rules
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar overlay for mobile -->
    <div class="sidebar-overlay" onclick="toggleMobileSidebar()"></div>

    <script src="js/admin.js"></script>
    <script>
        // Mobile sidebar toggle
        function toggleMobileSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.sidebar-overlay');

            if (sidebar && overlay) {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('active');
            }
        }
    </script>
</body>
</html>
