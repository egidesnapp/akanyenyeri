<?php
/**
 * Users Management - Akanyenyeri Magazine Admin
 * Functional PHP page for managing users with role management functionality
 */

session_start();
require_once 'php/auth_check.php';
require_once '../config/database.php';

// Require authentication and admin role
requireAuth();
requireRole('admin', 'You need admin privileges to manage users');

// Get database connection
$pdo = getDB();

// Handle form submissions
$success_message = '';
$error_message = '';

// Add new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    try {
        // Validate CSRF token
        if (!validateCSRF($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'author';
        $status = $_POST['status'] ?? 'active';

        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
            throw new Exception('All fields are required');
        }

        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters long');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }

        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            throw new Exception('Username or email already exists');
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$username, $email, $hashed_password, $full_name, $role, $status]);

        $success_message = "User '{$username}' added successfully!";

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    try {
        // Validate CSRF token
        if (!validateCSRF($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }

        $user_id = intval($_POST['user_id']);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? 'author';
        $status = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';

        // Validation
        if (empty($username) || empty($email) || empty($full_name)) {
            throw new Exception('Username, email, and full name are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }

        // Check if username or email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception('Username or email already exists');
        }

        // Prevent user from demoting themselves
        if ($user_id == $_SESSION['admin_user_id'] && $role !== 'admin') {
            throw new Exception('You cannot change your own role');
        }

        // Update user
        $update_query = "
            UPDATE users
            SET username = ?, email = ?, full_name = ?, role = ?, status = ?, updated_at = NOW()
        ";
        $params = [$username, $email, $full_name, $role, $status];

        // Update password if provided
        if (!empty($password)) {
            if (strlen($password) < 6) {
                throw new Exception('Password must be at least 6 characters long');
            }
            $update_query .= ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $update_query .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $pdo->prepare($update_query);
        $stmt->execute($params);

        $success_message = "User '{$username}' updated successfully!";

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Delete user
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $user_id = intval($_GET['id']);

        // Prevent user from deleting themselves
        if ($user_id == $_SESSION['admin_user_id']) {
            throw new Exception('You cannot delete your own account');
        }

        // Check if user has posts
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ?");
        $stmt->execute([$user_id]);
        $post_count = $stmt->fetchColumn();

        if ($post_count > 0) {
            throw new Exception("Cannot delete user with {$post_count} posts. Please reassign posts first.");
        }

        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        $success_message = 'User deleted successfully!';

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get search parameters
$role_filter = $_GET['role'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$search_query = trim($_GET['search'] ?? '');
$per_page = 20;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($role_filter !== 'all') {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($search_query)) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get users with post counts
try {
    $users_query = "
        SELECT u.*, COUNT(p.id) as post_count
        FROM users u
        LEFT JOIN posts p ON u.id = p.author_id
        $where_clause
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT $per_page OFFSET $offset
    ";

    $stmt = $pdo->prepare($users_query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $count_query = "
        SELECT COUNT(*) as total
        FROM users u
        $where_clause
    ";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $total_pages = ceil($total_users / $per_page);

} catch (Exception $e) {
    $error_message = "Error loading users: " . $e->getMessage();
    $users = [];
    $total_users = 0;
    $total_pages = 0;
}

// Get user for editing
$edit_user = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get user statistics
$stats = ['all' => 0, 'admin' => 0, 'editor' => 0, 'author' => 0];
try {
    $stmt = $pdo->query("
        SELECT role, COUNT(*) as count
        FROM users
        WHERE status = 'active'
        GROUP BY role
    ");
    $role_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($role_counts as $stat) {
        $stats[$stat['role']] = $stat['count'];
        $stats['all'] += $stat['count'];
    }
} catch (Exception $e) {
    // Silent error handling
}

// Get current user info
$current_user = getCurrentUser();

// Helper functions
function getRoleBadge($role) {
    $badges = [
        'admin' => '<span class="role-badge role-admin"><i class="fas fa-crown"></i> Admin</span>',
        'editor' => '<span class="role-badge role-editor"><i class="fas fa-edit"></i> Editor</span>',
        'author' => '<span class="role-badge role-author"><i class="fas fa-pen"></i> Author</span>'
    ];
    return $badges[$role] ?? '<span class="role-badge">Unknown</span>';
}

function getStatusBadge($status) {
    $badges = [
        'active' => '<span class="status-badge status-active"><i class="fas fa-check-circle"></i> Active</span>',
        'inactive' => '<span class="status-badge status-inactive"><i class="fas fa-times-circle"></i> Inactive</span>'
    ];
    return $badges[$status] ?? '<span class="status-badge">Unknown</span>';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Akanyenyeri Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">

    <style>
        .users-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        .users-section {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .users-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
        }

        .user-stat {
            color: #646970;
        }

        .user-stat.active {
            color: #2271b1;
            font-weight: 600;
        }

        .user-stat a {
            color: inherit;
            text-decoration: none;
        }

        .filters-section {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f1;
        }

        .filters-row {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 150px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #50575e;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .filter-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .filter-actions {
            display: flex;
            gap: 0.5rem;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f1;
        }

        .users-table th {
            background: #f6f7f7;
            font-weight: 600;
            color: #1d2327;
            font-size: 0.9rem;
        }

        .users-table tbody tr:hover {
            background: #f6f7f7;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #2271b1;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            color: #1d2327;
            margin-bottom: 0.25rem;
        }

        .user-email {
            font-size: 0.8rem;
            color: #646970;
        }

        .role-badge,
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .role-admin {
            background: #fff3cd;
            color: #856404;
        }

        .role-editor {
            background: #e3f2fd;
            color: #1976d2;
        }

        .role-author {
            background: #e8f5e9;
            color: #388e3c;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .user-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-link {
            color: #2271b1;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .action-link:hover {
            text-decoration: underline;
        }

        .action-link.delete {
            color: #d63638;
        }

        .user-form {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 80px;
        }

        .form-section h3 {
            margin: 0 0 1rem 0;
            color: #1d2327;
            font-size: 1.1rem;
            font-weight: 600;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #2271b1;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #646970;
            cursor: pointer;
        }

        .no-users {
            text-align: center;
            padding: 3rem;
            color: #646970;
        }

        .no-users i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #c3c4c7;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: #f6f7f7;
            border-top: 1px solid #f0f0f1;
        }

        .pagination-info {
            color: #646970;
            font-size: 0.9rem;
        }

        .pagination-links {
            display: flex;
            gap: 0.5rem;
        }

        .pagination-link {
            padding: 0.5rem 0.75rem;
            text-decoration: none;
            color: #2271b1;
            border-radius: 4px;
            transition: background 0.2s ease;
        }

        .pagination-link:hover {
            background: #f0f0f1;
        }

        .pagination-link.current {
            background: #2271b1;
            color: white;
        }

        @media (max-width: 768px) {
            .users-layout {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .user-form {
                position: static;
            }

            .filters-row {
                flex-direction: column;
            }

            .filter-group {
                min-width: auto;
            }

            .users-table {
                font-size: 0.8rem;
            }

            .users-table th,
            .users-table td {
                padding: 0.75rem 0.5rem;
            }

            .user-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .pagination {
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

                <div class="users-layout">
                    <!-- Users List -->
                    <div class="users-section">
                        <!-- Header -->
                        <div class="users-header">
                            <h2><i class="fas fa-users"></i> Users</h2>
                            <div class="user-stats">
                                <div class="user-stat <?php echo $role_filter === 'all' ? 'active' : ''; ?>">
                                    <a href="?role=all">All (<?php echo $stats['all']; ?>)</a>
                                </div>
                                <div class="user-stat <?php echo $role_filter === 'admin' ? 'active' : ''; ?>">
                                    <a href="?role=admin">Admins (<?php echo $stats['admin']; ?>)</a>
                                </div>
                                <div class="user-stat <?php echo $role_filter === 'editor' ? 'active' : ''; ?>">
                                    <a href="?role=editor">Editors (<?php echo $stats['editor']; ?>)</a>
                                </div>
                                <div class="user-stat <?php echo $role_filter === 'author' ? 'active' : ''; ?>">
                                    <a href="?role=author">Authors (<?php echo $stats['author']; ?>)</a>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="filters-section">
                            <form method="GET" class="filters-form">
                                <div class="filters-row">
                                    <div class="filter-group">
                                        <label for="status">Status</label>
                                        <select id="status" name="status" class="filter-control">
                                            <option value="all">All Status</option>
                                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>

                                    <div class="filter-group">
                                        <label for="search">Search Users</label>
                                        <input type="text" id="search" name="search" class="filter-control"
                                               placeholder="Search by name, username, or email..."
                                               value="<?php echo htmlspecialchars($search_query); ?>">
                                    </div>

                                    <div class="filter-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Filter
                                        </button>
                                        <a href="users.php" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Clear
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Users Table -->
                        <?php if (!empty($users)): ?>
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Posts</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['full_name'] ?: $user['username'], 0, 1)); ?>
                                            </div>
                                            <div class="user-details">
                                                <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo getRoleBadge($user['role']); ?></td>
                                    <td><?php echo getStatusBadge($user['status']); ?></td>
                                    <td>
                                        <?php if ($user['post_count'] > 0): ?>
                                        <a href="posts.php?author=<?php echo $user['id']; ?>" class="action-link">
                                            <?php echo $user['post_count']; ?>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo timeAgo($user['created_at']); ?></td>
                                    <td class="user-actions">
                                        <a href="?edit=<?php echo $user['id']; ?>" class="action-link">Edit</a>

                                        <?php if ($user['id'] != $_SESSION['admin_user_id']): ?>
                                        <a href="?action=delete&id=<?php echo $user['id']; ?>" class="action-link delete"
                                           onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <div class="pagination-info">
                                Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_users); ?> of <?php echo $total_users; ?> users
                            </div>

                            <div class="pagination-links">
                                <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="pagination-link">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                                   class="pagination-link <?php echo $i === $page ? 'current' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="pagination-link">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php else: ?>
                        <div class="no-users">
                            <i class="fas fa-users"></i>
                            <h3>No users found</h3>
                            <p>
                                <?php if (!empty($search_query) || $role_filter !== 'all' || $status_filter !== 'all'): ?>
                                    No users match your current filters. <a href="users.php">Clear filters</a> to see all users.
                                <?php else: ?>
                                    No users found in the system.
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Add/Edit User Form -->
                    <div class="user-form">
                        <div class="form-section">
                            <h3>
                                <i class="fas fa-<?php echo $edit_user ? 'edit' : 'user-plus'; ?>"></i>
                                <?php echo $edit_user ? 'Edit User' : 'Add New User'; ?>
                            </h3>

                            <form method="POST" id="userForm">
                                <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                                <?php if ($edit_user): ?>
                                <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                                <?php endif; ?>

                                <div class="form-group">
                                    <label for="username">Username *</label>
                                    <input type="text" id="username" name="username" class="form-control" required
                                           placeholder="Enter username"
                                           value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" class="form-control" required
                                           placeholder="Enter email address"
                                           value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="full_name">Full Name *</label>
                                    <input type="text" id="full_name" name="full_name" class="form-control" required
                                           placeholder="Enter full name"
                                           value="<?php echo htmlspecialchars($edit_user['full_name'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="password">Password <?php echo $edit_user ? '' : '*'; ?></label>
                                    <div class="password-toggle">
                                        <input type="password" id="password" name="password" class="form-control"
                                               placeholder="<?php echo $edit_user ? 'Leave blank to keep current password' : 'Enter password'; ?>"
                                               <?php echo $edit_user ? '' : 'required'; ?>>
                                        <button type="button" onclick="togglePassword()">
                                            <i class="fas fa-eye" id="passwordIcon"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="role">Role *</label>
                                    <select id="role" name="role" class="form-control" required>
                                        <option value="author" <?php echo ($edit_user['role'] ?? 'author') === 'author' ? 'selected' : ''; ?>>Author</option>
                                        <option value="editor" <?php echo ($edit_user['role'] ?? '') === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                        <option value="admin" <?php echo ($edit_user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="status">Status *</label>
                                    <select id="status" name="status" class
