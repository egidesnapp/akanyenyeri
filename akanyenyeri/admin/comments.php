<?php
/**
 * Comments Management - Akanyenyeri Magazine Admin
 * Functional PHP page for managing comments with moderation functionality
 */

session_start();
require_once 'php/auth_check.php';
require_once '../config/database.php';

// Require authentication
requireAuth();

// Get database connection
$pdo = getDB();

// Handle form submissions
$success_message = '';
$error_message = '';

// Bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    try {
        // Validate CSRF token
        if (!validateCSRF($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }

        $action = $_POST['bulk_action'];
        $comment_ids = $_POST['comment_ids'] ?? [];

        if (empty($comment_ids)) {
            throw new Exception('No comments selected');
        }

        $placeholders = str_repeat('?,', count($comment_ids) - 1) . '?';

        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id IN ($placeholders)");
                $stmt->execute($comment_ids);
                $success_message = count($comment_ids) . ' comments approved successfully!';
                break;

            case 'pending':
                $stmt = $pdo->prepare("UPDATE comments SET status = 'pending' WHERE id IN ($placeholders)");
                $stmt->execute($comment_ids);
                $success_message = count($comment_ids) . ' comments moved to pending!';
                break;

            case 'spam':
                $stmt = $pdo->prepare("UPDATE comments SET status = 'spam' WHERE id IN ($placeholders)");
                $stmt->execute($comment_ids);
                $success_message = count($comment_ids) . ' comments marked as spam!';
                break;

            case 'delete':
                if (!canDo('moderate_comments')) {
                    throw new Exception('You do not have permission to delete comments');
                }

                $stmt = $pdo->prepare("DELETE FROM comments WHERE id IN ($placeholders)");
                $stmt->execute($comment_ids);
                $success_message = count($comment_ids) . ' comments deleted successfully!';
                break;

            default:
                throw new Exception('Invalid action');
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Single comment actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $comment_id = intval($_GET['id']);
    $action = $_GET['action'];

    try {
        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
                $stmt->execute([$comment_id]);
                $success_message = 'Comment approved successfully!';
                break;

            case 'pending':
                $stmt = $pdo->prepare("UPDATE comments SET status = 'pending' WHERE id = ?");
                $stmt->execute([$comment_id]);
                $success_message = 'Comment moved to pending!';
                break;

            case 'spam':
                $stmt = $pdo->prepare("UPDATE comments SET status = 'spam' WHERE id = ?");
                $stmt->execute([$comment_id]);
                $success_message = 'Comment marked as spam!';
                break;

            case 'delete':
                if (!canDo('moderate_comments')) {
                    throw new Exception('You do not have permission to delete comments');
                }

                $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
                $stmt->execute([$comment_id]);
                $success_message = 'Comment deleted successfully!';
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search_query = trim($_GET['search'] ?? '');
$per_page = 20;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
}

if (!empty($search_query)) {
    $where_conditions[] = "(c.content LIKE ? OR c.author_name LIKE ? OR p.title LIKE ?)";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get comments with pagination
try {
    $comments_query = "
        SELECT c.*, p.title as post_title, p.slug as post_slug
        FROM comments c
        LEFT JOIN posts p ON c.post_id = p.id
        $where_clause
        ORDER BY c.created_at DESC
        LIMIT $per_page OFFSET $offset
    ";

    $stmt = $pdo->prepare($comments_query);
    $stmt->execute($params);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $count_query = "
        SELECT COUNT(*) as total
        FROM comments c
        LEFT JOIN posts p ON c.post_id = p.id
        $where_clause
    ";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_comments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $total_pages = ceil($total_comments / $per_page);

} catch (Exception $e) {
    $error_message = "Error loading comments: " . $e->getMessage();
    $comments = [];
    $total_comments = 0;
    $total_pages = 0;
}

// Get comment statistics
$stats = ['all' => 0, 'approved' => 0, 'pending' => 0, 'spam' => 0];
try {
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count
        FROM comments
        GROUP BY status
    ");
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($status_counts as $stat) {
        $stats[$stat['status']] = $stat['count'];
        $stats['all'] += $stat['count'];
    }
} catch (Exception $e) {
    // Silent error handling
}

// Get current user info
$current_user = getCurrentUser();

// Helper functions
function getStatusBadge($status) {
    $badges = [
        'approved' => '<span class="status-badge status-approved"><i class="fas fa-check-circle"></i> Approved</span>',
        'pending' => '<span class="status-badge status-pending"><i class="fas fa-clock"></i> Pending</span>',
        'spam' => '<span class="status-badge status-spam"><i class="fas fa-ban"></i> Spam</span>'
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

function getGravatar($email, $size = 40) {
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=identicon";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments - Akanyenyeri Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">

    <style>
        .comments-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
            gap: 1rem;
        }

        .comments-title h1 {
            margin: 0 0 0.5rem 0;
            color: #1d2327;
        }

        .comment-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
        }

        .comment-stat {
            color: #646970;
        }

        .comment-stat.active {
            color: #2271b1;
            font-weight: 600;
        }

        .comment-stat a {
            color: inherit;
            text-decoration: none;
        }

        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .filters-row {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
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

        .comments-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .bulk-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f6f7f7;
            border-radius: 6px;
        }

        .bulk-actions select {
            padding: 0.5rem;
            border: 1px solid #8c8f94;
            border-radius: 4px;
        }

        .comments-list {
            padding: 1rem;
        }

        .comment-item {
            display: flex;
            gap: 1rem;
            padding: 1.5rem;
            border: 1px solid #f0f0f1;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }

        .comment-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-color: #e1e5e9;
        }

        .comment-checkbox {
            width: auto;
            margin: 0;
            align-self: flex-start;
            margin-top: 0.5rem;
        }

        .comment-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .comment-content {
            flex: 1;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .comment-author {
            font-weight: 600;
            color: #1d2327;
            margin-bottom: 0.25rem;
        }

        .comment-email {
            font-size: 0.8rem;
            color: #646970;
            margin-bottom: 0.25rem;
        }

        .comment-meta {
            font-size: 0.8rem;
            color: #646970;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .comment-post {
            color: #2271b1;
            text-decoration: none;
        }

        .comment-post:hover {
            text-decoration: underline;
        }

        .comment-text {
            color: #1d2327;
            line-height: 1.6;
            margin: 1rem 0;
            background: #f9f9f9;
            padding: 1rem;
            border-radius: 6px;
            border-left: 3px solid #e1e5e9;
        }

        .comment-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f1;
        }

        .comment-action {
            color: #2271b1;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            transition: background 0.2s ease;
        }

        .comment-action:hover {
            background: #f0f6fc;
            text-decoration: none;
        }

        .comment-action.approve {
            color: #00a32a;
        }

        .comment-action.spam {
            color: #f56e00;
        }

        .comment-action.delete {
            color: #d63638;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #cce5ff;
            color: #004085;
        }

        .status-spam {
            background: #fff3cd;
            color: #856404;
        }

        .no-comments {
            text-align: center;
            padding: 3rem;
            color: #646970;
        }

        .no-comments i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #c3c4c7;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
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
            .comments-header {
                flex-direction: column;
                align-items: stretch;
            }

            .filters-row {
                flex-direction: column;
            }

            .filter-group {
                min-width: auto;
            }

            .comment-item {
                flex-direction: column;
                gap: 0.75rem;
            }

            .comment-header {
                flex-direction: column;
                gap: 0.5rem;
            }

            .comment-actions {
                flex-wrap: wrap;
            }

            .bulk-actions {
                flex-wrap: wrap;
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
                <!-- Comments Header -->
                <div class="comments-header">
                    <div class="comments-title">
                        <h1><i class="fas fa-comments"></i> Comments</h1>
                        <div class="comment-stats">
                            <div class="comment-stat <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                                <a href="?status=all">All (<?php echo $stats['all']; ?>)</a>
                            </div>
                            <div class="comment-stat <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                                <a href="?status=approved">Approved (<?php echo $stats['approved']; ?>)</a>
                            </div>
                            <div class="comment-stat <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                                <a href="?status=pending">Pending (<?php echo $stats['pending']; ?>)</a>
                            </div>
                            <div class="comment-stat <?php echo $status_filter === 'spam' ? 'active' : ''; ?>">
                                <a href="?status=spam">Spam (<?php echo $stats['spam']; ?>)</a>
                            </div>
                        </div>
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

                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" class="filters-form">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">

                        <div class="filters-row">
                            <div class="filter-group">
                                <label for="search">Search Comments</label>
                                <input type="text" id="search" name="search" class="filter-control"
                                       placeholder="Search by content, author, or post title..."
                                       value="<?php echo htmlspecialchars($search_query); ?>">
                            </div>

                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="comments.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Comments Container -->
                <div class="comments-container">
                    <?php if (!empty($comments)): ?>
                    <!-- Bulk Actions -->
                    <form method="POST" id="commentsForm">
                        <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">

                        <div class="bulk-actions">
                            <input type="checkbox" id="select-all" class="comment-checkbox">
                            <label for="select-all">Select All</label>

                            <select name="bulk_action" id="bulk-action">
                                <option value="">Bulk Actions</option>
                                <option value="approve">Approve</option>
                                <option value="pending">Move to Pending</option>
                                <option value="spam">Mark as Spam</option>
                                <?php if (canDo('moderate_comments')): ?>
                                <option value="delete">Delete</option>
                                <?php endif; ?>
                            </select>

                            <button type="submit" class="btn btn-secondary" id="apply-bulk">Apply</button>
                        </div>

                        <div class="comments-list">
                            <?php foreach ($comments as $comment): ?>
                            <div class="comment-item">
                                <input type="checkbox" name="comment_ids[]" value="<?php echo $comment['id']; ?>" class="comment-checkbox comment-select">

                                <img src="<?php echo getGravatar($comment['author_email']); ?>"
                                     alt="<?php echo htmlspecialchars($comment['author_name']); ?>"
                                     class="comment-avatar">

                                <div class="comment-content">
                                    <div class="comment-header">
                                        <div>
                                            <div class="comment-author"><?php echo htmlspecialchars($comment['author_name']); ?></div>
                                            <div class="comment-email"><?php echo htmlspecialchars($comment['author_email']); ?></div>
                                            <div class="comment-meta">
                                                <span><?php echo timeAgo($comment['created_at']); ?></span>
                                                <span>•</span>
                                                <a href="../single.php?slug=<?php echo htmlspecialchars($comment['post_slug']); ?>#comment-<?php echo $comment['id']; ?>"
                                                   class="comment-post" target="_blank">
                                                    <?php echo htmlspecialchars($comment['post_title'] ?? 'Unknown Post'); ?>
                                                </a>
                                            </div>
                                        </div>
                                        <div>
                                            <?php echo getStatusBadge($comment['status']); ?>
                                        </div>
                                    </div>

                                    <div class="comment-text">
                                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                    </div>

                                    <div class="comment-actions">
                                        <?php if ($comment['status'] !== 'approved'): ?>
                                        <a href="?action=approve&id=<?php echo $comment['id']; ?>" class="comment-action approve">
                                            <i class="fas fa-check"></i> Approve
                                        </a>
                                        <?php endif; ?>

                                        <?php if ($comment['status'] !== 'pending'): ?>
                                        <a href="?action=pending&id=<?php echo $comment['id']; ?>" class="comment-action">
                                            <i class="fas fa-clock"></i> Pending
                                        </a>
                                        <?php endif; ?>

                                        <?php if ($comment['status'] !== 'spam'): ?>
                                        <a href="?action=spam&id=<?php echo $comment['id']; ?>" class="comment-action spam">
                                            <i class="fas fa-ban"></i> Spam
                                        </a>
                                        <?php endif; ?>

                                        <a href="../single.php?slug=<?php echo htmlspecialchars($comment['post_slug']); ?>#comment-<?php echo $comment['id']; ?>"
                                           class="comment-action" target="_blank">
                                            <i class="fas fa-external-link-alt"></i> View
                                        </a>

                                        <?php if (canDo('moderate_comments')): ?>
                                        <a href="?action=delete&id=<?php echo $comment['id']; ?>" class="comment-action delete"
                                           onclick="return confirm('Are you sure you want to delete this comment?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </form>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <div class="pagination-info">
                            Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_comments); ?> of <?php echo $total_comments; ?> comments
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
                    <div class="no-comments">
                        <i class="fas fa-comments"></i>
                        <h3>No comments found</h3>
                        <p>
                            <?php if (!empty($search_query) || $status_filter !== 'all'): ?>
                                No comments match your current filters. <a href="comments.php">Clear filters</a> to see all comments.
                            <?php else: ?>
                                No comments have been submitted yet.
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Select all functionality
            const selectAll = document.getElementById('select-all');
            const commentSelects = document.querySelectorAll('.comment-select');
            const bulkActionSelect = document.getElementById('bulk-action');
            const applyBulkBtn = document.getElementById('apply-bulk');

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    commentSelects.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    updateBulkActionsState();
                });
            }

            // Individual checkbox change
            commentSelects.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const allChecked = Array.from(commentSelects).every(cb => cb.checked);
                    const someChecked = Array.from(commentSelects).some(cb => cb.checked);

                    if (selectAll) {
                        selectAll.checked = allChecked;
                        selectAll.indeterminate = someChecked && !allChecked;
                    }

                    updateBulkActionsState();
                });
            });

            // Update bulk actions state
            function updateBulkActionsState() {
                const hasSelection = Array.from(commentSelects).some(cb => cb.checked);
                if (bulkActionSelect) bulkActionSelect.disabled = !hasSelection;
                if (applyBulkBtn) applyBulkBtn.disabled = !hasSelection;
            }

            // Bulk action form submission
            const commentsForm = document.getElementById('commentsForm');
            if (commentsForm) {
                commentsForm.addEventListener('submit', function(e) {
                    const selectedComments = Array.from(commentSelects).filter(cb => cb.checked);
                    const action = bulkActionSelect.value;

                    if (selectedComments.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one comment.');
                        return;
                    }

                    if (!action) {
                        e.preventDefault();
                        alert('Please select a bulk action.');
                        return;
                    }

                    if (action === 'delete') {
                        const confirmMessage = `Are you sure you want to delete ${selectedComments.length} comment(s)? This action cannot be undone.`;
                        if (!confirm(confirmMessage)) {
                            e.preventDefault();
                            return;
                        }
                    }
                });
            }

            // Initialize bulk actions state
            updateBulkActionsState();

            // Auto-submit search form with delay
            const searchInput = document.getElementById('search');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        if (this.value.length >= 3 || this.value.length === 0) {
                            this.form.submit();
                        }
                    }, 500);
                });
            }
        });
    </script>
</body>
</html>
