<?php
/**
 * Category Archive Template for Akanyenyeri Magazine
 */

require_once __DIR__ . '/../database/config/database.php';

$pdo = getDB();

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

$category = null;
if ($slug) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

if (!$category) {
    header("Location: index.php");
    exit;
}

function getCategoryPosts($pdo, $categoryId) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.full_name as author_name, c.name as category_name
            FROM posts p
            LEFT JOIN users u ON p.author_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.category_id = ? AND p.status = 'published'
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { return []; }
}

$posts = getCategoryPosts($pdo, $category['id']);

function getCategories($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { return []; }
}

$categories = getCategories($pdo);

function formatDate($date) { return date('F j, Y', strtotime($date)); }
?>

<?php include_once __DIR__ . '/header.php'; ?>

<div class="site-content">
    <div class="container-inner">
        <main id="primary" class="site-main">
            <header class="archive-header">
                <h1 class="archive-title"><?php echo htmlspecialchars($category['name']); ?></h1>
                <?php if (!empty($category['description'])): ?>
                <div class="archive-description"><?php echo htmlspecialchars($category['description']); ?></div>
                <?php endif; ?>
            </header>

            <div class="post-list">
                <?php if (count($posts) > 0): ?>
                    <?php foreach ($posts as $post): ?>
                    <article class="post-item">
                        <div class="post-item-thumbnail">
                            <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                                <img src="<?php echo htmlspecialchars($post['featured_image'] ?: 'https://via.placeholder.com/300x200'); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                            </a>
                        </div>
                        <div class="post-item-content">
                            <h2 class="post-item-title">
                                <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                            </h2>
                            <div class="post-item-meta">
                                <span><i class="fa fa-user"></i> <?php echo htmlspecialchars($post['author_name']); ?></span>
                                <span style="margin-left: 10px;"><i class="fa fa-calendar"></i> <?php echo formatDate($post['created_at']); ?></span>
                            </div>
                            <div class="post-item-excerpt">
                                <?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 150)) . '...'; ?>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No posts found in this category.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php include_once __DIR__ . '/footer.php'; ?>
