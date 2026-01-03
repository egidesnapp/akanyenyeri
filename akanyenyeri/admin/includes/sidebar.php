<?php
/**
 * Admin Sidebar Navigation Component
 * Reusable sidebar for all admin pages
 */

// Get current page for active menu highlighting
$current_page = basename($_SERVER["PHP_SELF"], ".php");
$current_user = getCurrentUser();
?>
<div class="admin-sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <a href="../index.php" target="_blank">
                <i class="fas fa-newspaper"></i>
                <span class="logo-text">Akanyenyeri</span>
            </a>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <!-- Dashboard -->
            <li class="nav-item <?php echo $current_page === "dashboard"
                ? "active"
                : ""; ?>">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Posts Section -->
            <li class="nav-section">
                <span class="section-title">Content</span>
            </li>

            <li class="nav-item <?php echo in_array($current_page, [
                "posts",
                "post-new",
                "post-edit",
            ])
                ? "active"
                : ""; ?>">
                <a href="posts.php" class="nav-link">
                    <i class="fas fa-file-alt"></i>
                    <span>Posts</span>
                    <?php // Show draft count badge
                    if (isset($pdo)) {
                        try {
                            $stmt = $pdo->query(
                                "SELECT COUNT(*) FROM posts WHERE status = 'draft'",
                            );
                            $draft_count = $stmt->fetchColumn();
                            if ($draft_count > 0) {
                                echo "<span class=\"badge\">{$draft_count}</span>";
                            }
                        } catch (Exception $e) {
                        }
                    } ?>
                </a>
                <ul class="nav-submenu <?php echo in_array($current_page, [
                    "posts",
                    "post-new",
                    "post-edit",
                ])
                    ? "show"
                    : ""; ?>">
                    <li><a href="posts.php" class="<?php echo $current_page ===
                    "posts"
                        ? "active"
                        : ""; ?>">All Posts</a></li>
                    <li><a href="post-new.php" class="<?php echo $current_page ===
                    "post-new"
                        ? "active"
                        : ""; ?>">Add New</a></li>
                </ul>
            </li>

            <li class="nav-item <?php echo $current_page === "media"
                ? "active"
                : ""; ?>">
                <a href="media.php" class="nav-link">
                    <i class="fas fa-photo-video"></i>
                    <span>Media Library</span>
                </a>
            </li>

            <!-- Organization Section -->
            <li class="nav-section">
                <span class="section-title">Organization</span>
            </li>

            <li class="nav-item <?php echo $current_page === "categories"
                ? "active"
                : ""; ?>">
                <a href="categories.php" class="nav-link">
                    <i class="fas fa-folder"></i>
                    <span>Categories</span>
                </a>
            </li>

            <li class="nav-item <?php echo $current_page === "tags"
                ? "active"
                : ""; ?>">
                <a href="tags.php" class="nav-link">
                    <i class="fas fa-tags"></i>
                    <span>Tags</span>
                </a>
            </li>

            <!-- Comments -->
            <li class="nav-item <?php echo $current_page === "comments"
                ? "active"
                : ""; ?>">
                <a href="comments.php" class="nav-link">
                    <i class="fas fa-comments"></i>
                    <span>Comments</span>
                    <?php // Show pending comments badge
                    if (isset($pdo)) {
                        try {
                            $stmt = $pdo->query(
                                "SELECT COUNT(*) FROM comments WHERE status = 'pending'",
                            );
                            $pending_count = $stmt->fetchColumn();
                            if ($pending_count > 0) {
                                echo "<span class=\"badge badge-warning\">{$pending_count}</span>";
                            }
                        } catch (Exception $e) {
                        }
                    } ?>
                </a>
            </li>

            <!-- Users & Settings Section -->
            <?php if (hasRole("admin")): ?>
            <li class="nav-section">
                <span class="section-title">Administration</span>
            </li>

            <li class="nav-item <?php echo $current_page === "users"
                ? "active"
                : ""; ?>">
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>

            <li class="nav-item <?php echo $current_page === "settings"
                ? "active"
                : ""; ?>">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Analytics & Tools -->
            <li class="nav-section">
                <span class="section-title">Tools</span>
            </li>

            <li class="nav-item <?php echo $current_page === "analytics"
                ? "active"
                : ""; ?>">
                <a href="analytics.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="../status.php" class="nav-link" target="_blank">
                    <i class="fas fa-heartbeat"></i>
                    <span>System Status</span>
                </a>
            </li>

            <?php if (hasRole("admin")): ?>
            <li class="nav-item <?php echo $current_page === "security_audit"
                ? "active"
                : ""; ?>">
                <a href="security_audit.php" class="nav-link">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security Audit</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars(
                    $current_user["full_name"] ?? "User",
                ); ?></div>
                <div class="user-role"><?php echo ucfirst(
                    $current_user["role"] ?? "user",
                ); ?></div>
            </div>
        </div>

        <div class="footer-actions">
            <a href="users.php?edit=<?php echo $current_user['id']; ?>" class="action-btn" title="Profile">
                <i class="fas fa-user"></i>
            </a>
            <a href="../index.php" class="action-btn" title="View Site" target="_blank">
                <i class="fas fa-external-link-alt"></i>
            </a>
            <a href="php/logout.php" class="action-btn" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</div>

<style>
.admin-sidebar {
    width: 250px;
    background: var(--sidebar-bg, #1d2327);
    color: #c3c4c7;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    overflow-y: auto;
    z-index: 1000;
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    padding: 1rem;
    border-bottom: 1px solid #2c3338;
}

.sidebar-header .logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.sidebar-header .logo a {
    color: #f0f0f1;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.25rem;
    font-weight: 600;
}

.sidebar-header .logo i {
    font-size: 1.5rem;
    color: var(--primary-color, #2271b1);
}

.sidebar-nav {
    flex: 1;
    padding: 1rem 0;
}

.nav-menu {
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-section {
    padding: 0.75rem 1rem 0.5rem;
    margin-top: 1rem;
}

.nav-section:first-child {
    margin-top: 0;
}

.section-title {
    font-size: 0.8rem;
    font-weight: 600;
    color: #8c8f94;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.nav-item {
    position: relative;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: #c3c4c7;
    text-decoration: none;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.nav-link:hover {
    color: #f0f0f1;
    background: var(--sidebar-hover, #2c3338);
}

.nav-item.active > .nav-link {
    color: #f0f0f1;
    background: var(--sidebar-active, #2271b1);
    border-left-color: #ffffff;
}

.nav-link i {
    width: 20px;
    text-align: center;
    font-size: 1rem;
}

.badge {
    background: var(--primary-color, #2271b1);
    color: white;
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    margin-left: auto;
}

.badge-warning {
    background: #dba617;
}

.nav-submenu {
    list-style: none;
    margin: 0;
    padding: 0;
    background: rgba(0,0,0,0.2);
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.nav-submenu.show {
    max-height: 200px;
}

.nav-submenu li {
    padding: 0;
}

.nav-submenu a {
    display: block;
    padding: 0.5rem 1rem 0.5rem 3rem;
    color: #a7aaad;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.nav-submenu a:hover,
.nav-submenu a.active {
    color: #f0f0f1;
    background: rgba(255,255,255,0.1);
}

.sidebar-footer {
    border-top: 1px solid #2c3338;
    padding: 1rem;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: var(--primary-color, #2271b1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
}

.user-details {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-weight: 500;
    color: #f0f0f1;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role {
    font-size: 0.8rem;
    color: #8c8f94;
    text-transform: capitalize;
}

.footer-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    padding: 0.5rem;
    background: rgba(255,255,255,0.1);
    border: none;
    border-radius: 4px;
    color: #c3c4c7;
    text-decoration: none;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-btn:hover {
    background: rgba(255,255,255,0.2);
    color: #f0f0f1;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .admin-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .admin-sidebar.mobile-open {
        transform: translateX(0);
    }
}

/* Scrollbar styling */
.admin-sidebar::-webkit-scrollbar {
    width: 6px;
}

.admin-sidebar::-webkit-scrollbar-track {
    background: #1a1a1a;
}

.admin-sidebar::-webkit-scrollbar-thumb {
    background: #444;
    border-radius: 3px;
}

.admin-sidebar::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
