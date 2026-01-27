<?php
/**
 * Category Page Display
 * Handles displaying posts by category
 */

require_once '../database/config/database.php';

// Get the category slug from URL parameter
$category_slug = $_GET['slug'] ?? '';

// If no slug provided, redirect to homepage
if (empty($category_slug)) {
    header("Location: /");
    exit;
}

// Get database connection
try {
    $pdo = getDB();

    // Fetch the category information
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$category_slug]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        // Category not found - show 404
        header("HTTP/1.0 404 Not Found");
        include '../404.php';
        exit;
    }

    // Fetch posts in this category
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name as author_name, c.name as category_name, c.slug as category_slug, c.color as category_color
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = ? AND p.status = 'published'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$category['id']]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

<main class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Category Header -->
            <header class="mb-4">
                <h1 class="display-4">
                    Category: <?php echo htmlspecialchars($category['name']); ?>
                </h1>
                <p class="lead"><?php echo htmlspecialchars($category['description'] ?? ''); ?></p>
            </header>

            <!-- Posts List -->
            <?php if (count($posts) > 0): ?>
                <div class="row">
                    <?php foreach ($posts as $post): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <?php if (!empty($post['featured_image'])): ?>
                                    <img src="/akanyenyeri/uploads/images/<?php echo htmlspecialchars($post['featured_image']); ?>"
                                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                                         class="card-img-top">
                                <?php endif; ?>

                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                                    <p class="card-text">
                                        <?php echo mb_substr(strip_tags($post['content']), 0, 150) . '...'; ?>
                                    </p>
                                </div>

                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge" style="background-color: <?php echo htmlspecialchars($post['category_color'] ?? '#6c757d'); ?>;">
                                            <?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?>
                                        </span>
                                        <a href="<?php echo SITE_URL; ?>public/single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            Read More
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No posts found in this category.
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- Sidebar content -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">About This Category</h5>
                    <p class="card-text">
                        <?php echo htmlspecialchars($category['description'] ?? 'No description available.'); ?>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">All Categories</h5>
                    <ul class="list-unstyled">
                        <?php
                        // Fetch all categories
                        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
                        $all_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($all_categories as $cat): ?>
                            <li class="mb-2">
                                <a href="/akanyenyeri/category/<?php echo htmlspecialchars($cat['slug']); ?>"
                                   style="color: <?php echo htmlspecialchars($cat['color'] ?? '#6c757d'); ?>;">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// Include footer
include 'includes/footer.php';
?>