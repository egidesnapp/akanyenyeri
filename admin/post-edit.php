<?php
session_start();
require_once 'php/auth_check.php';
require_once '../database/config/database.php';

requireAuth();
$pdo = getDB();

$post_id = intval($_GET['id'] ?? 0);
if (!$post_id) {
    header('Location: posts.php');
    exit;
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_post'])) {
    try {
        if (!validateCSRF($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }

        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $status = $_POST['status'] ?? 'draft';
        $featured_image = trim($_POST['featured_image'] ?? '');
        $meta_title = trim($_POST['meta_title'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;

        if (empty($title)) throw new Exception('Title is required');
        if (empty($content)) throw new Exception('Content is required');
        if ($category_id <= 0) throw new Exception('Please select a category');

        $current_post = $pdo->prepare("SELECT slug, title FROM posts WHERE id = ?");
        $current_post->execute([$post_id]);
        $current_data = $current_post->fetch(PDO::FETCH_ASSOC);

        $slug = $current_data['slug'];
        if ($title !== $current_data['title']) {
            $new_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
            $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
            $stmt->execute([$new_slug, $post_id]);
            if ($stmt->fetch()) {
                $new_slug .= '-' . time();
            }
            $slug = $new_slug;
        }

        if (empty($excerpt)) {
            $excerpt = substr(strip_tags($content), 0, 200) . '...';
        }
        if (empty($meta_title)) {
            $meta_title = $title;
        }
        if (empty($meta_description)) {
            $meta_description = substr(strip_tags($content), 0, 160);
        }

        $stmt = $pdo->prepare("
            UPDATE posts SET
                title = ?, slug = ?, content = ?, excerpt = ?,
                featured_image = ?, category_id = ?, status = ?, is_featured = ?,
                meta_title = ?, meta_description = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $title, $slug, $content, $excerpt, $featured_image,
            $category_id, $status, $is_featured,
            $meta_title, $meta_description, $post_id
        ]);

        $success_message = 'Post updated successfully!';

        // Clear cache so changes show immediately on the website
        require_once __DIR__ . '/../public/cache.php';
        cache_clear_posts();

        $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$post_id]);
        $tags = array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')));
        foreach ($tags as $tag_name) {
            $tag_stmt = $pdo->prepare("INSERT OR IGNORE INTO tags (name) VALUES (?)");
            $tag_stmt->execute([$tag_name]);
            $tag_id = $pdo->lastInsertId();
            if (!$tag_id) {
                $tag_stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
                $tag_stmt->execute([$tag_name]);
                $tag_id = $tag_stmt->fetchColumn();
            }
            if ($tag_id) {
                $pt_stmt = $pdo->prepare("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                $pt_stmt->execute([$post_id, $tag_id]);
            }
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        header('Location: posts.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT t.name FROM tags t INNER JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ? ORDER BY t.name");
    $stmt->execute([$post_id]);
    $post_tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
    $post = null;
}

$categories = [];
try {
    $stmt = $pdo->query("SELECT id, name, color FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

if (!$post) {
    echo "<h1>Post not found</h1>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Post: <?php echo htmlspecialchars($post['title']); ?></title>
    <link rel="stylesheet" href="css/admin-layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
    <style>
        :root {
            --primary: #2b6cb0;
            --danger: #f56565;
            --muted: #6b7280;
            --light: #f9fafb;
        }
        
        .post-editor {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 24px;
            padding: 24px;
        }
        
        .editor-main {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .editor-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(16,24,40,0.04);
        }
        
        .editor-card h2 {
            margin: 0 0 16px 0;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group:last-child {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }
        
        .form-group input[type="text"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-group input[type="text"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(43,108,176,0.1);
        }
        
        #content {
            display: none;
        }
        
        .mce-tinymce {
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
        }
        
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .sidebar-box {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 4px 12px rgba(16,24,40,0.04);
        }
        
        .sidebar-box h3 {
            margin: 0 0 12px 0;
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox-item label {
            margin: 0;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn {
            padding: 10px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e4d7b;
        }
        
        .btn-secondary {
            background: var(--light);
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .btn-secondary:hover {
            background: #f3f4f6;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .post-info {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.6;
        }
        
        .post-info strong {
            color: #1f2937;
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .btn-back:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 1024px) {
            .post-editor {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            <div class="content-area">
                <div style="max-width:1200px;margin:0 auto;">
                    
                    <a href="posts.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Posts</a>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" class="post-editor" id="postForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        <input type="hidden" name="update_post" value="1">
                        
                        <div class="editor-main">
                            <div class="editor-card">
                                <div class="form-group">
                                    <input type="text" id="title" name="title" placeholder="Post title..." value="<?php echo htmlspecialchars($post['title']); ?>" style="font-size:28px;font-weight:bold;padding:12px;border:none;background:none;">
                                </div>
                            </div>
                            
                            <div class="editor-card">
                                <h2>Content</h2>
                                <textarea id="content" name="content"><?php echo htmlspecialchars($post['content']); ?></textarea>
                            </div>
                            
                            <div class="editor-card">
                                <h2>Excerpt</h2>
                                <div class="form-group">
                                    <textarea name="excerpt" placeholder="Brief summary of the post" style="min-height:100px;"><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
                                </div>
                                <small style="color:var(--muted);">If left empty, it will be auto-generated from content.</small>
                            </div>
                            
                            <div class="editor-card">
                                <h2>SEO</h2>
                                <div class="form-group">
                                    <label>Meta Title</label>
                                    <input type="text" name="meta_title" placeholder="SEO title" value="<?php echo htmlspecialchars($post['meta_title']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Meta Description</label>
                                    <textarea name="meta_description" placeholder="SEO description" style="min-height:80px;"><?php echo htmlspecialchars($post['meta_description']); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="sidebar">
                            <div class="sidebar-box">
                                <h3><i class="fas fa-cog"></i> Publish</h3>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status">
                                        <option value="draft" <?php echo $post['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="published" <?php echo $post['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                        <option value="archived" <?php echo $post['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                    </select>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="is_featured" name="is_featured" <?php echo $post['is_featured'] ? 'checked' : ''; ?>>
                                    <label for="is_featured">Featured Post</label>
                                </div>
                                <div class="btn-group" style="margin-top:16px;gap:8px;">
                                    <button type="button" class="btn btn-primary" style="flex:1;" onclick="setStatusAndSubmit('published')"><i class="fas fa-paper-plane"></i> Publish</button>
                                    <button type="button" class="btn btn-secondary" style="flex:1;" onclick="setStatusAndSubmit('draft')"><i class="fas fa-save"></i> Save Draft</button>
                                </div>
                                <a href="posts.php" class="btn btn-secondary" style="margin-top:8px;width:100%;text-align:center;text-decoration:none;display:inline-block;"><i class="fas fa-times"></i> Cancel</a>
                            </div>
                            
                            <div class="sidebar-box">
                                <h3><i class="fas fa-folder"></i> Category</h3>
                                <div class="form-group">
                                    <select name="category_id" id="category_id">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo $post['category_id'] === $cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="sidebar-box">
                                <h3><i class="fas fa-image"></i> Featured Image</h3>
                                <div class="form-group">
                                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <input
                                            type="url"
                                            id="featured_image"
                                            name="featured_image"
                                            class="form-input"
                                            placeholder="https://example.com/image.jpg"
                                            value="<?php echo htmlspecialchars($post['featured_image'] ?? ''); ?>"
                                            style="display: none;"
                                        >
                                        <button type="button" id="selectImageBtn" class="btn" style="flex-shrink: 0; background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer;">
                                            <i class="fas fa-images"></i> Browse
                                        </button>
                                    </div>
                                    <img id="imagePreview" class="image-preview" alt="Current featured image" style="max-width: 100%; max-height: 150px; border-radius: 6px; margin-top: 0.5rem; <?php echo empty($post['featured_image']) ? 'display: none;' : ''; ?>" src="<?php echo htmlspecialchars($post['featured_image'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="sidebar-box">
                                <h3><i class="fas fa-tag"></i> Tags</h3>
                                <div class="form-group">
                                    <input type="text" name="tags" placeholder="tag1, tag2, tag3" value="<?php echo htmlspecialchars(implode(', ', $post_tags)); ?>">
                                </div>
                                <small style="color:var(--muted);">Separate with commas</small>
                            </div>
                            
                            <div class="sidebar-box" style="background:#f0f9ff;border-color:#bfdbfe;">
                                <h3><i class="fas fa-info-circle"></i> Info</h3>
                                <div class="post-info">
                                    <div><strong>ID:</strong> #<?php echo $post['id']; ?></div>
                                    <div><strong>Created:</strong> <?php echo date('M j, Y', strtotime($post['created_at'])); ?></div>
                                    <div><strong>Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($post['updated_at'])); ?></div>
                                    <div><strong>Status:</strong> <span style="text-transform:capitalize;padding:2px 8px;background:#dbeafe;color:#1e40af;border-radius:4px;font-size:12px;"><?php echo $post['status']; ?></span></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        tinymce.init({
            selector: '#content',
            plugins: 'link image code lists',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | code',
            height: 400,
            skin: 'oxide',
            content_css: 'oxide',
            branding: false
        });

        function setStatusAndSubmit(status) {
            document.querySelector('select[name="status"]').value = status;
            document.getElementById('postForm').submit();
        }

        document.getElementById('postForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const content = tinymce.get('content').getContent().trim();
            const category = document.getElementById('category_id').value;

            if (!title) {
                alert('Please enter a post title');
                e.preventDefault();
                return;
            }

            if (!content) {
                alert('Please enter post content');
                e.preventDefault();
                return;
            }

            if (!category) {
                alert('Please select a category');
                e.preventDefault();
                return;
            }
        });

        // Image selection functionality
        (function(){
            const selectImageBtn = document.getElementById('selectImageBtn');
            const imageModal = document.createElement('div');
            imageModal.id = 'imageModal';
            imageModal.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;';
            imageModal.innerHTML = `
                <div style="background:white;border-radius:12px;width:90%;max-width:900px;max-height:85vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                    <div style="padding:1.5rem 2rem;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;">
                        <div>
                            <h3 style="margin:0;font-size:1.375rem;font-weight:700;">Select Featured Image</h3>
                            <p style="margin:0.5rem 0 0 0;opacity:0.9;font-size:0.875rem;">Choose an image from your media library</p>
                        </div>
                        <button type="button" id="closeImageModal" style="background:none;border:none;font-size:2rem;cursor:pointer;color:white;width:40px;height:40px;display:flex;align-items:center;justify-content:center;opacity:0.8;">×</button>
                    </div>
                    <div id="imageGallery" style="padding:2rem;overflow-y:auto;flex:1;display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1.5rem;background:#f9fafb;">
                        <div style="grid-column:1/-1;text-align:center;color:#718096;padding:3rem 1rem;">
                            <p style="margin:0;animation:pulse 1.5s infinite;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;margin-bottom:1rem;"></i><br>Loading images...</p>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(imageModal);

            const featuredImageInput = document.getElementById('featured_image');
            const imagePreview = document.getElementById('imagePreview');

            // Load images from media library
            function loadImages() {
                const gallery = document.getElementById('imageGallery');
                gallery.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#718096;padding:3rem 1rem;"><p style="margin:0;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;margin-bottom:1rem;"></i><br>Loading images...</p></div>';

                fetch('media.php?action=get_images')
                    .then(response => response.json())
                    .then(data => {
                        gallery.innerHTML = '';
                        if (data.success && data.images.length > 0) {
                            data.images.forEach(image => {
                                const div = document.createElement('div');
                                div.style.cssText = 'cursor:pointer;border:2px solid #e2e8f0;border-radius:8px;overflow:hidden;transition:all 0.3s;background:white;box-shadow:0 1px 3px rgba(0,0,0,0.1);';

                                const imgContainer = document.createElement('div');
                                imgContainer.style.cssText = 'width:100%;height:160px;overflow:hidden;background:#f3f4f6;display:flex;align-items:center;justify-content:center;';

                                const img = document.createElement('img');
                                img.src = image.url;
                                img.alt = image.alt;
                                img.style.cssText = 'width:100%;height:100%;object-fit:cover;';

                                const info = document.createElement('div');
                                info.style.cssText = 'padding:0.75rem;background:white;display:flex;flex-direction:column;gap:8px;';
                                info.innerHTML = `<p style="margin:0;font-size:0.8rem;color:#4b5563;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${image.name}</p>`;

                                const insertBtn = document.createElement('button');
                                insertBtn.textContent = 'Select';
                                insertBtn.style.cssText = 'padding:6px 8px;border-radius:6px;border:none;background:#2b6cb0;color:#fff;cursor:pointer;font-size:13px;flex:1;';
                                insertBtn.addEventListener('click', function(e){
                                    e.stopPropagation();
                                    featuredImageInput.value = image.url;
                                    imageModal.style.display = 'none';
                                    imagePreview.src = image.url;
                                    imagePreview.style.display = 'block';
                                    imagePreview.style.marginTop = '1rem';
                                });

                                info.appendChild(insertBtn);

                                imgContainer.appendChild(img);
                                div.appendChild(imgContainer);
                                div.appendChild(info);

                                div.addEventListener('mouseover', function() {
                                    this.style.borderColor = '#667eea';
                                    this.style.boxShadow = '0 10px 25px rgba(102,126,234,0.2)';
                                    this.style.transform = 'translateY(-2px)';
                                });
                                div.addEventListener('mouseout', function() {
                                    this.style.borderColor = '#e2e8f0';
                                    this.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
                                    this.style.transform = 'translateY(0)';
                                });

                                div.addEventListener('click', function() {
                                    featuredImageInput.value = image.url;
                                    imageModal.style.display = 'none';
                                    imagePreview.src = image.url;
                                    imagePreview.style.display = 'block';
                                    imagePreview.style.marginTop = '1rem';
                                });

                                gallery.appendChild(div);
                            });
                        } else {
                            gallery.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#718096;padding:3rem 1rem;"><i class="fas fa-image" style="font-size:3rem;color:#cbd5e0;margin-bottom:1rem;"></i><p style="margin:0.5rem 0 0 0;font-weight:500;">No images found</p><p style="margin:0.5rem 0 0 0;font-size:0.875rem;">Upload images in <a href="media.php" target="_blank" style="color:#667eea;text-decoration:none;">Media Library</a></p></div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading images:', error);
                        gallery.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#e53e3e;padding:3rem 1rem;"><i class="fas fa-exclamation-circle" style="font-size:2rem;margin-bottom:1rem;"></i><p style="margin:0;">Error loading images</p><p style="margin:0.5rem 0 0 0;font-size:0.875rem;">Please try again or upload images in Media Library</p></div>';
                    });
            }

            if (selectImageBtn) {
                selectImageBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    imageModal.style.display = 'flex';
                    loadImages();
                });
            }

            document.getElementById('closeImageModal').addEventListener('click', function() {
                imageModal.style.display = 'none';
            });

            imageModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });

            // Featured image preview
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
        })();
    </script>
</body>
</html>
