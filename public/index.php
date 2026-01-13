<?php
/**
 * Main Homepage for Akanyenyeri Magazine
 * Dynamic PHP version with database-driven content
 */



require_once __DIR__ . '/../database/config/database.php';

// Get database connection
$pdo = getDB();

// Fetch featured posts
function getFeaturedPosts($pdo, $limit = 3) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.title, p.slug, p.excerpt, p.featured_image, p.created_at, p.views,
                   u.full_name as author_name, c.name as category_name, c.color as category_color
            FROM posts p
            LEFT JOIN users u ON p.author_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'published' AND p.is_featured = 1
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Fetch recent posts
function getRecentPosts($pdo, $limit = 6) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.title, p.slug, p.excerpt, p.featured_image, p.created_at, p.views,
                   u.full_name as author_name, c.name as category_name, c.color as category_color
            FROM posts p
            LEFT JOIN users u ON p.author_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'published'
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Fetch popular posts
function getPopularPosts($pdo, $limit = 4) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.title, p.slug, p.excerpt, p.featured_image, p.created_at, p.views,
                   u.full_name as author_name, c.name as category_name, c.color as category_color
            FROM posts p
            LEFT JOIN users u ON p.author_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'published'
            ORDER BY p.views DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Fetch categories
function getCategories($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT c.*, COUNT(p.id) as post_count
            FROM categories c
            LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published'
            GROUP BY c.id
            ORDER BY post_count DESC, c.name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Get data
$featuredPosts = getFeaturedPosts($pdo, 3);
$recentPosts = getRecentPosts($pdo, 6);
$popularPosts = getPopularPosts($pdo, 4);
$categories = getCategories($pdo);

// Helper functions
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    return formatDate($datetime);
}

function excerpt($text, $length = 150) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, strrpos(substr($text, 0, $length), ' ')) . '...';
}
?>

<?php include_once __DIR__ . '/header.php'; ?>

<div class="site-content">
    <div class="container-inner">
        <!-- Magazine-Style Recent Posts Layout -->
        <div class="magazine-hero" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 50px;">
            <!-- Left: Main Recent Post (Large Square) -->
            <div class="main-recent-post" data-aos="fade-right" style="position: relative; overflow: hidden; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <?php if (!empty($recentPosts) && isset($recentPosts[0])): $post = $recentPosts[0]; ?>
                <div class="post-image-wrapper" style="position: relative; height: 400px; overflow: hidden;">
                    <img
                        src="<?php echo htmlspecialchars($post['featured_image'] ?: 'https://via.placeholder.com/600x400/3498db/ffffff?text=Latest+News'); ?>"
                        alt="<?php echo htmlspecialchars($post['title']); ?>"
                        style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;"
                    />
                    <div class="image-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(45deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 100%);"></div>
                </div>
                <div class="post-content-overlay" style="position: absolute; bottom: 0; left: 0; right: 0; padding: 30px; color: white;">
                    <span class="category-badge" style="background: <?php echo htmlspecialchars($post['category_color'] ?: '#3498db'); ?>; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; margin-bottom: 15px; display: inline-block; animation: slideInUp 0.8s ease-out;"><?php echo htmlspecialchars($post['category_name']); ?></span>
                    <h2 class="animated-title" style="font-size: 2.2rem; font-weight: 700; margin: 0 0 15px 0; line-height: 1.2; animation: slideInUp 1s ease-out; animation-delay: 0.2s; opacity: 0; animation-fill-mode: forwards;">
                        <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" style="color: white; text-decoration: none; transition: color 0.3s ease;">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </a>
                    </h2>
                    <div class="post-meta" style="font-size: 0.9rem; opacity: 0.9; animation: slideInUp 1s ease-out; animation-delay: 0.4s; opacity: 0; animation-fill-mode: forwards;">
                        <span><i class="fa fa-calendar"></i> <?php echo formatDate($post['created_at']); ?></span>
                        <span style="margin-left: 15px;"><i class="fa fa-user"></i> <?php echo htmlspecialchars($post['author_name']); ?></span>
                        <span style="margin-left: 15px;"><i class="fa fa-eye"></i> <?php echo number_format($post['views']); ?> views</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right: Two Recent Posts (Smaller Squares) -->
            <div class="recent-posts-sidebar" style="display: flex; flex-direction: column; gap: 30px;">
                <?php for($i=1; $i<=2; $i++): if(!empty($recentPosts) && isset($recentPosts[$i])): $post = $recentPosts[$i]; ?>
                <div class="recent-square-post" data-aos="fade-left" data-aos-delay="<?php echo ($i-1) * 200; ?>" style="position: relative; overflow: hidden; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); height: 185px;">
                    <div class="post-image-wrapper" style="position: relative; height: 100%; overflow: hidden;">
                        <img
                            src="<?php echo htmlspecialchars($post['featured_image'] ?: 'https://via.placeholder.com/300x200/' . substr($post['category_color'] ?: '#3498db', 1) . '/ffffff?text=News'); ?>"
                            alt="<?php echo htmlspecialchars($post['title']); ?>"
                            style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;"
                        />
                        <div class="image-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(45deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.2) 100%);"></div>
                    </div>
                    <div class="post-content-overlay" style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; color: white;">
                        <span class="category-badge" style="background: <?php echo htmlspecialchars($post['category_color'] ?: '#3498db'); ?>; padding: 3px 10px; border-radius: 15px; font-size: 0.7rem; font-weight: 600; margin-bottom: 10px; display: inline-block; animation: slideInUp 0.8s ease-out; animation-delay: <?php echo 0.2 + ($i-1) * 0.2; ?>s; opacity: 0; animation-fill-mode: forwards;"><?php echo htmlspecialchars($post['category_name']); ?></span>
                        <h3 class="animated-title" style="font-size: 1.1rem; font-weight: 600; margin: 0; line-height: 1.3; animation: slideInUp 0.8s ease-out; animation-delay: <?php echo 0.4 + ($i-1) * 0.2; ?>s; opacity: 0; animation-fill-mode: forwards;">
                            <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" style="color: white; text-decoration: none; transition: color 0.3s ease;">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h3>
                        <div class="post-meta" style="font-size: 0.8rem; opacity: 0.8; margin-top: 8px; animation: slideInUp 0.8s ease-out; animation-delay: <?php echo 0.6 + ($i-1) * 0.2; ?>s; opacity: 0; animation-fill-mode: forwards;">
                            <span><i class="fa fa-clock"></i> <?php echo timeAgo($post['created_at']); ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; endfor; ?>
            </div>
        </div>

        <div class="row" style="display: flex; flex-wrap: wrap; gap: 30px; margin-top: 50px;">
            <!-- Main Content Column -->
            <div class="col-lg-8" style="flex: 2; min-width: 300px;">
                <!-- Recent Posts -->
                <section class="recent-posts">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;">
                        <h2 class="section-title" style="margin: 0; font-size: 1.8rem;">Latest News</h2>
                        <a href="archive.php" style="font-weight: 600;">View All <i class="fa fa-arrow-right"></i></a>
                    </div>

                    <div class="posts-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px;">
                        <?php foreach ($recentPosts as $index => $post): ?>
                        <article class="post-item" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                            <div class="post-img-wrap">
                                <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                                    <img src="<?php echo htmlspecialchars($post['featured_image'] ?: 'https://via.placeholder.com/400x200'); ?>" class="post-img" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                </a>
                            </div>
                            <div class="post-content">
                                <div class="post-header">
                                    <span class="category-badge" style="font-size: 0.75rem; margin-bottom: 10px;"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                    <h3 class="post-title">
                                        <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h3>
                                </div>
                                <div class="post-excerpt">
                                    <?php echo htmlspecialchars(excerpt($post['excerpt'] ?: strip_tags($post['title']), 100)); ?>
                                </div>
                                <div class="post-footer" style="margin-top: auto; display: flex; justify-content: space-between; align-items: center;">
                                    <div class="post-meta" style="font-size: 0.85rem; color: var(--text-muted);">
                                        <span><i class="fa fa-clock"></i> <?php echo timeAgo($post['created_at']); ?></span>
                                    </div>
                                    <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="read-more-btn">Read More <i class="fa fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4" style="flex: 1; min-width: 300px;">
                <aside class="sidebar">
                    <!-- Popular Posts -->
                    <?php if (!empty($popularPosts)): ?>
                    <section class="popular-posts" data-aos="fade-left" data-aos-delay="200">
                        <h3 class="sidebar-title">Most Popular</h3>
                        <?php foreach ($popularPosts as $post): ?>
                        <article class="sidebar-post">
                            <div class="sidebar-post-image" style="background-image: url('<?php echo htmlspecialchars($post['featured_image'] ?: 'https://via.placeholder.com/80x80/' . substr($post['category_color'] ?: '667eea', 1) . '/ffffff?text=' . substr($post['title'], 0, 1)); ?>')"></div>
                            <div class="sidebar-post-content">
                                <h4 class="sidebar-post-title">
                                    <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h4>
                                <div class="sidebar-post-meta">
                                    <span><?php echo timeAgo($post['created_at']); ?></span> •
                                    <span><?php echo number_format($post['views']); ?> views</span>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </section>
                    <?php endif; ?>

                    <!-- Categories -->
                    <?php if (!empty($categories)): ?>
                    <section class="categories-widget" data-aos="fade-left" data-aos-delay="400">
                        <h3 class="sidebar-title">Categories</h3>
                        <div class="categories-grid">
                            <?php foreach ($categories as $category): ?>
                            <div class="category-card" style="border-left-color: <?php echo htmlspecialchars($category['color'] ?: '#667eea'); ?>">
                                <div class="category-name">
                                    <a href="category.php?slug=<?php echo htmlspecialchars($category['slug']); ?>" style="color: inherit; text-decoration: none;">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </a>
                                </div>
                                <div class="category-count"><?php echo $category['post_count']; ?> articles</div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
    </div>
</main>

<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="row">
                <div class="col-md-4">
                    <div class="footer-section">
                        <h3>Akanyenyeri Magazine</h3>
                        <p>Your trusted source for breaking news, in-depth analysis, and stories that matter. Stay informed with our daily coverage of politics, technology, sports, and more.</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="footer-section">
                        <h4>Categories</h4>
                        <ul class="footer-links">
                            <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                            <li><a href="category.php?slug=<?php echo htmlspecialchars($category['slug']); ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="footer-section">
                        <h4>Quick Links</h4>
                        <ul class="footer-links">
                            <li><a href="index.php">Home</a></li>
                            <li><a href="about.php">About Us</a></li>
                            <li><a href="contact.php">Contact</a></li>
                            <li><a href="privacy.php">Privacy Policy</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="footer-section">
                        <h4>Stay Connected</h4>
                        <p>Follow us for the latest updates and breaking news.</p>
                        <div class="social-links">
                            <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> Akanyenyeri Magazine. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p>Built with ❤️ for informed readers</p>
                </div>
            </div>
        </div>
    </div>
</footer>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        easing: 'ease-out-cubic',
        once: true,
        offset: 50
    });
</script>
<script src="../assets/js/theme.js"></script>
</body>
</html>
