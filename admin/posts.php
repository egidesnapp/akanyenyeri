<?php
/**
 * Posts Management - Akanyenyeri Magazine Admin
 * Properly structured responsive page that fits the window
 */

session_start();
require_once "php/auth_check.php";
require_once "../config/database.php";

// Require authentication
requireAuth();

// Get database connection
$pdo = getDB();

// Handle bulk actions
$success_message = "";
$error_message = "";

// Check for success message from post creation
if (isset($_GET["success"]) && $_GET["success"] === "true") {
    $post_id = intval($_GET["id"] ?? 0);
    $status = $_GET["status"] ?? "draft";
    $status_text = ($status === "published") ? "published" : "saved as draft";
    $success_message = "Post created successfully and {$status_text}!";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["bulk_action"])) {
    try {
        // Validate CSRF token
        if (!validateCSRF($_POST["csrf_token"] ?? "")) {
            throw new Exception("Invalid security token");
        }

        $action = $_POST["bulk_action"];
        $post_ids = $_POST["post_ids"] ?? [];

        if (empty($post_ids)) {
            throw new Exception("No posts selected");
        }

        $placeholders = str_repeat("?,", count($post_ids) - 1) . "?";

        switch ($action) {
            case "publish":
                $stmt = $pdo->prepare(
                    "UPDATE posts SET status = 'published' WHERE id IN ($placeholders)",
                );
                $stmt->execute($post_ids);
                $success_message =
                    count($post_ids) . " posts published successfully!";
                break;

            case "draft":
                $stmt = $pdo->prepare(
                    "UPDATE posts SET status = 'draft' WHERE id IN ($placeholders)",
                );
                $stmt->execute($post_ids);
                $success_message = count($post_ids) . " posts moved to draft!";
                break;

            case "delete":
                if (!canDo("delete_posts")) {
                    throw new Exception(
                        "You do not have permission to delete posts",
                    );
                }
                $stmt = $pdo->prepare(
                    "DELETE FROM posts WHERE id IN ($placeholders)",
                );
                $stmt->execute($post_ids);
                $success_message =
                    count($post_ids) . " posts deleted successfully!";
                break;

            default:
                throw new Exception("Invalid action");
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Handle single post actions
if (isset($_GET["action"]) && isset($_GET["id"])) {
    $post_id = intval($_GET["id"]);
    $action = $_GET["action"];

    try {
        switch ($action) {
            case "publish":
                $stmt = $pdo->prepare(
                    "UPDATE posts SET status = 'published' WHERE id = ?",
                );
                $stmt->execute([$post_id]);
                $success_message = "Post published successfully!";
                break;

            case "draft":
                $stmt = $pdo->prepare(
                    "UPDATE posts SET status = 'draft' WHERE id = ?",
                );
                $stmt->execute([$post_id]);
                $success_message = "Post moved to draft!";
                break;

            case "delete":
                if (!canDo("delete_posts")) {
                    throw new Exception(
                        "You do not have permission to delete posts",
                    );
                }
                $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                $stmt->execute([$post_id]);
                $success_message = "Post deleted successfully!";
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET["status"] ?? "all";
$category_filter = $_GET["category"] ?? "";
$search_query = trim($_GET["search"] ?? "");
$per_page = 20;
$page = max(1, intval($_GET["page"] ?? 1));
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($status_filter !== "all") {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

if (!empty($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($search_query)) {
    $where_conditions[] = "(p.title LIKE ? OR p.content LIKE ?)";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
}

$where_clause = !empty($where_conditions)
    ? "WHERE " . implode(" AND ", $where_conditions)
    : "";

// Get posts with pagination
try {
    $query = "
        SELECT p.*,
               u.full_name as author_name,
               c.name as category_name,
               c.color as category_color
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        $where_clause
        ORDER BY p.created_at DESC
        LIMIT $per_page OFFSET $offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $count_query = "
        SELECT COUNT(*) as total
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        $where_clause
    ";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_posts = $stmt->fetch(PDO::FETCH_ASSOC)["total"];
    $total_pages = ceil($total_posts / $per_page);
} catch (Exception $e) {
    $error_message = "Error loading posts: " . $e->getMessage();
    $posts = [];
    $total_posts = 0;
    $total_pages = 0;
}

// Get categories for filter
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// Get post statistics
$stats = ["all" => 0, "published" => 0, "draft" => 0, "pending" => 0];
try {
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count
        FROM posts
        GROUP BY status
    ");
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($status_counts as $stat) {
        $stats[$stat["status"]] = $stat["count"];
        $stats["all"] += $stat["count"];
    }
} catch (Exception $e) {
    // Silent error handling
}

// Get current user info
$current_user = getCurrentUser();

// Helper functions
function getStatusBadge($status)
{
    $badges = [
        "published" =>
            '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Published</span>',
        "draft" =>
            '<span class="badge badge-warning"><i class="fas fa-edit"></i> Draft</span>',
        "pending" =>
            '<span class="badge badge-secondary"><i class="fas fa-clock"></i> Pending</span>',
    ];
    return $badges[$status] ??
        '<span class="badge badge-secondary">Unknown</span>';
}

function timeAgo($datetime)
{
    $time = time() - strtotime($datetime);
    if ($time < 60) {
        return "just now";
    }
    if ($time < 3600) {
        return floor($time / 60) . " min ago";
    }
    if ($time < 86400) {
        return floor($time / 3600) . " hours ago";
    }
    if ($time < 2592000) {
        return floor($time / 86400) . " days ago";
    }
    return date("M j, Y", strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts - Akanyenyeri Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin-layout.css">

    <style>
        /* Posts page specific styles */
        .posts-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .posts-title {
            flex: 1;
            min-width: 300px;
        }

        .posts-title h1 {
            margin: 0 0 1rem 0;
            color: #1a202c;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.875rem;
            font-weight: 700;
        }

        .posts-title h1 i {
            color: #3182ce;
            font-size: 1.5rem;
        }

        .post-stats {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .post-stat {
            color: #718096;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .post-stat:hover {
            color: #3182ce;
            border-color: #3182ce;
            background: rgba(49, 130, 206, 0.05);
        }

        .post-stat.active {
            color: #3182ce;
            background: rgba(49, 130, 206, 0.1);
            border-color: #3182ce;
            font-weight: 600;
        }

        .filters-section {
            background: #ffffff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .filters-form {
            display: grid;
            gap: 1rem;
        }

        .filters-row {
            display: grid;
            grid-template-columns: 1fr 2fr auto;
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
        }

        .filter-control {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            background: #ffffff;
            transition: border-color 0.2s ease;
        }

        .filter-control:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 0.75rem;
        }

        .bulk-actions {
            background: #ffffff;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .bulk-controls {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .bulk-select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        .posts-count {
            color: #718096;
            font-size: 0.875rem;
        }

        .posts-table-container {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .posts-table {
            width: 100%;
            border-collapse: collapse;
        }

        .posts-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.875rem;
            white-space: nowrap;
        }

        .posts-table td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }

        .posts-table tbody tr:hover {
            background: #f9fafb;
        }

        .posts-table tbody tr:last-child td {
            border-bottom: none;
        }

        .post-title-link {
            font-weight: 600;
            color: #1a202c;
            text-decoration: none;
            display: block;
            margin-bottom: 0.25rem;
        }

        .post-title-link:hover {
            color: #3182ce;
        }

        .post-meta {
            font-size: 0.8125rem;
            color: #6b7280;
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .category-badge {
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .featured-badge {
            color: #f59e0b;
            font-weight: 600;
        }

        .post-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .post-action-link {
            color: #3182ce;
            text-decoration: none;
            font-size: 0.8125rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .post-action-link:hover {
            background: rgba(49, 130, 206, 0.1);
        }

        .post-action-link.delete {
            color: #e53e3e;
        }

        .post-action-link.delete:hover {
            background: rgba(229, 62, 62, 0.1);
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-top: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .pagination-info {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .pagination-links {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .pagination-link {
            padding: 0.5rem 0.75rem;
            color: #374151;
            text-decoration: none;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .pagination-link:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }

        .pagination-link.current {
            background: #3182ce;
            color: white;
            border-color: #3182ce;
        }

        .no-posts {
            text-align: center;
            padding: 4rem 2rem;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .no-posts i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .no-posts h3 {
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .no-posts p {
            color: #6b7280;
        }

        .no-posts a {
            color: #3182ce;
            text-decoration: none;
            font-weight: 600;
        }

        .no-posts a:hover {
            text-decoration: underline;
        }

        /* Mobile responsiveness */
        @media (max-width: 1024px) {
            .posts-header {
                flex-direction: column;
                align-items: stretch;
            }

            .filters-row {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                justify-content: stretch;
            }

            .bulk-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }

        @media (max-width: 768px) {
            .posts-title h1 {
                font-size: 1.5rem;
            }

            .post-stats {
                flex-direction: column;
                gap: 0.5rem;
            }

            .post-stat {
                text-align: center;
            }

            .posts-table-container {
                overflow-x: auto;
            }

            .posts-table {
                min-width: 800px;
            }

            .posts-table th,
            .posts-table td {
                padding: 0.75rem 0.5rem;
            }

            .pagination {
                flex-direction: column;
                text-align: center;
            }

            .pagination-links {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .filters-section {
                padding: 1rem;
            }

            .bulk-actions {
                padding: 1rem;
            }

            .posts-table th,
            .posts-table td {
                padding: 0.5rem 0.25rem;
            }

            .post-actions {
                flex-direction: column;
                gap: 0.25rem;
            }
        }
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
                <!-- Posts Header -->
                <div class="posts-header">
                    <div class="posts-title">
                        <h1><i class="fas fa-file-alt"></i> Posts</h1>
                        <div class="post-stats">
                            <a href="?status=all" class="post-stat <?php echo $status_filter ===
                            "all"
                                ? "active"
                                : ""; ?>">
                                All (<?php echo $stats["all"]; ?>)
                            </a>
                            <a href="?status=published" class="post-stat <?php echo $status_filter ===
                            "published"
                                ? "active"
                                : ""; ?>">
                                Published (<?php echo $stats["published"]; ?>)
                            </a>
                            <a href="?status=draft" class="post-stat <?php echo $status_filter ===
                            "draft"
                                ? "active"
                                : ""; ?>">
                                Drafts (<?php echo $stats["draft"]; ?>)
                            </a>
                            <a href="?status=pending" class="post-stat <?php echo $status_filter ===
                            "pending"
                                ? "active"
                                : ""; ?>">
                                Pending (<?php echo $stats["pending"]; ?>)
                            </a>
                        </div>
                    </div>

                    <div class="page-actions">
                        <a href="post-new.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Post
                        </a>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(
                        $success_message,
                    ); ?>
                </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars(
                        $error_message,
                    ); ?>
                </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" class="filters-form">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars(
                            $status_filter,
                        ); ?>">

                        <div class="filters-row">
                            <div class="filter-group">
                                <label for="category">Category</label>
                                <select id="category" name="category" class="filter-control">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category[
                                        "id"
                                    ]; ?>" <?php echo $category_filter ==
$category["id"]
    ? "selected"
    : ""; ?>>
                                        <?php echo htmlspecialchars(
                                            $category["name"],
                                        ); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="search">Search Posts</label>
                                <input type="text" id="search" name="search" class="filter-control"
                                       placeholder="Search by title or content..."
                                       value="<?php echo htmlspecialchars(
                                           $search_query,
                                       ); ?>">
                            </div>

                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="posts.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if (!empty($posts)): ?>
                <!-- Bulk Actions -->
                <form method="POST" id="bulkForm">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">

                    <div class="bulk-actions">
                        <div class="bulk-controls">
                            <input type="checkbox" id="select-all" class="post-checkbox">
                            <label for="select-all" style="margin-left: 0.5rem; font-weight: 500;">Select All</label>

                            <select name="bulk_action" class="bulk-select">
                                <option value="">Bulk Actions</option>
                                <option value="publish">Publish</option>
                                <option value="draft">Move to Draft</option>
                                <?php if (canDo("delete_posts")): ?>
                                <option value="delete">Delete</option>
                                <?php endif; ?>
                            </select>

                            <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to perform this action?')">
                                Apply
                            </button>
                        </div>

                        <div class="posts-count">
                            <?php echo $total_posts; ?> posts total
                        </div>
                    </div>

                    <!-- Posts Table -->
                    <div class="posts-table-container">
                        <table class="posts-table">
                            <thead>
                                <tr>
                                    <th width="40"><input type="checkbox" id="select-all-header" class="post-checkbox"></th>
                                    <th>Title</th>
                                    <th width="120">Author</th>
                                    <th width="120">Category</th>
                                    <th width="100">Status</th>
                                    <th width="140">Date</th>
                                    <th width="80">Views</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="post_ids[]" value="<?php echo $post[
                                            "id"
                                        ]; ?>" class="post-checkbox post-select">
                                    </td>
                                    <td>
                                        <a href="post-edit.php?id=<?php echo $post[
                                            "id"
                                        ]; ?>" class="post-title-link">
                                            <?php echo htmlspecialchars(
                                                $post["title"],
                                            ); ?>
                                        </a>
                                        <div class="post-meta">
                                            <?php if (
                                                $post["featured"] ?? false
                                            ): ?>
                                                <span class="featured-badge"><i class="fas fa-star"></i> Featured</span>
                                            <?php endif; ?>
                                            ID: <?php echo $post["id"]; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(
                                            $post["author_name"] ?? "Unknown",
                                        ); ?>
                                    </td>
                                    <td>
                                        <?php if ($post["category_name"]): ?>
                                        <span class="category-badge" style="background-color: <?php echo htmlspecialchars(
                                            $post["category_color"] ??
                                                "#3182ce",
                                        ); ?>">
                                            <?php echo htmlspecialchars(
                                                $post["category_name"],
                                            ); ?>
                                        </span>
                                        <?php else: ?>
                                        <span style="color: #9ca3af;">Uncategorized</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo getStatusBadge(
                                            $post["status"],
                                        ); ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo timeAgo(
                                            $post["created_at"],
                                        ); ?></div>
                                        <div class="post-meta"><?php echo date(
                                            "M j, Y",
                                            strtotime($post["created_at"]),
                                        ); ?></div>
                                    </td>
                                    <td>
                                        <?php echo number_format(
                                            $post["views"] ?? 0,
                                        ); ?>
                                    </td>
                                    <td>
                                        <div class="post-actions">
                                            <a href="post-edit.php?id=<?php echo $post[
                                                "id"
                                            ]; ?>" class="post-action-link">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>

                                            <?php if (
                                                $post["status"] === "draft"
                                            ): ?>
                                            <a href="?action=publish&id=<?php echo $post[
                                                "id"
                                            ]; ?>" class="post-action-link">
                                                <i class="fas fa-upload"></i> Publish
                                            </a>
                                            <?php elseif (
                                                $post["status"] === "published"
                                            ): ?>
                                            <a href="?action=draft&id=<?php echo $post[
                                                "id"
                                            ]; ?>" class="post-action-link">
                                                <i class="fas fa-archive"></i> Draft
                                            </a>
                                            <?php endif; ?>

                                            <a href="../single.php?slug=<?php echo $post[
                                                "slug"
                                            ]; ?>" class="post-action-link" target="_blank">
                                                <i class="fas fa-external-link-alt"></i> View
                                            </a>

                                            <?php if (canDo("delete_posts")): ?>
                                            <a href="?action=delete&id=<?php echo $post[
                                                "id"
                                            ]; ?>" class="post-action-link delete"
                                               onclick="return confirm('Are you sure you want to delete this post?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <div class="pagination-info">
                        Showing <?php echo ($page - 1) * $per_page +
                            1; ?> to <?php echo min(
     $page * $per_page,
     $total_posts,
 ); ?> of <?php echo $total_posts; ?> posts
                    </div>

                    <div class="pagination-links">
                        <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(
                            array_merge($_GET, ["page" => $page - 1]),
                        ); ?>" class="pagination-link">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?<?php echo http_build_query(
                            array_merge($_GET, ["page" => $i]),
                        ); ?>"
                           class="pagination-link <?php echo $i === $page
                               ? "current"
                               : ""; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor;
                        ?>

                        <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(
                            array_merge($_GET, ["page" => $page + 1]),
                        ); ?>" class="pagination-link">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <!-- No Posts Found -->
                <div class="no-posts">
                    <i class="fas fa-file-alt"></i>
                    <h3>No posts found</h3>
                    <p>
                        <?php if (
                            !empty($search_query) ||
                            !empty($category_filter) ||
                            $status_filter !== "all"
                        ): ?>
                            No posts match your current filters. <a href="posts.php">Clear filters</a> to see all posts.
                        <?php else: ?>
                            You haven't created any posts yet. <a href="post-new.php">Create your first post</a>!
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar overlay for mobile -->
    <div class="sidebar-overlay" onclick="toggleMobileSidebar()"></div>

    <script>
        // Mobile sidebar toggle
        function toggleMobileSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            if (mobileToggle) {
                mobileToggle.addEventListener('click', toggleMobileSidebar);
            }

            // Select all functionality
            const selectAllCheckboxes = document.querySelectorAll('#select-all, #select-all-header');
            const postCheckboxes = document.querySelectorAll('.post-select');
            const bulkForm = document.getElementById('bulkForm');

            selectAllCheckboxes.forEach(selectAll => {
                selectAll.addEventListener('change', function() {
                    postCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    updateSelectAllState();
                });
            });

            postCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateSelectAllState();
                });
            });

            function updateSelectAllState() {
                const allChecked = Array.from(postCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(postCheckboxes).some(cb => cb.checked);

                selectAllCheckboxes.forEach(selectAll => {
                    selectAll.checked = allChecked;
                    selectAll.indeterminate = someChecked && !allChecked;
                });
            }

            // Bulk form submission
            if (bulkForm) {
                bulkForm.addEventListener('submit', function(e) {
                    const selectedPosts = Array.from(postCheckboxes).filter(cb => cb.checked);
                    const bulkAction = this.querySelector('select[name="bulk_action"]').value;

                    if (selectedPosts.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one post.');
                        return;
                    }

                    if (!bulkAction) {
                        e.preventDefault();
                        alert('Please select a bulk action.');
                        return;
                    }

                    if (bulkAction === 'delete') {
                        const confirmMessage = `Are you sure you want to delete ${selectedPosts.length} post(s)? This action cannot be undone.`;
                        if (!confirm(confirmMessage)) {
                            e.preventDefault();
                            return;
                        }
                    }
                });
            }

            // Auto-submit category filter
            const categoryFilter = document.querySelector('select[name="category"]');
            if (categoryFilter) {
                categoryFilter.addEventListener('change', function() {
                    this.form.submit();
                });
            }

            // Search with debounce
            const searchInput = document.querySelector('input[name="search"]');
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

            // Table responsive behavior
            function handleTableResponsive() {
                const table = document.querySelector('.posts-table');
                const container = document.querySelector('.posts-table-container');

                if (table && container) {
                    if (window.innerWidth <= 768) {
                        container.style.overflowX = 'auto';
                    } else {
                        container.style.overflowX = 'visible';
                    }
                }
            }

            window.addEventListener('resize', handleTableResponsive);
            handleTableResponsive();
        });
    </script>
</body>
</html>
