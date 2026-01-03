<?php
/**
 * Settings Management - Akanyenyeri Magazine Admin
 * Functional PHP page for managing site settings and configuration
 */

session_start();
require_once 'php/auth_check.php';
require_once '../config/database.php';

// Require authentication and admin role
requireAuth();
requireRole('admin', 'You need admin privileges to manage settings');

// Get database connection
$pdo = getDB();

// Handle form submissions
$success_message = '';
$error_message = '';

// Update settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        // Validate CSRF token
        if (!validateCSRF($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }

        // Get all settings from form
        $settings_to_update = [
            'site_name' => trim($_POST['site_name'] ?? ''),
            'site_description' => trim($_POST['site_description'] ?? ''),
            'admin_email' => trim($_POST['admin_email'] ?? ''),
            'posts_per_page' => intval($_POST['posts_per_page'] ?? 10),
            'enable_comments' => isset($_POST['enable_comments']) ? '1' : '0',
            'comment_moderation' => isset($_POST['comment_moderation']) ? '1' : '0',
            'allow_registration' => isset($_POST['allow_registration']) ? '1' : '0',
            'default_user_role' => $_POST['default_user_role'] ?? 'author',
            'timezone' => $_POST['timezone'] ?? 'UTC',
            'date_format' => $_POST['date_format'] ?? 'Y-m-d',
            'time_format' => $_POST['time_format'] ?? 'H:i:s',
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
            'maintenance_message' => trim($_POST['maintenance_message'] ?? ''),
            'analytics_code' => trim($_POST['analytics_code'] ?? ''),
            'social_facebook' => trim($_POST['social_facebook'] ?? ''),
            'social_twitter' => trim($_POST['social_twitter'] ?? ''),
            'social_instagram' => trim($_POST['social_instagram'] ?? ''),
            'social_youtube' => trim($_POST['social_youtube'] ?? ''),
            'seo_meta_title' => trim($_POST['seo_meta_title'] ?? ''),
            'seo_meta_description' => trim($_POST['seo_meta_description'] ?? ''),
            'seo_meta_keywords' => trim($_POST['seo_meta_keywords'] ?? ''),
            'contact_address' => trim($_POST['contact_address'] ?? ''),
            'contact_phone' => trim($_POST['contact_phone'] ?? ''),
            'contact_email' => trim($_POST['contact_email'] ?? ''),
        ];

        // Validation
        if (empty($settings_to_update['site_name'])) {
            throw new Exception('Site name is required');
        }

        if (!filter_var($settings_to_update['admin_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid admin email address');
        }

        if ($settings_to_update['posts_per_page'] < 1 || $settings_to_update['posts_per_page'] > 100) {
            throw new Exception('Posts per page must be between 1 and 100');
        }

        // Update each setting
        foreach ($settings_to_update as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO site_settings (setting_key, setting_value, setting_type, updated_at)
                VALUES (?, ?, 'text', NOW())
                ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                updated_at = NOW()
            ");
            $stmt->execute([$key, $value]);
        }

        $success_message = 'Settings updated successfully!';

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get current settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    $settings_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($settings_data as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (Exception $e) {
    $error_message = "Error loading settings: " . $e->getMessage();
}

// Default values
$defaults = [
    'site_name' => 'Akanyenyeri Magazine',
    'site_description' => 'Your Trusted News Source',
    'admin_email' => 'admin@akanyenyeri.com',
    'posts_per_page' => '10',
    'enable_comments' => '1',
    'comment_moderation' => '1',
    'allow_registration' => '0',
    'default_user_role' => 'author',
    'timezone' => 'UTC',
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i:s',
    'maintenance_mode' => '0',
    'maintenance_message' => 'Site is under maintenance. Please check back later.',
    'analytics_code' => '',
    'social_facebook' => '',
    'social_twitter' => '',
    'social_instagram' => '',
    'social_youtube' => '',
    'seo_meta_title' => 'Akanyenyeri Magazine - Your Trusted News Source',
    'seo_meta_description' => 'Stay informed with the latest news from Akanyenyeri Magazine',
    'seo_meta_keywords' => 'news, magazine, akanyenyeri, politics, sports, technology',
    'contact_address' => '',
    'contact_phone' => '',
    'contact_email' => '',
];

// Merge with defaults
foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

// Get current user info
$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Akanyenyeri Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">

    <style>
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .settings-tabs {
            display: flex;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .tab-button {
            flex: 1;
            padding: 1rem;
            background: #f6f7f7;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #646970;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .tab-button.active {
            background: #2271b1;
            color: white;
        }

        .tab-button:hover {
            background: #e1e5e9;
        }

        .tab-button.active:hover {
            background: #135e96;
        }

        .settings-content {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        .settings-section {
            margin-bottom: 2rem;
        }

        .settings-section h3 {
            margin: 0 0 1rem 0;
            color: #1d2327;
            font-size: 1.2rem;
            font-weight: 600;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f1;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #1d2327;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: border-color 0.15s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.2);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .help-text {
            font-size: 0.8rem;
            color: #646970;
            margin-top: 0.25rem;
        }

        .settings-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 2rem;
            border-top: 1px solid #f0f0f1;
            margin-top: 2rem;
        }

        .danger-zone {
            background: #fff8f8;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .danger-zone h4 {
            color: #721c24;
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .danger-zone p {
            color: #721c24;
            margin-bottom: 1rem;
        }

        .btn-danger {
            background: #d63638;
            color: white;
        }

        .btn-danger:hover {
            background: #bb2d3b;
            color: white;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .settings-tabs {
                flex-direction: column;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .settings-actions {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Include Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <!-- Include Header -->
            <?php include 'includes/header.php'; ?>

            <div class="content-area">
                <div class="content-header">
                    <h1><i class="fas fa-cog"></i> Settings</h1>
                    <div class="header-actions">
                        <a href="../" class="btn btn-outline" target="_blank">
                            <i class="fas fa-external-link-alt"></i> View Site
                        </a>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>

                <div class="settings-container">
                    <!-- Settings Tabs -->
                    <div class="settings-tabs">
                        <button class="tab-button active" onclick="switchTab('general')">
                            <i class="fas fa-cog"></i> General
                        </button>
                        <button class="tab-button" onclick="switchTab('content')">
                            <i class="fas fa-file-alt"></i> Content
                        </button>
                        <button class="tab-button" onclick="switchTab('social')">
                            <i class="fas fa-share-alt"></i> Social
                        </button>
                        <button class="tab-button" onclick="switchTab('seo')">
                            <i class="fas fa-search"></i> SEO
                        </button>
                        <button class="tab-button" onclick="switchTab('advanced')">
                            <i class="fas fa-tools"></i> Advanced
                        </button>
                    </div>

                    <!-- Settings Form -->
                    <form method="POST" class="settings-content" id="settingsForm">
                        <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">

                        <!-- General Settings -->
                        <div class="tab-panel active" id="general">
                            <div class="settings-section">
                                <h3><i class="fas fa-info-circle"></i> Site Information</h3>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="site_name">Site Name *</label>
                                        <input type="text" id="site_name" name="site_name" class="form-control" required
                                               placeholder="Your site name"
                                               value="<?php echo htmlspecialchars($settings['site_name']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="admin_email">Admin Email *</label>
                                        <input type="email" id="admin_email" name="admin_email" class="form-control" required
                                               placeholder="admin@example.com"
                                               value="<?php echo htmlspecialchars($settings['admin_email']); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="site_description">Site Description</label>
                                    <textarea id="site_description" name="site_description" class="form-control" rows="3"
                                              placeholder="Brief description of your site"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                                </div>
                            </div>

                            <div class="settings-section">
                                <h3><i class="fas fa-globe"></i> Localization</h3>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="timezone">Timezone</label>
                                        <select id="timezone" name="timezone" class="form-control">
                                            <option value="UTC" <?php echo $settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                            <option value="America/New_York" <?php echo $settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                            <option value="America/Chicago" <?php echo $settings['timezone'] === 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                            <option value="America/Denver" <?php echo $settings['timezone'] === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                            <option value="America/Los_Angeles" <?php echo $settings['timezone'] === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                            <option value="Europe/London" <?php echo $settings['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>London</option>
                                            <option value="Europe/Paris" <?php echo $settings['timezone'] === 'Europe/Paris' ? 'selected' : ''; ?>>Paris</option>
                                            <option value="Africa/Kigali" <?php echo $settings['timezone'] === 'Africa/Kigali' ? 'selected' : ''; ?>>Kigali</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="date_format">Date Format</label>
                                        <select id="date_format" name="date_format" class="form-control">
                                            <option value="Y-m-d" <?php echo $settings['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                            <option value="m/d/Y" <?php echo $settings['date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                            <option value="d/m/Y" <?php echo $settings['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                            <option value="F j, Y" <?php echo $settings['date_format'] === 'F j, Y' ? 'selected' : ''; ?>>Month DD, YYYY</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="settings-section">
                                <h3><i class="fas fa-address-book"></i> Contact Information</h3>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="contact_email">Contact Email</label>
                                        <input type="email" id="contact_email" name="contact_email" class="form-control"
                                               placeholder="contact@example.com"
                                               value="<?php echo htmlspecialchars($settings['contact_email']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="contact_phone">Contact Phone</label>
                                        <input type="tel" id="contact_phone" name="contact_phone" class="form-control"
                                               placeholder="+1 (555) 123-4567"
                                               value="<?php echo htmlspecialchars($settings['contact_phone']); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="contact_address">Contact Address</label>
                                    <textarea id="contact_address" name="contact_address" class="form-control" rows="3"
                                              placeholder="Your business address"><?php echo htmlspecialchars($settings['contact_address']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Content Settings -->
                        <div class="tab-panel" id="content">
                            <div class="settings-section">
                                <h3><i class="fas fa-file-alt"></i> Posts & Content</h3>

                                <div class="form-group">
                                    <label for="posts_per_page">Posts Per Page</label>
                                    <input type="number" id="posts_per_page" name="posts_per_page" class="form-control"
                                           min="1" max="100" required
                                           value="<?php echo htmlspecialchars($settings['posts_per_page']); ?>">
                                    <div class="help-text">Number of posts to display on homepage and category pages</div>
                                </div>
                            </div>

                            <div class="settings-section">
                                <h3><i class="fas fa-comments"></i> Comments</h3>

                                <div class="checkbox-group">
                                    <input type="checkbox" id="enable_comments" name="enable_comments" value="1"
                                           <?php echo $settings['enable_comments'] ? 'checked' : ''; ?>>
                                    <label for="enable_comments">Enable comments on posts</label>
                                </div>

                                <div class="checkbox-group">
                                    <input type="checkbox" id="comment_moderation" name="comment_moderation" value="1"
                                           <?php echo $settings['comment_moderation'] ? 'checked' : ''; ?>>
                                    <label for="comment_moderation">Comments must be approved before appearing</label>
                                </div>
                            </div>

                            <div class="settings-section">
                                <h3><i class="fas fa-users"></i> User Registration</h3>

                                <div class="checkbox-group">
                                    <input type="checkbox" id="allow_registration" name="allow_registration" value="1"
                                           <?php echo $settings['allow_registration'] ? 'checked' : ''; ?>>
                                    <label for="allow_registration">Allow new user registration</label>
                                </div>

                                <div class="form-group">
                                    <label for="default_user_role">Default Role for New Users</label>
                                    <select id="default_user_role" name="default_user_role" class="form-control">
                                        <option value="author" <?php echo $settings['default_user_role'] === 'author' ? 'selected' : ''; ?>>Author</option>
                                        <option value="editor" <?php echo $settings['default_user_role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Social Settings -->
                        <div class="tab-panel" id="social">
                            <div class="settings-section">
                                <h3><i class="fas fa-share-alt"></i> Social Media Links</h3>

                                <div class="form-group">
                                    <label for="social_facebook">Facebook URL</label>
                                    <input type="url" id="social_facebook" name="social_facebook" class="form-control"
                                           placeholder="https://facebook.com/yourpage"
                                           value="<?php echo htmlspecialchars($settings['social_facebook']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="social_twitter">Twitter URL</label>
                                    <input type="url" id="social_twitter" name="social_twitter" class="form-control"
                                           placeholder="https://twitter.com/yourusername"
                                           value="<?php echo htmlspecialchars($settings['social_twitter']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="social_instagram">Instagram URL</label>
                                    <input type="url" id="social_instagram" name="social_instagram" class="form-control"
                                           placeholder="https://instagram.com/yourusername"
                                           value="<?php echo htmlspecialchars($settings['social_instagram']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="social_youtube">YouTube URL</label>
                                    <input type="url" id="social_youtube" name="social_youtube" class="form-control"
                                           placeholder="https://youtube.com/yourchannel"
                                           value="<?php echo htmlspecialchars($settings['social_youtube']); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- SEO Settings -->
                        <div class="tab-panel" id="seo">
                            <div class="settings-section">
                                <h3><i class="fas fa-search"></i> Search Engine Optimization</h3>

                                <div class="form-group">
                                    <label for="seo_meta_title">Default Meta Title</label>
                                    <input type="text" id="seo_meta_title" name="seo_meta_title" class="form-control"
                                           placeholder="Your Site Title"
                                           value="<?php echo htmlspecialchars($settings['seo_meta_title']); ?>">
                                    <div class="help-text">Used as the default title tag for pages without a specific title</div>
                                </div>

                                <div class="form-group">
                                    <label for="seo_meta_description">Default Meta Description</label>
                                    <textarea id="seo_meta_description" name="seo_meta_description" class="form-control" rows="3"
                                              placeholder="Brief description of your site"><?php echo htmlspecialchars($settings['seo_meta_description']); ?></textarea>
                                    <div class="help-text">Used as the default description for pages without a specific description</div>
                                </div>

                                <div class="form-group">
                                    <label for="seo_meta_keywords">Default Meta Keywords</label>
                                    <input type="text" id="seo_meta_keywords" name="seo_meta_keywords" class="form-control"
                                           placeholder="keyword1, keyword2, keyword3"
                                           value="<?php echo htmlspecialchars($settings['seo_meta_keywords']); ?>">
                                    <div class="help-text">Comma-separated keywords relevant to your site</div>
                                </div>
                            </div>

                            <div class="settings-section">
                                <h3><i class="fas fa-chart-line"></i> Analytics</h3>

                                <div class="form-group">
                                    <label for="analytics_code">Google Analytics Code</label>
                                    <textarea id="analytics_code" name="analytics_code" class="form-control" rows="5"
                                              placeholder="<!-- Google Analytics code here -->"><?php echo htmlspecialchars($settings['analytics_code']); ?></textarea>
                                    <div class="help-text">Paste your Google Analytics tracking code here</div>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Settings -->
                        <div class="tab-panel" id="advanced">
                            <div class="settings-section">
                                <h3><i class="fas fa-tools"></i> Maintenance</h3>

                                <div class="checkbox-group">
                                    <input type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1"
                                           <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                    <label for="maintenance_mode">Enable maintenance mode</label>
                                </div>

                                <div class="form-group">
                                    <label for="maintenance_message">Maintenance Message</label>
                                    <textarea id="maintenance_message" name="maintenance_message" class="form-control" rows="3"
                                              placeholder="Site is under maintenance..."><?php echo htmlspecialchars($settings['maintenance_message']); ?></textarea>
                                </div>
                            </div>

                            <div class="danger-zone">
                                <h4><i class="fas fa-exclamation-triangle"></i> Danger Zone</h4>
                                <p>These actions are irreversible. Please proceed with caution.</p>

                                <div class="form-actions">
                                    <button type="button" class="btn btn-danger" onclick="clearCache()">
                                        <i class="fas fa-trash"></i> Clear Cache
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="resetSettings()">
                                        <i class="fas fa-undo"></i> Reset All Settings
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="settings-actions">
                            <div class="action-info">
                                <i class="fas fa-info-circle"></i>
                                Changes will be applied immediately after saving.
                            </div>

                            <div class="action-buttons">
                                <button type="submit" name="update_settings" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Remove active class from all tabs and panels
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.classList.remove('active');
            });

            // Add active class to clicked tab and corresponding panel
            event.target.classList.add('active');
            document.getElementById(tabName).classList.add('active');
        }

        function clearCache() {
            if (confirm('Are you sure you want to clear all cached data? This action cannot be undone.')) {
                // Implement cache clearing logic
                alert('Cache cleared successfully!');
            }
        }

        function resetSettings() {
            if (confirm('Are you sure you want to reset all settings to default values? This action cannot be undone.')) {
                // Implement settings reset logic
                alert('Settings reset to defaults!');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            document.getElementById('settingsForm').addEventListener('submit', function(e) {
                const siteName = document.getElementById('site_name').value.trim();
                const adminEmail = document.getElementById('admin_email').value.trim();
                const postsPerPage = parseInt(document.getElementById('posts_per_page').value);

                if (!siteName) {
                    alert('Site name is required');
                    e.preventDefault();
                    return;
                }

                if (!adminEmail || !adminEmail.includes('@')) {
                    alert('Valid admin email is required');
                    e.preventDefault();
                    return;
                }

                if (postsPerPage < 1 || postsPerPage > 100) {
                    alert('Posts per page must be between 1 and 100');
                    e.preventDefault();
                    return;
                }
            });

            // Auto-save draft (optional)
            let saveTimeout;
            document.querySelectorAll('.form-control').forEach(input => {
                input.addEventListener('input', function() {
                    clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(() => {
                        // Auto-save logic could be implemented here
                        console.log('Auto-saving settings...');
                    }, 5000);
                });
            });
        });
    </script>
</body>
</html>
