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

<?php include 'includes/head.php'; ?>
<?php include 'includes/nav.php'; ?>

    <!-- Single Post Section -->
    <section class="single-post-section" style="padding: 6rem 0 3rem; background: var(--featured-bg);">
        <div class="container">
            <!-- Breadcrumbs -->
            <nav class="breadcrumbs mb-4" style="background: rgba(255,255,255,0.95); padding: 1rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <ul class="trail-items" style="list-style: none; padding: 0; margin: 0; display: flex; align-items: center;">
                    <li style="margin-right: 0.5rem;"><a href="index.php" style="color: var(--primary-color); text-decoration: none;"><i class="fa fa-home"></i> Home</a></li>
                    <li style="margin-right: 0.5rem;"><a href="category.php?slug=<?php echo htmlspecialchars($post['category_slug']); ?>" style="color: var(--primary-color); text-decoration: none;"><?php echo htmlspecialchars($post['category_name']); ?></a></li>
                    <li class="trail-end" style="color: #374151; font-weight: 500;"><?php echo htmlspecialchars($post['title']); ?></li>
                </ul>
            </nav>

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <article class="single-post card" style="border: none; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.1); background: white; padding: 2rem;" data-aos="fade-up">
                        <header class="post-header mb-4">
                            <div class="mb-3">
                                <span class="badge bg-primary" style="font-size: 0.9rem; padding: 0.5rem 1rem;">Sports</span>
                            </div>
                            <h1 class="post-title" style="font-family: 'Playfair Display', serif; font-size: 2.5rem; font-weight: 700; color: #1f2937; line-height: 1.2; margin-bottom: 1rem;"><?php echo htmlspecialchars($post['title']); ?></h1>
                            <div class="post-meta" style="display: flex; align-items: center; gap: 1rem; color: #6b7280; font-size: 0.9rem;">
                                <span><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($post['author_name']); ?></span>
                                <span><i class="fas fa-calendar me-1"></i><?php echo formatDate($post['created_at']); ?></span>
                                <span><i class="fas fa-eye me-1"></i><?php echo $post['views']; ?> Views</span>
                            </div>
                        </header>

                        <?php if ($post['featured_image']): ?>
                        <div class="post-thumbnail mb-4">
                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="img-fluid rounded" style="width: 100%; height: auto; max-height: 500px; object-fit: cover; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                        </div>
                        <?php endif; ?>

                        <div class="post-content" style="font-size: 1.1rem; line-height: 1.8; color: #374151;">
                            <?php echo nl2br(htmlspecialchars_decode($post['content'])); ?>
                        </div>
                    </article>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <aside class="sidebar">
                        <!-- Recent Posts Widget -->
                        <div class="card mb-4" style="border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); background: white;" data-aos="fade-up" data-aos-delay="200">
                            <div class="card-body">
                                <h5 class="card-title" style="font-family: 'Playfair Display', serif; font-weight: 600; color: #1f2937; margin-bottom: 1rem;">Recent Posts</h5>
                                <ul class="list-unstyled">
                                    <?php foreach ($recentPosts as $recent): ?>
                                    <li class="mb-3" style="border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">
                                        <a href="single.php?slug=<?php echo htmlspecialchars($recent['slug']); ?>" style="text-decoration: none; color: #374151; font-weight: 500; display: block; transition: color 0.3s ease;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='#374151'"><?php echo htmlspecialchars($recent['title']); ?></a>
                                        <small style="color: #6b7280;"><?php echo formatDate($recent['created_at']); ?></small>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>
