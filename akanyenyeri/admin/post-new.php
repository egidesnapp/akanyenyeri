<?php
/**
 * Add New Post - Akanyenyeri Magazine Admin
 * Properly structured responsive page that fits the window
 */

session_start();
require_once "php/auth_check.php";
require_once "../config/database.php";

// Require authentication
requireAuth();

// Get database connection
$pdo = getDB();

// Handle form submission
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create_post"])) {
    try {
        // Validate CSRF token
        if (!validateCSRF($_POST["csrf_token"] ?? "")) {
            throw new Exception("Invalid security token");
        }

        // Get form data
        $title = trim($_POST["title"] ?? "");
        $content = trim($_POST["content"] ?? "");
        $excerpt = trim($_POST["excerpt"] ?? "");
        $category_id = intval($_POST["category_id"] ?? 0);
        $status = $_POST["status"] ?? "draft";
        $featured_image = trim($_POST["featured_image"] ?? "");
        $meta_title = trim($_POST["meta_title"] ?? "");
        $meta_description = trim($_POST["meta_description"] ?? "");
        $is_featured = isset($_POST["is_featured"]) ? 1 : 0;
        $tags = trim($_POST["tags"] ?? "");

        // Basic validation
        if (empty($title)) {
            throw new Exception("Title is required");
        }
        if (empty($content)) {
            throw new Exception("Content is required");
        }
        if ($category_id <= 0) {
            throw new Exception("Please select a category");
        }

        // Generate slug from title
        $slug = strtolower(
            trim(preg_replace("/[^A-Za-z0-9-]+/", "-", $title), "-"),
        );

        // Check if slug exists
        $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug .= "-" . time();
        }

        // Auto-generate excerpt if not provided
        if (empty($excerpt)) {
            $excerpt = substr(strip_tags($content), 0, 200) . "...";
        }

        // Auto-generate meta fields if not provided
        if (empty($meta_title)) {
            $meta_title = $title;
        }
        if (empty($meta_description)) {
            $meta_description = substr(strip_tags($content), 0, 160);
        }

        // Insert post
        $stmt = $pdo->prepare("
            INSERT INTO posts (
                title, slug, content, excerpt, featured_image,
                author_id, category_id, status, is_featured,
                meta_title, meta_description, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $title,
            $slug,
            $content,
            $excerpt,
            $featured_image,
            $_SESSION["admin_user_id"],
            $category_id,
            $status,
            $is_featured,
            $meta_title,
            $meta_description,
        ]);

        $post_id = $pdo->lastInsertId();

        // Handle tags
        if (!empty($tags)) {
            $tag_array = array_map("trim", explode(",", $tags));
            foreach ($tag_array as $tag_name) {
                if (!empty($tag_name)) {
                    $tag_slug = strtolower(
                        trim(
                            preg_replace("/[^A-Za-z0-9-]+/", "-", $tag_name),
                            "-",
                        ),
                    );

                    // Insert or get tag
                    $stmt = $pdo->prepare(
                        "INSERT IGNORE INTO tags (name, slug) VALUES (?, ?)",
                    );
                    $stmt->execute([$tag_name, $tag_slug]);

                    $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = ?");
                    $stmt->execute([$tag_slug]);
                    $tag_id = $stmt->fetchColumn();

                    if ($tag_id) {
                        // Check if post_tags table exists and has correct structure
                        try {
                            $stmt = $pdo->prepare(
                                "INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)",
                            );
                            $stmt->execute([$post_id, $tag_id]);
                        } catch (PDOException $e) {
                            // Table might not exist, skip tag association
                        }
                    }
                }
            }
        }

        $success_message = "Post '{$title}' created successfully!";

        // Redirect based on status
        if ($status === "published") {
            header("Location: posts.php?success=published&id={$post_id}");
        } else {
            header("Location: post-edit.php?id={$post_id}&success=created");
        }
        exit();
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get categories for dropdown
$categories = [];
try {
    $stmt = $pdo->query(
        "SELECT id, name, color FROM categories ORDER BY name ASC",
    );
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// Get current user info
$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Post - Akanyenyeri Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin-layout.css">

    <!-- TinyMCE Rich Text Editor -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

    <style>
        /* Add New Post specific styles */
        .post-editor-container {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 2rem;
            max-width: none;
        }

        .post-main-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .post-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .editor-section {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .editor-section-header {
            padding: 1.25rem 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .editor-section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #2d3748;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .editor-section-title i {
            color: #3182ce;
        }

        .editor-section-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-label.required::after {
            content: " *";
            color: #e53e3e;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            background-color: #ffffff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }

        .form-input#title {
            font-size: 1.125rem;
            font-weight: 600;
            padding: 1rem;
        }

        .editor-content {
            min-height: 400px;
        }

        .form-help {
            font-size: 0.8125rem;
            color: #6b7280;
            margin-top: 0.375rem;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: auto;
            margin: 0;
            cursor: pointer;
        }

        .checkbox-wrapper label {
            margin: 0;
            cursor: pointer;
            font-size: 0.875rem;
        }

        .button-group {
            display: flex;
            gap: 0.75rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            border-radius: 0 0 8px 8px;
            flex-wrap: wrap;
        }

        .btn-publish {
            background: #059669;
            color: white;
            border: 1px solid #059669;
        }

        .btn-publish:hover {
            background: #047857;
            border-color: #047857;
        }

        .btn-draft {
            background: #f59e0b;
            color: white;
            border: 1px solid #f59e0b;
        }

        .btn-draft:hover {
            background: #d97706;
            border-color: #d97706;
        }

        .publish-options {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .publish-section {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .publish-section:last-child {
            border-bottom: none;
        }

        .publish-section h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin: 0 0 0.75rem 0;
        }

        .status-select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .image-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 6px;
            margin-top: 0.5rem;
            display: none;
        }

        .tags-input-wrapper {
            position: relative;
        }

        .tags-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #d1d5db;
            border-top: none;
            border-radius: 0 0 6px 6px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 10;
            display: none;
        }

        .tag-suggestion {
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            font-size: 0.875rem;
        }

        .tag-suggestion:hover {
            background: #f3f4f6;
        }

        /* Character counter */
        .char-counter {
            font-size: 0.75rem;
            color: #6b7280;
            text-align: right;
            margin-top: 0.25rem;
        }

        .char-counter.warning {
            color: #f59e0b;
        }

        .char-counter.error {
            color: #e53e3e;
        }

        /* Mobile responsiveness */
        @media (max-width: 1200px) {
            .post-editor-container {
                grid-template-columns: 1fr 280px;
                gap: 1.5rem;
            }
        }

        @media (max-width: 1024px) {
            .post-editor-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .post-sidebar {
                order: -1;
            }

            .editor-section-body {
                padding: 1.25rem;
            }
        }

        @media (max-width: 768px) {
            .post-editor-container {
                gap: 1rem;
            }

            .editor-section-body {
                padding: 1rem;
            }

            .button-group {
                padding: 1rem;
                flex-direction: column;
            }

            .btn {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .editor-section-header {
                padding: 1rem;
            }

            .editor-section-body {
                padding: 0.75rem;
            }

            .form-input#title {
                font-size: 1rem;
                padding: 0.875rem;
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
                        <i class="fas fa-plus"></i> Add New Post
                    </h1>
                    <div class="page-actions">
                        <a href="posts.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Posts
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

                <!-- Post Editor Form -->
                <form method="POST" id="postForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <input type="hidden" name="create_post" value="1">

                    <div class="post-editor-container">
                        <!-- Main Content Area -->
                        <div class="post-main-content">
                            <!-- Title Section -->
                            <div class="editor-section">
                                <div class="editor-section-body">
                                    <div class="form-group">
                                        <label for="title" class="form-label required">Post Title</label>
                                        <input
                                            type="text"
                                            id="title"
                                            name="title"
                                            class="form-input"
                                            placeholder="Enter your post title here..."
                                            required
                                            maxlength="255"
                                        >
                                        <div class="char-counter" id="titleCounter">0/255</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Content Editor -->
                            <div class="editor-section">
                                <div class="editor-section-header">
                                    <h3 class="editor-section-title">
                                        <i class="fas fa-edit"></i>
                                        Content
                                    </h3>
                                </div>
                                <div class="editor-section-body">
                                    <div class="form-group">
                                        <label for="content" class="form-label required">Post Content</label>
                                        <textarea
                                            id="content"
                                            name="content"
                                            class="form-textarea editor-content"
                                            placeholder="Write your post content here..."
                                            required
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Excerpt Section -->
                            <div class="editor-section">
                                <div class="editor-section-header">
                                    <h3 class="editor-section-title">
                                        <i class="fas fa-quote-right"></i>
                                        Excerpt
                                    </h3>
                                </div>
                                <div class="editor-section-body">
                                    <div class="form-group">
                                        <label for="excerpt" class="form-label">Post Excerpt</label>
                                        <textarea
                                            id="excerpt"
                                            name="excerpt"
                                            class="form-textarea"
                                            rows="3"
                                            placeholder="Optional: Write a short excerpt for your post..."
                                            maxlength="300"
                                        ></textarea>
                                        <div class="form-help">Leave empty to auto-generate from content.</div>
                                        <div class="char-counter" id="excerptCounter">0/300</div>
                                    </div>
                                </div>
                            </div>

                            <!-- SEO Section -->
                            <div class="editor-section">
                                <div class="editor-section-header">
                                    <h3 class="editor-section-title">
                                        <i class="fas fa-search"></i>
                                        SEO Settings
                                    </h3>
                                </div>
                                <div class="editor-section-body">
                                    <div class="form-group">
                                        <label for="meta_title" class="form-label">Meta Title</label>
                                        <input
                                            type="text"
                                            id="meta_title"
                                            name="meta_title"
                                            class="form-input"
                                            placeholder="Leave empty to use post title"
                                            maxlength="60"
                                        >
                                        <div class="char-counter" id="metaTitleCounter">0/60</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="meta_description" class="form-label">Meta Description</label>
                                        <textarea
                                            id="meta_description"
                                            name="meta_description"
                                            class="form-textarea"
                                            rows="2"
                                            placeholder="Leave empty to auto-generate from content"
                                            maxlength="160"
                                        ></textarea>
                                        <div class="char-counter" id="metaDescCounter">0/160</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="post-sidebar">
                            <!-- Publish Options -->
                            <div class="editor-section">
                                <div class="editor-section-header">
                                    <h3 class="editor-section-title">
                                        <i class="fas fa-paper-plane"></i>
                                        Publish
                                    </h3>
                                </div>
                                <div class="publish-options">
                                    <div class="publish-section">
                                        <h4>Status</h4>
                                        <select name="status" class="status-select">
                                            <option value="draft">Draft</option>
                                            <option value="published">Published</option>
                                            <option value="pending">Pending Review</option>
                                        </select>
                                    </div>
                                    <div class="publish-section">
                                        <div class="checkbox-wrapper">
                                            <input type="checkbox" id="is_featured" name="is_featured">
                                            <label for="is_featured">Featured Post</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="button-group">
                                    <button type="submit" class="btn btn-publish">
                                        <i class="fas fa-paper-plane"></i>
                                        Create Post
                                    </button>
                                    <button type="button" class="btn btn-draft" onclick="setStatusAndSubmit('draft')">
                                        <i class="fas fa-save"></i>
                                        Save as Draft
                                    </button>
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="editor-section">
                                <div class="editor-section-header">
                                    <h3 class="editor-section-title">
                                        <i class="fas fa-folder"></i>
                                        Category
                                    </h3>
                                </div>
                                <div class="editor-section-body">
                                    <div class="form-group">
                                        <label for="category_id" class="form-label required">Select Category</label>
                                        <select id="category_id" name="category_id" class="form-select" required>
                                            <option value="">Choose a category...</option>
                                            <?php foreach (
                                                $categories
                                                as $category
                                            ): ?>
                                            <option value="<?php echo $category[
                                                "id"
                                            ]; ?>">
                                                <?php echo htmlspecialchars(
                                                    $category["name"],
                                                ); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Tags -->
                            <div class="editor-section">
                                <div class="editor-section-header">
                                    <h3 class="editor-section-title">
                                        <i class="fas fa-tags"></i>
                                        Tags
                                    </h3>
                                </div>
                                <div class="editor-section-body">
                                    <div class="form-group">
                                        <label for="tags" class="form-label">Post Tags</label>
                                        <div class="tags-input-wrapper">
                                            <input
                                                type="text"
                                                id="tags"
                                                name="tags"
                                                class="form-input"
                                                placeholder="Enter tags separated by commas..."
                                            >
                                            <div class="tags-suggestions" id="tagsSuggestions"></div>
                                        </div>
                                        <div class="form-help">Separate tags with commas. Example: news, politics, breaking</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Featured Image -->
                            <div class="editor-section">
                                <div class="editor-section-header">
                                    <h3 class="editor-section-title">
                                        <i class="fas fa-image"></i>
                                        Featured Image
                                    </h3>
                                </div>
                                <div class="editor-section-body">
                                    <div class="form-group">
                                        <label for="featured_image" class="form-label">Image URL</label>
                                        <input
                                            type="url"
                                            id="featured_image"
                                            name="featured_image"
                                            class="form-input"
                                            placeholder="https://example.com/image.jpg"
                                        >
                                        <div class="form-help">
                                            <a href="media.php" target="_blank">Upload images in Media Library</a>
                                        </div>
                                        <img id="imagePreview" class="image-preview" alt="Preview">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
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

        // Initialize TinyMCE
        tinymce.init({
            selector: '#content',
            height: 400,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family: Inter, Arial, sans-serif; font-size: 14px }',
            setup: function (editor) {
                editor.on('change', function () {
                    tinymce.triggerSave();
                });
            }
        });

        // Character counters
        function setupCharCounter(inputId, counterId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);

            if (input && counter) {
                function updateCounter() {
                    const length = input.value.length;
                    counter.textContent = length + '/' + maxLength;

                    counter.classList.remove('warning', 'error');
                    if (length > maxLength * 0.9) {
                        counter.classList.add('warning');
                    }
                    if (length >= maxLength) {
                        counter.classList.add('error');
                    }
                }

                input.addEventListener('input', updateCounter);
                updateCounter();
            }
        }

        // Setup character counters
        document.addEventListener('DOMContentLoaded', function() {
            setupCharCounter('title', 'titleCounter', 255);
            setupCharCounter('excerpt', 'excerptCounter', 300);
            setupCharCounter('meta_title', 'metaTitleCounter', 60);
            setupCharCounter('meta_description', 'metaDescCounter', 160);

            // Featured image preview
            const featuredImageInput = document.getElementById('featured_image');
            const imagePreview = document.getElementById('imagePreview');

            if (featuredImageInput && imagePreview) {
                featuredImageInput.addEventListener('input', function() {
                    if (this.value) {
                        imagePreview.src = this.value;
                        imagePreview.style.display = 'block';
                        imagePreview.onerror = function() {
                            this.style.display = 'none';
                        };
                    } else {
                        imagePreview.style.display = 'none';
                    }
                });
            }

            // Form validation
            const form = document.getElementById('postForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const title = document.getElementById('title').value.trim();
                    const categoryId = document.getElementById('category_id').value;

                    if (!title) {
                        e.preventDefault();
                        alert('Please enter a post title.');
                        document.getElementById('title').focus();
                        return;
                    }

                    if (!categoryId) {
                        e.preventDefault();
                        alert('Please select a category.');
                        document.getElementById('category_id').focus();
                        return;
                    }

                    // Ensure TinyMCE content is saved
                    if (tinymce.get('content')) {
                        tinymce.triggerSave();
                    }

                    const content = document.getElementById('content').value.trim();
                    if (!content) {
                        e.preventDefault();
                        alert('Please enter post content.');
                        if (tinymce.get('content')) {
                            tinymce.get('content').focus();
                        }
                        return;
                    }

                    // Show loading state
                    const submitBtns = form.querySelectorAll('button[type="submit"]');
                    submitBtns.forEach(btn => {
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                    });
                });
            }

            // Mobile menu toggle
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            if (mobileToggle) {
                mobileToggle.addEventListener('click', toggleMobileSidebar);
            }

            // Auto-save draft functionality (optional)
            let autoSaveTimer;
            function scheduleAutoSave() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(function() {
                    // You can implement auto-save functionality here
                    console.log('Auto-save triggered');
                }, 30000); // Auto-save every 30 seconds
            }

            // Trigger auto-save on content changes
            document.getElementById('title').addEventListener('input', scheduleAutoSave);
            if (tinymce.get('content')) {
                tinymce.get('content').on('change', scheduleAutoSave);
            }
        });

        // Function to set status and submit form
        function setStatusAndSubmit(status) {
            document.querySelector('select[name="status"]').value = status;
            document.getElementById('postForm').submit();
        }

        // Prevent data loss on page unload
        let formModified = false;
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    formModified = true;
                });
            });

            window.addEventListener('beforeunload', function(e) {
                if (formModified) {
                    e.preventDefault();
                    e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                }
            });

            // Clear flag on form submit
            document.getElementById('postForm').addEventListener('submit', function() {
                formModified = false;
            });
        });
    </script>
</body>
</html>
