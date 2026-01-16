<?php
/**
 * Admin Header Component
 * Reusable header for all admin pages with user info, notifications, and quick actions
 */

// Get current user info
$current_user = getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Get notifications count (if database is available)
$notifications = [];
$pending_comments = 0;
$draft_posts = 0;

if (isset($pdo)) {
    try {
        // Get pending comments count
        $stmt = $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'");
        $pending_comments = $stmt->fetchColumn();

        // Get draft posts count
        $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'");
        $draft_posts = $stmt->fetchColumn();

        // Get recent notifications
        $stmt = $pdo->prepare("
            SELECT 'comment' as type, c.id, c.author_name as title,
                   CONCAT('New comment on \"', p.title, '\"') as message,
                   c.created_at
            FROM comments c
            LEFT JOIN posts p ON c.post_id = p.id
            WHERE c.status = 'pending'
            ORDER BY c.created_at DESC
            LIMIT 5
        ");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        // Silently handle database errors
    }
}

// Page titles mapping
$page_titles = [
    'dashboard' => 'Dashboard',
    'posts' => 'Posts',
    'post-new' => 'Add New Post',
    'post-edit' => 'Edit Post',
    'media' => 'Media Library',
    'categories' => 'Categories',
    'tags' => 'Tags',
    'comments' => 'Comments',
    'users' => 'Users',
    'settings' => 'Settings',
    'analytics' => 'Analytics'
];

$page_title = $page_titles[$current_page] ?? 'Admin Panel';
?>

<header class="admin-header">
    <div class="header-left">
        <!-- Mobile menu toggle -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <ol>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <?php if ($current_page !== 'dashboard'): ?>
                <li class="active"><?php echo htmlspecialchars($page_title); ?></li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>

    <div class="header-right">
        <!-- Notifications -->
        <div class="notifications-dropdown">
            <button class="notifications-toggle" id="notificationsToggle">
                <i class="fas fa-bell"></i>
                <?php if ($pending_comments > 0): ?>
                <span class="notification-badge"><?php echo $pending_comments; ?></span>
                <?php endif; ?>
            </button>

            <div class="notifications-menu" id="notificationsMenu">
                <div class="notifications-header">
                    <h3>Notifications</h3>
                    <?php if (count($notifications) > 0): ?>
                    <button class="mark-all-read">Mark all read</button>
                    <?php endif; ?>
                </div>

                <div class="notifications-list">
                    <?php if (empty($notifications)): ?>
                    <div class="no-notifications">
                        <i class="fas fa-check-circle"></i>
                        <p>All caught up!</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item">
                            <div class="notification-icon">
                                <i class="fas fa-comment"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title"><?php echo htmlspecialchars($notification['message']); ?></div>
                                <div class="notification-time"><?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="notifications-footer">
                            <a href="comments.php">View all comments</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Menu -->
        <div class="user-menu-dropdown">
            <button class="user-menu-toggle" id="userMenuToggle">
                <div class="user-avatar">
                    <img src="../logo/akanyenyeri logo.png" alt="User Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($current_user['full_name'] ?? 'User'); ?></div>
                    <div class="user-role"><?php echo ucfirst($current_user['role'] ?? 'user'); ?></div>
                </div>
                <i class="fas fa-chevron-down"></i>
            </button>

            <div class="user-menu" id="userMenu">
                <div class="user-menu-header">
                    <div class="user-avatar-large">
                        <img src="../logo/akanyenyeri logo.png" alt="User Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    </div>
                    <div class="user-details">
                        <div class="name"><?php echo htmlspecialchars($current_user['full_name'] ?? 'User'); ?></div>
                        <div class="email"><?php echo htmlspecialchars($current_user['email'] ?? ''); ?></div>
                        <div class="role-badge"><?php echo ucfirst($current_user['role'] ?? 'user'); ?></div>
                    </div>
                </div>

                <ul class="user-menu-items">
                    <li>
                        <a href="users.php?edit=<?php echo $current_user['id']; ?>">
                            <i class="fas fa-user-edit"></i>
                            Edit Profile
                        </a>
                    </li>
                    <li>
                        <a href="settings.php">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a href="../index.php" target="_blank">
                            <i class="fas fa-external-link-alt"></i>
                            View Website
                        </a>
                    </li>
                    <li>
                        <a href="../status.php" target="_blank">
                            <i class="fas fa-heartbeat"></i>
                            System Status
                        </a>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a href="php/logout.php" class="logout-link">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

<style>
                    <?php if (count($notifications) > 0): ?>
                    <button class="mark-all-read">Mark all read</button>
                    <?php endif; ?>
                </div>

                <div class="notifications-list">
                    <?php if (empty($notifications)): ?>
                    <div class="no-notifications">
                        <i class="fas fa-check-circle"></i>
                        <p>All caught up!</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item">
                            <div class="notification-icon">
                                <i class="fas fa-comment"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title"><?php echo htmlspecialchars($notification['message']); ?></div>
                                <div class="notification-time"><?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="notifications-footer">
                            <a href="comments.php">View all comments</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Menu -->
        <div class="user-menu-dropdown">
            <button class="user-menu-toggle" id="userMenuToggle">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($current_user['full_name'] ?? 'User'); ?></div>
                    <div class="user-role"><?php echo ucfirst($current_user['role'] ?? 'user'); ?></div>
                </div>
                <i class="fas fa-chevron-down"></i>
            </button>

            <div class="user-menu" id="userMenu">
                <div class="user-menu-header">
                    <div class="user-avatar-large">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <div class="name"><?php echo htmlspecialchars($current_user['full_name'] ?? 'User'); ?></div>
                        <div class="email"><?php echo htmlspecialchars($current_user['email'] ?? ''); ?></div>
                        <div class="role-badge"><?php echo ucfirst($current_user['role'] ?? 'user'); ?></div>
                    </div>
                </div>

                <ul class="user-menu-items">
                    <li>
                        <a href="users.php?edit=<?php echo $current_user['id']; ?>">
                            <i class="fas fa-user-edit"></i>
                            Edit Profile
                        </a>
                    </li>
                    <li>
                        <a href="settings.php">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a href="../index.php" target="_blank">
                            <i class="fas fa-external-link-alt"></i>
                            View Website
                        </a>
                    </li>
                    <li>
                        <a href="../status.php" target="_blank">
                            <i class="fas fa-heartbeat"></i>
                            System Status
                        </a>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a href="php/logout.php" class="logout-link">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

<style>
.admin-header {
    height: 60px;
    background: white;
    border-bottom: 1px solid #e1e5e9;
    padding: 0 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: fixed;
    top: 0;
    left: 250px;
    right: 0;
    z-index: 999;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #646970;
    cursor: pointer;
    padding: 0.5rem;
}

.breadcrumbs ol {
    list-style: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    padding: 0;
    font-size: 0.9rem;
}

.breadcrumbs li:not(:last-child)::after {
    content: '/';
    margin-left: 0.5rem;
    color: #8c8f94;
}

.breadcrumbs a {
    color: #2271b1;
    text-decoration: none;
}

.breadcrumbs a:hover {
    text-decoration: underline;
}

.breadcrumbs .active {
    color: #50575e;
    font-weight: 500;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.quick-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quick-action {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #2271b1;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: background 0.2s ease;
}

.quick-action:hover {
    background: #135e96;
    color: white;
}

.action-text {
    display: none;
}

.notifications-dropdown,
.user-menu-dropdown {
    position: relative;
}

.notifications-toggle,
.user-menu-toggle {
    background: none;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    border-radius: 4px;
    transition: background 0.2s ease;
}

.notifications-toggle:hover,
.user-menu-toggle:hover {
    background: #f6f7f7;
}

.notifications-toggle {
    position: relative;
    font-size: 1.2rem;
    color: #646970;
}

.notification-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: #d63638;
    color: white;
    font-size: 0.7rem;
    padding: 0.15rem 0.4rem;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

.user-avatar {
    width: 32px;
    height: 32px;
    background: #2271b1;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.9rem;
}

.user-info {
    text-align: left;
}

.user-name {
    font-size: 0.9rem;
    font-weight: 500;
    color: #1d2327;
    line-height: 1.2;
}

.user-role {
    font-size: 0.8rem;
    color: #646970;
    text-transform: capitalize;
}

.notifications-menu,
.user-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    min-width: 320px;
    z-index: 1001;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
}

.notifications-menu.show,
.user-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.notifications-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #f0f0f1;
}

.notifications-header h3 {
    margin: 0;
    font-size: 1rem;
    color: #1d2327;
}

.mark-all-read {
    background: none;
    border: none;
    color: #2271b1;
    font-size: 0.8rem;
    cursor: pointer;
}

.notifications-list {
    max-height: 300px;
    overflow-y: auto;
}

.no-notifications {
    text-align: center;
    padding: 2rem;
    color: #646970;
}

.no-notifications i {
    font-size: 2rem;
    color: #00a32a;
    margin-bottom: 0.5rem;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f6f7f7;
    cursor: pointer;
    transition: background 0.2s ease;
}

.notification-item:hover {
    background: #f6f7f7;
}

.notification-icon {
    width: 32px;
    height: 32px;
    background: #f0f6fc;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2271b1;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-size: 0.9rem;
    color: #1d2327;
    line-height: 1.4;
    margin-bottom: 0.25rem;
}

.notification-time {
    font-size: 0.8rem;
    color: #646970;
}

.notifications-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid #f0f0f1;
    text-align: center;
}

.notifications-footer a {
    color: #2271b1;
    text-decoration: none;
    font-size: 0.9rem;
}

.user-menu {
    min-width: 280px;
}

.user-menu-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-bottom: 1px solid #f0f0f1;
}

.user-avatar-large {
    width: 48px;
    height: 48px;
    background: #2271b1;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.user-details .name {
    font-weight: 600;
    color: #1d2327;
    margin-bottom: 0.25rem;
}

.user-details .email {
    font-size: 0.8rem;
    color: #646970;
    margin-bottom: 0.5rem;
}

.role-badge {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    background: #f0f6fc;
    color: #2271b1;
    font-size: 0.7rem;
    border-radius: 12px;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.user-menu-items {
    list-style: none;
    margin: 0;
    padding: 0.5rem 0;
}

.user-menu-items li.divider {
    height: 1px;
    background: #f0f0f1;
    margin: 0.5rem 0;
}

.user-menu-items a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: #50575e;
    text-decoration: none;
    transition: background 0.2s ease;
}

.user-menu-items a:hover {
    background: #f6f7f7;
}

.user-menu-items a.logout-link {
    color: #d63638;
}

.user-menu-items a i {
    width: 16px;
    text-align: center;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .admin-header {
        left: 0;
        padding: 0 1rem;
    }

    .mobile-menu-toggle {
        display: block;
    }

    .breadcrumbs {
        display: none;
    }

    .quick-actions .action-text {
        display: none;
    }

    .quick-action {
        padding: 0.5rem;
        min-width: 40px;
        justify-content: center;
    }

    .user-info {
        display: none;
    }

    .notifications-menu,
    .user-menu {
        min-width: 280px;
    }
}

/* Large screens */
@media (min-width: 1200px) {
    .quick-actions .action-text {
        display: block;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.querySelector('.admin-sidebar');

    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
        });
    }

    // Notifications dropdown
    const notificationsToggle = document.getElementById('notificationsToggle');
    const notificationsMenu = document.getElementById('notificationsMenu');

    if (notificationsToggle && notificationsMenu) {
        notificationsToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationsMenu.classList.toggle('show');
            // Close user menu if open
            const userMenu = document.getElementById('userMenu');
            if (userMenu) userMenu.classList.remove('show');
        });
    }

    // User menu dropdown
    const userMenuToggle = document.getElementById('userMenuToggle');
    const userMenu = document.getElementById('userMenu');

    if (userMenuToggle && userMenu) {
        userMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('show');
            // Close notifications menu if open
            if (notificationsMenu) notificationsMenu.classList.remove('show');
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        if (notificationsMenu) notificationsMenu.classList.remove('show');
        if (userMenu) userMenu.classList.remove('show');
    });

    // Prevent dropdown from closing when clicking inside
    if (notificationsMenu) {
        notificationsMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    if (userMenu) {
        userMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // Mark all notifications as read
    const markAllRead = document.querySelector('.mark-all-read');
    if (markAllRead) {
        markAllRead.addEventListener('click', function() {
            // Add AJAX call to mark notifications as read
            console.log('Mark all notifications as read');
        });
    }
});
</script>
