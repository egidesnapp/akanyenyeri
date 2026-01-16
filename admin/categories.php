<?php
/**
 * Categories Management - Akanyenyeri Magazine Admin
 * Functional PHP page for managing categories with database integration
 */

session_start();
require_once "php/auth_check.php";
require_once "../database/config/database.php";

// Require authentication
requireAuth();

// Get database connection
$pdo = getDB();

// Handle form submissions
$success_message = "";
$error_message = "";

// Add new category
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_category"])) {
    try {
        // Validate CSRF token
        if (!validateCSRF($_POST["csrf_token"] ?? "")) {
            throw new Exception("Invalid security token");
        }

        $name = trim($_POST["name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $color = trim($_POST["color"] ?? "#2271b1");

        if (empty($name)) {
            throw new Exception("Category name is required");
        }

        // Generate slug
        $slug = strtolower(
            trim(preg_replace("/[^A-Za-z0-9-]+/", "-", $name), "-"),
        );

        // Check if slug already exists
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug .= "-" . time();
        }

        // Insert category
        $stmt = $pdo->prepare("
            INSERT INTO categories (name, slug, description, color, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$name, $slug, $description, $color]);

        $success_message = "Category '{$name}' added successfully!";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Update category
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_category"])) {
    try {
        // Validate CSRF token
        if (!validateCSRF($_POST["csrf_token"] ?? "")) {
            throw new Exception("Invalid security token");
        }

        $category_id = intval($_POST["category_id"]);
        $name = trim($_POST["name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $color = trim($_POST["color"] ?? "#2271b1");

        if (empty($name)) {
            throw new Exception("Category name is required");
        }

        // Generate slug
        $slug = strtolower(
            trim(preg_replace("/[^A-Za-z0-9-]+/", "-", $name), "-"),
        );

        // Check if slug already exists (excluding current category)
        $stmt = $pdo->prepare(
            "SELECT id FROM categories WHERE slug = ? AND id != ?",
        );
        $stmt->execute([$slug, $category_id]);
        if ($stmt->fetch()) {
            $slug .= "-" . time();
        }

        // Update category
        $stmt = $pdo->prepare("
            UPDATE categories
            SET name = ?, slug = ?, description = ?, color = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $slug, $description, $color, $category_id]);

        $success_message = "Category '{$name}' updated successfully!";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Delete category
if (
    isset($_GET["action"]) &&
    $_GET["action"] === "delete" &&
    isset($_GET["id"])
) {
    try {
        if (!canDo("manage_categories")) {
            throw new Exception(
                "You do not have permission to delete categories",
            );
        }

        $category_id = intval($_GET["id"]);

        // Check if category has posts
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM posts WHERE category_id = ?",
        );
        $stmt->execute([$category_id]);
        $post_count = $stmt->fetchColumn();

        if ($post_count > 0) {
            throw new Exception(
                "Cannot delete category with {$post_count} posts. Please reassign posts first.",
            );
        }

        // Delete category
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);

        $success_message = "Category deleted successfully!";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get categories with post counts
try {
    $categories_query = "
        SELECT c.*, COUNT(p.id) as post_count
        FROM categories c
        LEFT JOIN posts p ON c.id = p.category_id
        GROUP BY c.id
        ORDER BY c.name ASC
    ";

    $stmt = $pdo->query($categories_query);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "Error loading categories: " . $e->getMessage();
    $categories = [];
}

// Get category for editing
$edit_category = null;
if (isset($_GET["edit"]) && is_numeric($_GET["edit"])) {
    $edit_id = intval($_GET["edit"]);
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_category = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get current user info
$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Akanyenyeri Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin-layout.css">

    <style>
        /* Categories specific styles */
        .categories-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            align-items: start;
        }

        .categories-card {
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }

        .categories-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .categories-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #2d3748;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .categories-card-body {
            padding: 0;
        }

        .categories-table {
            width: 100%;
            border-collapse: collapse;
        }

        .categories-table th {
            background: #f8fafc;
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .categories-table td {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }

        .categories-table tbody tr:hover {
            background-color: #f7fafc;
        }

        .categories-table tbody tr:last-child td {
            border-bottom: none;
        }

        .category-name {
            font-weight: 600;
            color: #3182ce;
            text-decoration: none;
            display: block;
            margin-bottom: 0.25rem;
            transition: color 0.2s ease;
        }

        .category-name:hover {
            color: #2c5aa0;
            text-decoration: underline;
        }

        .category-slug {
            font-size: 0.75rem;
            color: #718096;
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
            background: #f7fafc;
            padding: 0.125rem 0.375rem;
            border-radius: 4px;
            display: inline-block;
        }

        .category-color {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            display: inline-block;
            border: 2px solid #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1), inset 0 1px 0 rgba(255,255,255,0.1);
        }

        .category-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .action-link {
            color: #3182ce;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .action-link:hover {
            background: rgba(49, 130, 206, 0.1);
            text-decoration: none;
        }

        .action-link.delete {
            color: #e53e3e;
        }

        .action-link.delete:hover {
            background: rgba(229, 62, 62, 0.1);
        }

        .category-form {
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

        .color-picker-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .color-preview {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .color-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .color-input {
            width: 60px;
            height: 40px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
        }

        .color-text-input {
            width: 100px;
            font-family: 'SF Mono', 'Monaco', monospace;
            font-size: 0.875rem;
        }

        .popular-colors {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .color-option {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            cursor: pointer;
            border: 2px solid #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }

        .color-option:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .no-categories {
            text-align: center;
            padding: 4rem 2rem;
            color: #718096;
        }

        .no-categories i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #cbd5e0;
        }

        .no-categories h3 {
            margin-bottom: 0.5rem;
            color: #4a5568;
        }

        .category-stats {
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
            .categories-layout {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .category-form {
                position: static;
                order: 1;
            }

            .categories-card {
                order: 2;
            }
        }

        @media (max-width: 768px) {
            .categories-layout {
                gap: 1rem;
            }

            .categories-table {
                font-size: 0.875rem;
            }

            .categories-table th,
            .categories-table td {
                padding: 0.5rem;
            }

            .category-actions {
                flex-direction: column;
                gap: 0.25rem;
            }

            .color-picker-group {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }

            .popular-colors {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 480px) {
            .categories-table th,
            .categories-table td {
                padding: 0.375rem;
                font-size: 0.8125rem;
            }

            .category-name {
                font-size: 0.875rem;
            }

            .form-section {
                padding: 1rem;
            }

            .no-categories {
                padding: 2rem 1rem;
            }

            .no-categories i {
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
                        <i class="fas fa-folder"></i> Categories
                    </h1>
                    <div class="page-actions">
                        <a href="posts.php" class="btn btn-secondary">
                            <i class="fas fa-file-alt"></i> View Posts
                        </a>
                    </div>
                </div>

                <!-- Success/Error Messages -->
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

                <div class="categories-layout">
                    <!-- Categories List -->
                    <div class="categories-card">
                        <div class="categories-card-header">
                            <h2 class="categories-card-title">
                                <i class="fas fa-list"></i> All Categories
                            </h2>
                            <span class="badge badge-primary"><?php echo count(
                                $categories,
                            ); ?></span>
                        </div>
                        <div class="categories-card-body">
                            <?php if (!empty($categories)): ?>
                            <table class="categories-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Color</th>
                                        <th>Posts</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <div class="category-name">
                                                <?php echo htmlspecialchars(
                                                    $category["name"],
                                                ); ?>
                                            </div>
                                            <div class="category-slug">
                                                <?php echo htmlspecialchars(
                                                    $category["slug"],
                                                ); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo $category["description"]
                                                ? htmlspecialchars(
                                                    $category["description"],
                                                )
                                                : '<span style="color: #9ca3af;">No description</span>'; ?>
                                        </td>
                                        <td>
                                            <div class="category-color"
                                                 style="background-color: <?php echo htmlspecialchars(
                                                     $category["color"],
                                                 ); ?>"
                                                 title="<?php echo htmlspecialchars(
                                                     $category["color"],
                                                 ); ?>"></div>
                                        </td>
                                        <td>
                                            <?php if (
                                                $category["post_count"] > 0
                                            ): ?>
                                            <a href="posts.php?category=<?php echo $category[
                                                "id"
                                            ]; ?>" class="badge badge-success">
                                                <?php echo $category[
                                                    "post_count"
                                                ]; ?>
                                            </a>
                                            <?php else: ?>
                                            <span class="badge badge-secondary">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="category-actions">
                                            <a href="?edit=<?php echo $category[
                                                "id"
                                            ]; ?>" class="action-link">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>

                                            <?php if (
                                                canDo("manage_categories") &&
                                                $category["post_count"] == 0
                                            ): ?>
                                            <a href="?action=delete&id=<?php echo $category[
                                                "id"
                                            ]; ?>" class="action-link delete"
                                               onclick="return confirm('Are you sure you want to delete this category?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="no-categories">
                                <i class="fas fa-folder-open"></i>
                                <h3>No categories yet</h3>
                                <p>Create your first category using the form to get started.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Add/Edit Category Form -->
                    <div class="category-form">
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <i class="fas fa-<?php echo $edit_category
                                    ? "edit"
                                    : "plus"; ?>"></i>
                                <?php echo $edit_category
                                    ? "Edit Category"
                                    : "Add New Category"; ?>
                            </h3>

                            <form method="POST" id="categoryForm">
                                <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                                <?php if ($edit_category): ?>
                                <input type="hidden" name="category_id" value="<?php echo $edit_category[
                                    "id"
                                ]; ?>">
                                <?php endif; ?>

                                <div class="form-group">
                                    <label for="name" class="form-label">Name *</label>
                                    <input type="text" id="name" name="name" class="form-input" required
                                           placeholder="Enter category name"
                                           value="<?php echo htmlspecialchars(
                                               $edit_category["name"] ?? "",
                                           ); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea id="description" name="description" class="form-textarea" rows="3"
                                              placeholder="Brief description of this category"><?php echo htmlspecialchars(
                                                  $edit_category[
                                                      "description"
                                                  ] ?? "",
                                              ); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="color" class="form-label">Category Color</label>
                                    <div class="color-picker-group">
                                        <div class="color-preview" id="colorPreview"
                                             style="background-color: <?php echo htmlspecialchars(
                                                 $edit_category["color"] ??
                                                     "#3182ce",
                                             ); ?>"></div>
                                        <input type="color" id="color" name="color" class="color-input"
                                               value="<?php echo htmlspecialchars(
                                                   $edit_category["color"] ??
                                                       "#3182ce",
                                               ); ?>">
                                        <input type="text" id="colorText" class="form-input color-text-input"
                                               value="<?php echo htmlspecialchars(
                                                   $edit_category["color"] ??
                                                       "#3182ce",
                                               ); ?>"
                                               pattern="^#[0-9A-Fa-f]{6}$" placeholder="#3182ce">
                                    </div>

                                    <div class="popular-colors">
                                        <div class="color-option" style="background-color: #3182ce" data-color="#3182ce" title="Blue"></div>
                                        <div class="color-option" style="background-color: #e53e3e" data-color="#e53e3e" title="Red"></div>
                                        <div class="color-option" style="background-color: #38a169" data-color="#38a169" title="Green"></div>
                                        <div class="color-option" style="background-color: #f56565" data-color="#f56565" title="Orange"></div>
                                        <div class="color-option" style="background-color: #9f7aea" data-color="#9f7aea" title="Purple"></div>
                                        <div class="color-option" style="background-color: #4fd1c7" data-color="#4fd1c7" title="Teal"></div>
                                        <div class="color-option" style="background-color: #ed8936" data-color="#ed8936" title="Orange"></div>
                                        <div class="color-option" style="background-color: #4a5568" data-color="#4a5568" title="Gray"></div>
                                    </div>
                                </div>

                                <div class="form-group" style="margin: 0;">
                                    <?php if ($edit_category): ?>
                                    <button type="submit" name="update_category" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Category
                                    </button>
                                    <a href="categories.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <?php else: ?>
                                    <button type="submit" name="add_category" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add Category
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <?php if (!empty($categories)): ?>
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <i class="fas fa-chart-bar"></i> Category Statistics
                            </h3>
                            <div class="category-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Total Categories</span>
                                    <span class="stat-value"><?php echo count(
                                        $categories,
                                    ); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">With Posts</span>
                                    <span class="stat-value"><?php echo count(
                                        array_filter($categories, function (
                                            $c,
                                        ) {
                                            return $c["post_count"] > 0;
                                        }),
                                    ); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Empty</span>
                                    <span class="stat-value"><?php echo count(
                                        array_filter($categories, function (
                                            $c,
                                        ) {
                                            return $c["post_count"] == 0;
                                        }),
                                    ); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Color picker functionality
            const colorInput = document.getElementById('color');
            const colorText = document.getElementById('colorText');
            const colorPreview = document.getElementById('colorPreview');
            const colorOptions = document.querySelectorAll('.color-option');

            function updateColor(color) {
                if (colorInput && colorText && colorPreview) {
                    colorInput.value = color;
                    colorText.value = color;
                    colorPreview.style.backgroundColor = color;
                }
            }

            // Color input change
            if (colorInput) {
                colorInput.addEventListener('input', function() {
                    updateColor(this.value);
                });
            }

            // Text input change with validation
            if (colorText) {
                colorText.addEventListener('input', function() {
                    const color = this.value;
                    if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                        colorInput.value = color;
                        colorPreview.style.backgroundColor = color;
                        this.style.borderColor = '#d1d5db';
                    } else {
                        this.style.borderColor = '#ef4444';
                    }
                });

                colorText.addEventListener('blur', function() {
                    const color = this.value;
                    if (!/^#[0-9A-Fa-f]{6}$/.test(color)) {
                        this.value = colorInput.value; // Reset to valid value
                        this.style.borderColor = '#d1d5db';
                    }
                });
            }

            // Color option clicks with animation
            colorOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const color = this.dataset.color;
                    updateColor(color);

                    // Add click animation
                    this.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1.1)';
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 100);
                    }, 100);
                });
            });

            // Enhanced form validation
            const categoryForm = document.getElementById('categoryForm');
            if (categoryForm) {
                categoryForm.addEventListener('submit', function(e) {
                    const name = document.getElementById('name').value.trim();
                    const color = document.getElementById('colorText').value.trim();

                    if (!name) {
                        showAlert('Please enter a category name', 'error');
                        e.preventDefault();
                        return;
                    }

                    if (name.length < 2) {
                        showAlert('Category name must be at least 2 characters long', 'error');
                        e.preventDefault();
                        return;
                    }

                    if (name.length > 100) {
                        showAlert('Category name cannot exceed 100 characters', 'error');
                        e.preventDefault();
                        return;
                    }

                    if (!/^#[0-9A-Fa-f]{6}$/.test(color)) {
                        showAlert('Please enter a valid color code (e.g., #3182ce)', 'error');
                        e.preventDefault();
                        return;
                    }

                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    }
                });
            }

            // Slug preview functionality
            const nameInput = document.getElementById('name');
            if (nameInput) {
                let slugPreview = null;

                nameInput.addEventListener('input', function() {
                    const name = this.value;
                    const slug = name.toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '') // Remove special chars
                        .replace(/\s+/g, '-') // Replace spaces with hyphens
                        .replace(/-+/g, '-') // Replace multiple hyphens with single
                        .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens

                    // Create or update slug preview
                    if (!slugPreview) {
                        slugPreview = document.createElement('div');
                        slugPreview.className = 'category-slug';
                        slugPreview.style.marginTop = '0.5rem';
                        this.parentNode.appendChild(slugPreview);
                    }

                    slugPreview.textContent = slug || 'category-slug-preview';
                });
            }

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });

            // Utility function to show alerts
            function showAlert(message, type = 'info') {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type}`;
                alertDiv.style.opacity = '0';
                alertDiv.style.transform = 'translateY(-10px)';
                alertDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i> ${message}`;

                const contentArea = document.querySelector('.content-area');
                contentArea.insertBefore(alertDiv, contentArea.children[1]); // Insert after page header

                // Animate in
                setTimeout(() => {
                    alertDiv.style.transition = 'all 0.3s ease';
                    alertDiv.style.opacity = '1';
                    alertDiv.style.transform = 'translateY(0)';
                }, 10);

                // Auto-remove after 5 seconds
                setTimeout(() => {
                    alertDiv.style.opacity = '0';
                    alertDiv.style.transform = 'translateY(-10px)';
                    setTimeout(() => alertDiv.remove(), 300);
                }, 5000);
            }

            // Smooth scroll to form when editing
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('edit')) {
                setTimeout(() => {
                    document.querySelector('.category-form').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 100);
            }

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + N to focus on name input (new category)
                if ((e.ctrlKey || e.metaKey) && e.key === 'n' && !urlParams.get('edit')) {
                    e.preventDefault();
                    document.getElementById('name').focus();
                }

                // Escape to cancel edit mode
                if (e.key === 'Escape' && urlParams.get('edit')) {
                    window.location.href = 'categories.php';
                }
            });
        });
    </script>
</body>
</html>
