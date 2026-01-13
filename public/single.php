<?php
/**
 * Single Post Template for Akanyenyeri Magazine
 */

require_once __DIR__ . '/../database/config/database.php';

$pdo = getDB();

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

$post = null;
if ($slug) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.full_name as author_name, c.name as category_name, c.slug as category_slug
            FROM posts p
            LEFT JOIN users u ON p.author_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.slug = ? AND p.status = 'published'
        ");
        $stmt->execute([$slug]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post) {
            $updateStmt = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
            $updateStmt->execute([$post['id']]);
        }
    } catch (PDOException $e) {}
}

if (!$post) {
    header("Location: index.php");
    exit;
}

function getCategories($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { return []; }
}
$categories = getCategories($pdo);

function getRecentPosts($pdo, $limit = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'published'
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { return []; }
}
$recentPosts = getRecentPosts($pdo);

function formatDate($date) { return date('F j, Y', strtotime($date)); }
?>

<?php include_once __DIR__ . '/header.php'; ?>

<div class="site-content">
    <div class="container-inner">
        <main id="primary" class="site-main">
            <nav class="breadcrumbs">
                <ul class="trail-items">
                    <li><a href="index.php"><i class="fa fa-home"></i> Home</a></li>
                    <li><a href="category.php?slug=<?php echo htmlspecialchars($post['category_slug']); ?>"><?php echo htmlspecialchars($post['category_name']); ?></a></li>
                    <li class="trail-end"><?php echo htmlspecialchars($post['title']); ?></li>
                </ul>
            </nav>

            <article class="post">
                <header class="entry-header">
                    <div class="cat-links">
                        <a href="category.php?slug=<?php echo htmlspecialchars($post['category_slug']); ?>"><?php echo htmlspecialchars($post['category_name']); ?></a>
                    </div>
                    <h1 class="entry-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                    <div class="entry-meta">
                        <span class="byline"><i class="fa fa-user"></i> <?php echo htmlspecialchars($post['author_name']); ?></span>
                        <span class="posted-on"><i class="fa fa-calendar"></i> <?php echo formatDate($post['created_at']); ?></span>
                        <span class="views"><i class="fa fa-eye"></i> <?php echo $post['views']; ?> Views</span>
                    </div>
                </header>

                <?php if ($post['featured_image']): ?>
                <div class="post-thumbnail">
                    <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                </div>
                <?php endif; ?>

                <div class="entry-content">
                    <?php echo nl2br(htmlspecialchars_decode($post['content'])); ?>
                </div>
            </article>
        </main>

        <aside id="secondary" class="widget-area">
            <section class="widget">
                <h2 class="widget-title">Recent Posts</h2>
                <ul>
                    <?php foreach ($recentPosts as $recent): ?>
                    <li>
                        <a href="single.php?slug=<?php echo htmlspecialchars($recent['slug']); ?>"><?php echo htmlspecialchars($recent['title']); ?></a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </aside>
    </div>
</div>

<?php include_once __DIR__ . '/footer.php'; ?>
