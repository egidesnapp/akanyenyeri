<?php
/**
 * Tags Management - Akanyenyeri Magazine Admin
 * Functional PHP page for managing tags with database integration
 */

session_start();
require_once "php/auth_check.php";
require_once "../config/database.php";

// Require authentication
requireAuth();

// Get database connection
$pdo = getDB();

// Handle form submissions
$success_message = "";
$error_message = "";

// Add new tag
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_tag"])) {
    try {
        // Validate CSRF token
        if (!validateCSRF($_POST["csrf_token"] ?? "")) {
            throw new Exception("Invalid security token");
        }

        $name = trim($_POST["name"] ?? "");

        if (empty($name)) {
            throw new Exception("Tag name is required");
        }

        if (strlen($name) > 50) {
            throw new Exception("Tag name cannot exceed 50 characters");
        }

        // Generate slug
        $slug = strtolower(
            trim(preg_replace("/[^A-Za-z0-9-]+/", "-", $name), "-"),
        );

        // Check if slug already exists
        $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug .= "-" . time();
        }

        // Insert tag
        $stmt = $pdo->prepare("
            INSERT INTO tags (name, slug, created_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$name, $slug]);

        $success_message = "Tag '{$name}' added successfully!";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Update tag
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_tag"])) {
    try {
        // Validate CSRF token
        if (!validateCSRF($_POST["csrf_token"] ?? "")) {
            throw new Exception("Invalid security token");
        }

        $tag_id = intval($_POST["tag_id"]);
        $name = trim($_POST["name"] ?? "");

        if (empty($name)) {
            throw new Exception("Tag name is required");
        }

        if (strlen($name) > 50) {
            throw new Exception("Tag name cannot exceed 50 characters");
        }

        // Generate slug
        $slug = strtolower(
            trim(preg_replace("/[^A-Za-z0-9-]+/", "-", $name), "-"),
        );

        // Check if slug already exists (excluding current tag)
        $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $tag_id]);
        if ($stmt->fetch()) {
            $slug .= "-" . time();
        }

        // Update tag
        $stmt = $pdo->prepare("
            UPDATE tags
            SET name = ?, slug = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $slug, $tag_id]);

        $success_message = "Tag '{$name}' updated successfully!";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Delete tag
if (
    isset($_GET["action"]) &&
    $_GET["action"] === "delete" &&
    isset($_GET["id"])
) {
    try {
        if (!canDo("manage_categories")) {
            throw new Exception("You do not have permission to delete tags");
        }

        $tag_id = intval($_GET["id"]);

        // Get tag name for logging
        $stmt = $pdo->prepare("SELECT name FROM tags WHERE id = ?");
        $stmt->execute([$tag_id]);
        $tag_name = $stmt->fetchColumn();

        if (!$tag_name) {
            throw new Exception("Tag not found");
        }

        // Check if tag has posts
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM post_tags WHERE tag_id = ?",
        );
        $stmt->execute([$tag_id]);
        $post_count = $stmt->fetchColumn();

        // Delete tag relationships first
        if ($post_count > 0) {
            $stmt = $pdo->prepare("DELETE FROM post_tags WHERE tag_id = ?");
            $stmt->execute([$tag_id]);
        }

        // Delete tag
        $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
        $stmt->execute([$tag_id]);

        $success_message = "Tag '{$tag_name}' deleted successfully!";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get search parameters
$search_query = trim($_GET["search"] ?? "");
$per_page = 24;
$page = max(1, intval($_GET["page"] ?? 1));
$offset = ($page - 1) * $per_page;

// Build WHERE clause for search
$where_conditions = [];
$params = [];

if (!empty($search_query)) {
    $where_conditions[] = "t.name LIKE ?";
    $params[] = "%{$search_query}%";
}

$where_clause = !empty($where_conditions)
    ? "WHERE " . implode(" AND ", $where_conditions)
    : "";

// Get tags with post counts
try {
    $tags_query = "
        SELECT t.*, COUNT(pt.post_id) as post_count
        FROM tags t
        LEFT JOIN post_tags pt ON t.id = pt.tag_id
        $where_clause
        GROUP BY t.id
        ORDER BY t.name ASC
        LIMIT $per_page OFFSET $offset
    ";

    $stmt = $pdo->prepare($tags_query);
    $stmt->execute($params);
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $count_query = "
        SELECT COUNT(DISTINCT t.id) as total
        FROM tags t
        $where_clause
    ";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_tags = $stmt->fetch(PDO::FETCH_ASSOC)["total"];

    $total_pages = ceil($total_tags / $per_page);
} catch (Exception $e) {
    $error_message = "Error loading tags: " . $e->getMessage();
    $tags = [];
    $total_tags = 0;
    $total_pages = 0;
}

// Get tag for editing
$edit_tag = null;
if (isset($_GET["edit"]) && is_numeric($_GET["edit"])) {
    $edit_id = intval($_GET["edit"]);
    $stmt = $pdo->prepare("SELECT * FROM tags WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_tag = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get tag statistics
$stats_query = "
    SELECT
        COUNT(t.id) as total_tags,
        COUNT(pt.tag_id) as total_usage,
        COUNT(DISTINCT pt.post_id) as tagged_posts
    FROM tags t
    LEFT JOIN post_tags pt ON t.id = pt.tag_id
";
$stats = $pdo->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Get current user info
$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tags - Akanyenyeri Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin-layout.css">

    <style>
        /* Tags specific styles */
        .tags-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
            align-items: start;
        }

        .tags-card {
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }

        .tags-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .tags-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #2d3748;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tags-card-body {
            padding: 0;
        }

        .search-section {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .search-form {
            display: flex;
            gap: 0.75rem;
            align-items: end;
        }

        .search-group {
            flex: 1;
        }

        .search-input {
            width: 100%;
        }

        .tags-grid {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 0.75rem;
            min-height: 200px;
        }

        .tag-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            position: relative;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .tag-item:hover {
            background: #f7fafc;
            border-color: #3182ce;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .tag-content {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
            min-width: 0;
        }

        .tag-name {
            color: #2d3748;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tag-count {
            background: #3182ce;
            color: white;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            min-width: 20px;
            text-align: center;
        }

        .tag-actions {
            display: none;
            gap: 0.25rem;
            margin-left: 0.5rem;
        }

        .tag-item:hover .tag-actions {
            display: flex;
        }

        .tag-action {
            background: none;
            border: none;
            color: #718096;
            cursor: pointer;
            padding: 0.25rem;
            font-size: 0.875rem;
            border-radius: 4px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tag-action:hover {
            background: rgba(49, 130, 206, 0.1);
            color: #3182ce;
        }

        .tag-action.delete:hover {
            background: rgba(229, 62, 62, 0.1);
            color: #e53e3e;
        }

        .tag-form {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            position: sticky;
            top: 80px;
            overflow: hidden;
        }

        .form-section {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .form-section-title {
            margin: 0 0 1rem 0;
            color: #2d3748;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tag-suggestions {
            background: #f8fafc;
            border-radius: 6px;
            padding: 1rem;
        }

        .suggestion-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .suggestion-tag {
            background: #e6fffa;
            color: #234e52;
            border: 1px solid #b2f5ea;
            padding: 0.25rem 0.75rem;
            border-radius: 16px;
            font-size: 0.8125rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .suggestion-tag:hover {
            background: #b2f5ea;
            transform: translateY(-1px);
        }

        .no-tags {
            text-align: center;
            padding: 4rem 2rem;
            color: #718096;
        }

        .no-tags i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #cbd5e0;
        }

        .no-tags h3 {
            margin-bottom: 0.5rem;
            color: #4a5568;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .pagination-info {
            color: #718096;
            font-size: 0.875rem;
        }

        .pagination-links {
            display: flex;
            gap: 0.5rem;
        }

        .pagination-link {
            padding: 0.5rem 0.75rem;
            text-decoration: none;
            color: #3182ce;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .pagination-link:hover {
            background: rgba(49, 130, 206, 0.1);
            text-decoration: none;
        }

        .pagination-link.current {
            background: #3182ce;
            color: white;
        }

        .tag-stats {
            background: #f8fafc;
            border-radius: 6px;
            padding: 1rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #4a5568;
        }

        .stat-value {
            font-weight: 600;
            color: #2d3748;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .tags-layout {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .tag-form {
                position: static;
                order: 1;
            }

            .tags-card {
                order: 2;
            }
        }

        @media (max-width: 768px) {
            .tags-layout {
                gap: 1rem;
            }

            .search-form {
                flex-direction: column;
                gap: 0.5rem;
            }

            .tags-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 0.5rem;
                padding: 1rem;
            }

            .tag-item {
                padding: 0.5rem 0.75rem;
            }
        }

        @media (max-width: 480px) {
            .tags-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.5rem;
            }

            .tag-item {
                font-size: 0.8125rem;
                padding: 0.5rem;
            }

            .form-section {
                padding: 1rem;
            }

            .no-tags {
                padding: 2rem 1rem;
            }

            .no-tags i {
                font-size: 3rem;
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
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-tags"></i> Tags
                    </h1>
                    <div class="page-actions">
                        <a href="posts.php" class="btn btn-secondary">
                            <i class="fas fa-file-alt"></i> View Posts
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

                <div class="tags-layout">
                    <!-- Tags List -->
                    <div class="tags-card">
                        <div class="tags-card-header">
                            <h2 class="tags-card-title">
                                <i class="fas fa-tags"></i> All Tags
                            </h2>
                            <span class="badge badge-primary"><?php echo number_format(
                                $total_tags,
                            ); ?></span>
                        </div>
                        <div class="tags-card-body">
                        <!-- Search -->
                        <div class="search-section">
                            <form method="GET" class="search-form">
                                <div class="search-group">
                                    <label for="search" class="form-label">Search Tags</label>
                                    <input type="text" name="search" id="search" class="form-input search-input"
                                           placeholder="Search tags by name..."
                                           value="<?php echo htmlspecialchars(
                                               $search_query,
                                           ); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if ($search_query): ?>
                                <a href="tags.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                                <?php endif; ?>
                            </form>
                        </div>

                        <!-- Tags Grid -->
                        <?php if (!empty($tags)): ?>
                        <div class="tags-grid">
                            <?php foreach ($tags as $tag): ?>
                            <div class="tag-item">
                                <div class="tag-content">
                                    <span class="tag-name"><?php echo htmlspecialchars(
                                        $tag["name"],
                                    ); ?></span>
                                    <?php if ($tag["post_count"] > 0): ?>
                                    <span class="tag-count"><?php echo $tag[
                                        "post_count"
                                    ]; ?></span>
                                    <?php else: ?>
                                    <span class="tag-count" style="background: #9ca3af;">0</span>
                                    <?php endif; ?>
                                </div>

                                <div class="tag-actions">
                                    <button class="tag-action edit" onclick="editTag(<?php echo $tag[
                                        "id"
                                    ]; ?>, '<?php echo htmlspecialchars(
    $tag["name"],
); ?>')" title="Edit tag">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <?php if (canDo("manage_categories")): ?>
                                    <button class="tag-action delete" onclick="deleteTag(<?php echo $tag[
                                        "id"
                                    ]; ?>, '<?php echo htmlspecialchars(
    $tag["name"],
); ?>')" title="Delete tag">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <div class="pagination-info">
                                Showing <?php echo ($page - 1) * $per_page +
                                    1; ?> to <?php echo min(
     $page * $per_page,
     $total_tags,
 ); ?> of <?php echo $total_tags; ?> tags
                            </div>

                            <div class="pagination-links">
                                <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(
                                    array_merge($_GET, ["page" => $page - 1]),
                                ); ?>" class="pagination-link">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                                <?php endif; ?>

                                <?php for (
                                    $i = max(1, $page - 2);
                                    $i <= min($total_pages, $page + 2);
                                    $i++
                                ): ?>
                                <a href="?<?php echo http_build_query(
                                    array_merge($_GET, ["page" => $i]),
                                ); ?>"
                                   class="pagination-link <?php echo $i ===
                                   $page
                                       ? "current"
                                       : ""; ?>">
                                    <?php echo $i; ?>
                                </a>
                                <?php endfor; ?>

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
                            <div class="no-tags">
                                <i class="fas fa-tag"></i>
                                <h3>No tags yet</h3>
                                <p>
                                    <?php if ($search_query): ?>
                                    No tags match your search "<strong><?php echo htmlspecialchars(
                                        $search_query,
                                    ); ?></strong>".
                                    <a href="tags.php" style="color: #3182ce;">Clear search</a> to see all tags.
                                    <?php else: ?>
                                    Create your first tag using the form to organize your content.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Add/Edit Tag Form -->
                    <div class="tag-form">
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <i class="fas fa-<?php echo $edit_tag
                                    ? "edit"
                                    : "plus"; ?>"></i>
                                <?php echo $edit_tag
                                    ? "Edit Tag"
                                    : "Add New Tag"; ?>
                            </h3>

                            <form method="POST" id="tagForm">
                                <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                                <?php if ($edit_tag): ?>
                                <input type="hidden" name="tag_id" value="<?php echo $edit_tag[
                                    "id"
                                ]; ?>">
                                <?php endif; ?>

                                <div class="form-group">
                                    <label for="name" class="form-label">Tag Name *</label>
                                    <input type="text" id="name" name="name" class="form-input" required
                                           placeholder="Enter tag name (max 50 characters)"
                                           maxlength="50"
                                           value="<?php echo htmlspecialchars(
                                               $edit_tag["name"] ?? "",
                                           ); ?>">
                                </div>

                                <div class="form-group" style="margin: 0;">
                                    <?php if ($edit_tag): ?>
                                    <button type="submit" name="update_tag" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Tag
                                    </button>
                                    <a href="tags.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <?php else: ?>
                                    <button type="submit" name="add_tag" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add Tag
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <?php if (!empty($tags)): ?>
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <i class="fas fa-chart-bar"></i> Tag Statistics
                            </h3>
                            <div class="tag-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Total Tags</span>
                                    <span class="stat-value"><?php echo number_format(
                                        $total_tags,
                                    ); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Used Tags</span>
                                    <span class="stat-value"><?php echo count(
                                        array_filter($tags, function ($t) {
                                            return $t["post_count"] > 0;
                                        }),
                                    ); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Unused Tags</span>
                                    <span class="stat-value"><?php echo count(
                                        array_filter($tags, function ($t) {
                                            return $t["post_count"] == 0;
                                        }),
                                    ); ?></span>
                                </div>
                                <?php
                                $most_used = array_reduce(
                                    $tags,
                                    function ($max, $tag) {
                                        return $tag["post_count"] >
                                            ($max["post_count"] ?? 0)
                                            ? $tag
                                            : $max;
                                    },
                                    ["post_count" => 0],
                                );
                                if ($most_used["post_count"] > 0): ?>
                                <div class="stat-item">
                                    <span class="stat-label">Most Popular</span>
                                    <span class="stat-value"><?php echo htmlspecialchars(
                                        $most_used["name"],
                                    ); ?> (<?php echo $most_used[
     "post_count"
 ]; ?>)</span>
                                </div>
                                <?php endif;
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="form-section">
                            <h3 class="form-section-title">
                                <i class="fas fa-lightbulb"></i> Popular Tags
                            </h3>
                            <div class="tag-suggestions">
                                <p style="font-size: 0.875rem; color: #718096; margin-bottom: 0.75rem;">
                                    Quick add popular tags:
                                </p>
                                <div class="suggestion-tags">
                                    <button type="button" class="suggestion-tag" onclick="addSuggestion('Technology')">Technology</button>
                                    <button type="button" class="suggestion-tag" onclick="addSuggestion('Business')">Business</button>
                                    <button type="button" class="suggestion-tag" onclick="addSuggestion('Sports')">Sports</button>
                                    <button type="button" class="suggestion-tag" onclick="addSuggestion('Entertainment')">Entertainment</button>
                                    <button type="button" class="suggestion-tag" onclick="addSuggestion('Health')">Health</button>
                                    <button type="button" class="suggestion-tag" onclick="addSuggestion('Travel')">Travel</button>
                                    <button type="button" class="suggestion-tag" onclick="addSuggestion('Food')">Food</button>
                                    <button type="button" class="suggestion-tag" onclick="addSuggestion('Fashion')">Fashion</button>
                                    <button type="button" class="suggestion-tag" onclick="addSuggestion('Education')">Education</button>
                                    <button type="button" class="suggestion-tag" onclick="addSuggestion('Politics')">Politics</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editTag(id, name) {
            // Smooth scroll to form
            document.querySelector('.tag-form').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

            // Small delay to ensure scroll completes
            setTimeout(() => {
                window.location.href = `tags.php?edit=${id}`;
            }, 300);
        }

        function deleteTag(id, name) {
            if (confirm(`Are you sure you want to delete the tag "${name}"?\n\nThis will remove it from all posts that use this tag.`)) {
                // Show loading state
                showAlert('Deleting tag...', 'info');
                window.location.href = `tags.php?action=delete&id=${id}`;
            }
        }

        function addSuggestion(tagName) {
            const nameInput = document.getElementById('name');
            if (nameInput) {
                nameInput.value = tagName;
                nameInput.focus();

                // Add visual feedback
                nameInput.style.backgroundColor = '#f0f9ff';
                setTimeout(() => {
                    nameInput.style.backgroundColor = '';
                }, 500);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced form validation
            const tagForm = document.getElementById('tagForm');
            const nameInput = document.getElementById('name');

            if (tagForm && nameInput) {
                // Real-time validation
                nameInput.addEventListener('input', function() {
                    const value = this.value.trim();
                    const charCount = value.length;

                    // Remove existing feedback
                    const existingFeedback = this.parentNode.querySelector('.validation-feedback');
                    if (existingFeedback) {
                        existingFeedback.remove();
                    }

                    // Create feedback element
                    const feedback = document.createElement('div');
                    feedback.className = 'validation-feedback';
                    feedback.style.cssText = 'font-size: 0.875rem; margin-top: 0.25rem; display: flex; justify-content: space-between;';

                    let message = '';
                    let isValid = true;

                    if (charCount === 0) {
                        message = 'Tag name is required';
                        isValid = false;
                    } else if (charCount < 2) {
                        message = 'Minimum 2 characters';
                        isValid = false;
                    } else if (charCount > 50) {
                        message = 'Maximum 50 characters exceeded';
                        isValid = false;
                    } else {
                        message = 'Looks good!';
                        isValid = true;
                    }

                    feedback.innerHTML = `
                        <span style="color: ${isValid ? '#38a169' : '#e53e3e'}">${message}</span>
                        <span style="color: #718096">${charCount}/50</span>
                    `;

                    this.style.borderColor = isValid ? '#38a169' : '#e53e3e';
                    this.parentNode.appendChild(feedback);
                });

                // Form submission with enhanced validation
                tagForm.addEventListener('submit', function(e) {
                    const name = nameInput.value.trim();

                    if (!name) {
                        showAlert('Please enter a tag name', 'error');
                        nameInput.focus();
                        e.preventDefault();
                        return;
                    }

                    if (name.length < 2) {
                        showAlert('Tag name must be at least 2 characters long', 'error');
                        nameInput.focus();
                        e.preventDefault();
                        return;
                    }

                    if (name.length > 50) {
                        showAlert('Tag name cannot exceed 50 characters', 'error');
                        nameInput.focus();
                        e.preventDefault();
                        return;
                    }

                    // Check for existing tags (case-insensitive)
                    const existingTags = Array.from(document.querySelectorAll('.tag-name'))
                        .map(el => el.textContent.toLowerCase());

                    const editId = document.querySelector('input[name="tag_id"]')?.value;
                    const isEditing = Boolean(editId);

                    if (!isEditing && existingTags.includes(name.toLowerCase())) {
                        showAlert('A tag with this name already exists', 'error');
                        nameInput.focus();
                        e.preventDefault();
                        return;
                    }

                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                        // Re-enable after timeout (in case of network issues)
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }, 10000);
                    }
                });
            }

            // Enhanced search with debouncing
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                let searchTimeout;

                // Add search icon animation
                const searchBtn = document.querySelector('button[type="submit"]');

                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);

                    // Show searching state
                    if (searchBtn) {
                        searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    }

                    searchTimeout = setTimeout(() => {
                        if (this.value.length >= 2 || this.value.length === 0) {
                            this.form.submit();
                        } else {
                            // Reset button
                            if (searchBtn) {
                                searchBtn.innerHTML = '<i class="fas fa-search"></i>';
                            }
                        }
                    }, 800);
                });

                // Clear search on Escape
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && this.value) {
                        this.value = '';
                        this.form.submit();
                    }
                });
            }

            // Auto-hide alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'all 0.3s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Focus search with Ctrl+F or /
                if ((e.ctrlKey && e.key === 'f') || e.key === '/') {
                    e.preventDefault();
                    searchInput?.focus();
                }

                // Focus add tag form with Ctrl+N
                if (e.ctrlKey && e.key === 'n' && !document.querySelector('input[name="tag_id"]')) {
                    e.preventDefault();
                    nameInput?.focus();
                }

                // Cancel edit with Escape
                if (e.key === 'Escape' && document.querySelector('input[name="tag_id"]')) {
                    window.location.href = 'tags.php';
                }
            });

            // Smooth scroll to form when editing
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('edit')) {
                setTimeout(() => {
                    document.querySelector('.tag-form').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 100);
            }

            // Tag suggestion animations
            const suggestionTags = document.querySelectorAll('.suggestion-tag');
            suggestionTags.forEach(tag => {
                tag.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1.05)';
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 100);
                    }, 100);
                });
            });

            // Utility function to show alerts
            function showAlert(message, type = 'info') {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type}`;
                alertDiv.style.opacity = '0';
                alertDiv.style.transform = 'translateY(-10px)';
                alertDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i> ${message}`;

                const contentArea = document.querySelector('.content-area');
                contentArea.insertBefore(alertDiv, contentArea.children[1]);

                // Animate in
                setTimeout(() => {
                    alertDiv.style.transition = 'all 0.3s ease';
                    alertDiv.style.opacity = '1';
                    alertDiv.style.transform = 'translateY(0)';
                }, 10);

                // Auto-remove after delay
                setTimeout(() => {
                    alertDiv.style.opacity = '0';
                    alertDiv.style.transform = 'translateY(-10px)';
                    setTimeout(() => alertDiv.remove(), 300);
                }, type === 'error' ? 8000 : 5000);
            }

            // Make showAlert globally available
            window.showAlert = showAlert;
        });
    </script>
</body>
</html>
