<?php
/**
 * Single Post Display
 * Handles displaying individual blog posts
 */

require_once '../database/config/database.php';

// Get the post slug from URL parameter
$slug = $_GET['slug'] ?? '';

// If no slug provided, redirect to homepage
if (empty($slug)) {
    header("Location: /");
    exit;
}

// Get database connection
try {
    $pdo = getDB();

    // Fetch the post from database
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name as author_name, c.name as category_name, c.slug as category_slug, c.color as category_color, p.featured_image
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.slug = ? AND p.status = 'published'
    ");
    $stmt->execute([$slug]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        // Post not found - show 404
        header("HTTP/1.0 404 Not Found");
        include '../404.php';
        exit;
    }

} catch (PDOException $e) {
    // Database error
    header("HTTP/1.0 500 Internal Server Error");
    echo "Database error: " . htmlspecialchars($e->getMessage());
    exit;
}

// Include header
include 'includes/head.php';
include 'includes/nav.php';
?>

<main class="container mt-5 mb-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Post Header -->
            <article class="blog-post">
                <header class="mb-4">
                    <!-- Category & Date -->
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge rounded-pill px-3 py-2 me-3" style="background-color: <?php echo htmlspecialchars($post['category_color'] ?? '#0d6efd'); ?>; font-size: 0.9rem;">
                            <?php echo htmlspecialchars($post['category_name'] ?? 'News'); ?>
                        </span>
                        <span class="text-muted">
                            <i class="far fa-calendar-alt me-2"></i><?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                        </span>
                    </div>

                    <!-- Title -->
                    <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($post['title']); ?></h1>

                    <!-- Author -->
                    <div class="d-flex align-items-center mb-4">
                        <div class="avatar-circle me-3 bg-light d-flex align-items-center justify-content-center text-primary fw-bold" style="width: 40px; height: 40px; border-radius: 50%;">
                            <?php echo strtoupper(substr($post['author_name'] ?? 'A', 0, 1)); ?>
                        </div>
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></div>
                            <div class="text-muted small">Author</div>
                        </div>
                    </div>

                    <!-- Featured Image -->
                    <?php if (!empty($post['featured_image'])): ?>
                        <div class="ratio ratio-16x9 mb-5 rounded-3 overflow-hidden shadow-sm">
                            <img src="<?php echo SITE_URL; ?>uploads/images/<?php echo htmlspecialchars($post['featured_image']); ?>"
                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                 class="object-fit-cover">
                        </div>
                    <?php endif; ?>
                </header>

                <!-- Post Content -->
                <div class="post-content fs-5 lh-lg mb-5">
                    <?php 
                    if (!empty($post['content'])) {
                        echo $post['content']; 
                    } else {
                        echo '<p class="text-muted fst-italic">No content available for this article.</p>';
                    }
                    ?>
                </div>

                <!-- Share & Tags -->
                <div class="border-top border-bottom py-4 mb-5">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-2">
                            <span class="fw-bold me-2">Share:</span>
                            <button class="btn btn-sm btn-outline-primary rounded-circle"><i class="fab fa-facebook-f"></i></button>
                            <button class="btn btn-sm btn-outline-info rounded-circle"><i class="fab fa-twitter"></i></button>
                            <button class="btn btn-sm btn-outline-success rounded-circle"><i class="fab fa-whatsapp"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="d-flex justify-content-between mb-5">
                    <a href="<?php echo SITE_URL; ?>public/index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>
                    <?php if (!empty($post['category_slug'])): ?>
                        <a href="<?php echo SITE_URL; ?>public/category.php?slug=<?php echo htmlspecialchars($post['category_slug']); ?>" class="btn btn-primary">
                            More in <?php echo htmlspecialchars($post['category_name']); ?> <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    <?php endif; ?>
                </div>

            </article>
        </div>
    </div>
</main>

<?php
// Include footer
include 'includes/footer.php';
?>