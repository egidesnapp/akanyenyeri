<?php
require_once __DIR__ . '/../database/config/database.php';

// Get database connection
$pdo = getDB();
$dbConnectionError = $pdo === null;

// Function to get featured posts
function getFeaturedPosts($pdo, $limit = 6) {
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.full_name as author_name, c.name as category_name, c.slug as category_slug, c.color as category_color
            FROM posts p
            LEFT JOIN users u ON p.author_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'published'
            ORDER BY p.is_featured DESC, p.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Function to get categories with post counts
function getCategoriesWithCounts($pdo) {
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT c.*, COUNT(p.id) as post_count
            FROM categories c
            LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published'
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Function to format date
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

// Function to estimate read time
function estimateReadTime($content, $wordsPerMinute = 200) {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = ceil($wordCount / $wordsPerMinute);
    return $minutes . ' min read';
}

// Get featured posts and categories
$featured_posts = getFeaturedPosts($pdo, 6);
$categories = getCategoriesWithCounts($pdo);

// Get active advertisements for hero section
function getActiveAdvertisements($pdo) {
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM advertisements
            WHERE is_active = 1
            AND (start_date IS NULL OR start_date <= NOW())
            AND (end_date IS NULL OR end_date >= NOW())
            ORDER BY display_order ASC, created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}
$advertisements = getActiveAdvertisements($pdo);
?>

<?php include 'includes/head.php'; ?>
<?php include 'includes/nav.php'; ?>


    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <!-- Advertisement Background Images -->
        <div class="hero-advertisements" id="heroAdvertisements">
            <?php if (!empty($advertisements)): ?>
                <?php foreach ($advertisements as $index => $ad): ?>
                <div class="hero-ad-slide <?php echo $index === 0 ? 'active' : ''; ?>" style="background-image: url('<?php echo htmlspecialchars($ad['image_path']); ?>');">
                    <?php if (!empty($ad['link_url'])): ?>
                    <a href="<?php echo htmlspecialchars($ad['link_url']); ?>" class="hero-ad-link" target="_blank" rel="noopener noreferrer"></a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default background if no advertisements -->
                <div class="hero-ad-slide active" style="background: var(--hero-bg);"></div>
            <?php endif; ?>
        </div>

        <!-- Advertisement Navigation Dots -->
        <?php if (!empty($advertisements) && count($advertisements) > 1): ?>
        <div class="hero-ad-dots">
            <?php foreach ($advertisements as $index => $ad): ?>
            <button class="hero-ad-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Hero Content -->
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title fade-in" data-aos="fade-up">Urakazaneza ku AKANYENYERI</h1>
                <p class="hero-subtitle typewriter" data-aos="fade-up" data-aos-delay="200">
                    Your trusted source for breaking news, in-depth analysis, and compelling stories from around the globe.
                </p>
                <a href="#news" class="btn-custom fade-in" data-aos="fade-up" data-aos-delay="400">
                    <img src="../logo/akanyenyeri logo.png" alt="Akanyenyeri Logo" style="height: 20px; width: auto; margin-right: 8px;">Explore Latest News
                </a>
            </div>
        </div>
    </section>



    <!-- Featured News -->
    <section class="featured-news" id="news">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">AKANYENYERI NEWS</h2>

            <?php if (empty($featured_posts)): ?>
            <div class="text-center py-5">
                <img src="../logo/akanyenyeri logo.png" alt="Akanyenyeri Logo" style="height: 80px; width: auto; margin-bottom: 1rem; opacity: 0.5;">
                <h3>No posts available</h3>
                <p>Please check back later for the latest news.</p>
            </div>
            <?php else: ?>

            <!-- Special Layout for First Three Cards -->
            <div class="row mb-5">

                <!-- First Article - Large (Half Page) -->
                <div class="col-lg-6 mb-4" data-aos="fade-up">
                    <?php $post = $featured_posts[0]; ?>
                    <div class="article-large">
                        <img src="<?php echo htmlspecialchars($post['featured_image'] ?: 'https://via.placeholder.com/800x400/3498db/ffffff?text=No+Image'); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width: 100%; height: 350px; object-fit: cover; border-radius: 15px; margin-bottom: 1rem;">
                        <div class="article-content">
                            <div class="mb-2">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                <small class="text-muted ms-2">
                                    <i class="fas fa-calendar-alt me-1"></i><?php echo formatDate($post['created_at']); ?> |
                                    <i class="fas fa-clock me-1"></i><?php echo estimateReadTime($post['content']); ?>
                                </small>
                            </div>
                            <h4 class="article-title animated-heading" style="color: <?php echo htmlspecialchars($post['category_color'] ?: '#2563eb'); ?>;"><?php echo htmlspecialchars($post['title']); ?></h4>
                            <p class="article-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                            <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="btn btn-custom">
                                <i class="fas fa-arrow-right me-2"></i>Read More
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Second and Third Cards - Stacked on Right Side -->
                <div class="col-lg-6 d-flex flex-column" style="gap: 1rem;">
                    <!-- Second Article -->
                    <div class="flex-fill" style="flex: 1 1 45%;" data-aos="fade-up" data-aos-delay="200">
                        <?php $post = $featured_posts[1]; ?>
                        <div class="article-horizontal">
                            <div class="row g-0 h-100">
                                <div class="col-md-5 p-0">
                                    <img src="<?php echo htmlspecialchars($post['featured_image'] ?: 'https://via.placeholder.com/400x300/27ae60/ffffff?text=No+Image'); ?>" class="w-100 h-100" alt="<?php echo htmlspecialchars($post['title']); ?>" style="object-fit: cover; border-radius: 15px 0 0 15px;">
                                </div>
                                <div class="col-md-7 d-flex">
                                    <div class="article-body p-3 w-100">
                                        <div class="mb-2">
                                            <span class="badge bg-success"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                        </div>
                    <h6 class="article-title animated-heading mb-2" style="font-size: 0.95rem; line-height: 1.3; color: <?php echo htmlspecialchars($post['category_color'] ?: '#2563eb'); ?>;"><?php echo htmlspecialchars($post['title']); ?></h6>
                    <p class="article-excerpt small mb-2" style="font-size: 0.8rem; line-height: 1.4;"><?php echo htmlspecialchars(substr($post['excerpt'], 0, 60)) . (strlen($post['excerpt']) > 60 ? '...' : ''); ?></p>
                    <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="btn btn-custom btn-sm" style="font-size: 0.75rem; padding: 0.4rem 0.8rem;">
                        <i class="fas fa-arrow-right me-1"></i>Read More
                    </a>
                    <small class="text-muted" style="font-size: 0.7rem; margin-top: 0.5rem;">
                        <i class="fas fa-clock me-1"></i><?php echo estimateReadTime($post['content']); ?>
                    </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Third Article -->
                    <div class="flex-fill" style="flex: 1 1 45%;" data-aos="fade-up" data-aos-delay="400">
                        <?php $post = $featured_posts[2]; ?>
                        <div class="article-horizontal">
                            <div class="row g-0 h-100">
                                <div class="col-md-5 p-0">
                                    <img src="<?php echo htmlspecialchars($post['featured_image'] ?: 'https://via.placeholder.com/400x300/17a2b8/ffffff?text=No+Image'); ?>" class="w-100 h-100" alt="<?php echo htmlspecialchars($post['title']); ?>" style="object-fit: cover; border-radius: 15px 0 0 15px;">
                                </div>
                                <div class="col-md-7 d-flex">
                                    <div class="article-body p-3 w-100">
                                        <div class="mb-2">
                                            <span class="badge bg-info"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                        </div>
                                        <h6 class="article-title animated-heading mb-2" style="font-size: 0.95rem; line-height: 1.3; color: <?php echo htmlspecialchars($post['category_color'] ?: '#2563eb'); ?>;"><?php echo htmlspecialchars($post['title']); ?></h6>
                                        <p class="article-excerpt small mb-2" style="font-size: 0.8rem; line-height: 1.4;"><?php echo htmlspecialchars(substr($post['excerpt'], 0, 60)) . (strlen($post['excerpt']) > 60 ? '...' : ''); ?></p>
                                        <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="btn btn-custom btn-sm" style="font-size: 0.75rem; padding: 0.4rem 0.8rem;">
                                            <i class="fas fa-arrow-right me-1"></i>Read More
                                        </a>
                                        <small class="text-muted" style="font-size: 0.7rem; margin-top: 0.5rem;">
                                            <i class="fas fa-clock me-1"></i><?php echo estimateReadTime($post['content']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remaining Articles - Standard Grid -->
            <div class="row">
                <?php for($i = 3; $i < count($featured_posts); $i++): $post = $featured_posts[$i]; ?>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="<?php echo ($i - 2) * 100; ?>">
                    <div class="article-grid">
                        <img src="<?php echo htmlspecialchars($post['featured_image'] ?: 'https://via.placeholder.com/400x250/e74c3c/ffffff?text=No+Image'); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 1rem;">
                        <div class="article-content">
                            <div class="mb-2">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                <small class="text-muted ms-2">
                                    <i class="fas fa-calendar-alt me-1"></i><?php echo formatDate($post['created_at']); ?> |
                                    <i class="fas fa-clock me-1"></i><?php echo estimateReadTime($post['content']); ?>
                                </small>
                            </div>
                            <h5 class="article-title animated-heading" style="color: <?php echo htmlspecialchars($post['category_color'] ?: '#2563eb'); ?>;"><?php echo htmlspecialchars($post['title']); ?></h5>
                            <p class="article-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                            <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="btn btn-custom">
                                <i class="fas fa-arrow-right me-2"></i>Read More
                            </a>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="container my-5" id="categories">
        <h2 class="section-title" data-aos="fade-up">Explore by Category</h2>
        <div class="row">
            <?php
            // Define icons and colors for categories
            $categoryIcons = [
                'Politics' => 'fas fa-landmark',
                'Sports' => 'fas fa-futbol',
                'Technology' => 'fas fa-laptop-code',
                'Business' => 'fas fa-chart-line',
                'Entertainment' => 'fas fa-film',
                'Health' => 'fas fa-heartbeat'
            ];

            $categoryColors = [
                'Politics' => 'primary',
                'Sports' => 'warning',
                'Technology' => 'info',
                'Business' => 'success',
                'Entertainment' => 'danger',
                'Health' => 'secondary'
            ];

            if (!empty($categories)):
                foreach ($categories as $index => $category):
                    $icon = $categoryIcons[$category['name']] ?? 'fas fa-folder';
                    $color = $categoryColors[$category['name']] ?? 'primary';
            ?>
            <div class="col-lg-4 col-md-6 mb-4" data-aos="zoom-in" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="category-item text-center">
                    <div class="category-content">
                        <div class="mb-3">
                            <i class="<?php echo $icon; ?> fa-3x text-<?php echo $color; ?>"></i>
                        </div>
                        <h5 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                        <p class="category-count"><?php echo $category['post_count']; ?> articles</p>
                        <a href="category.php?slug=<?php echo htmlspecialchars($category['slug']); ?>" class="btn btn-outline-<?php echo $color; ?>">
                            <i class="fas fa-arrow-right me-2"></i>Explore
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="col-12 text-center">
                <p>No categories available.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="slide-in-left">
                    <h3 class="mb-4">Stay Updated with Latest News</h3>
                    <p class="mb-4">Subscribe to our newsletter and get the latest breaking news delivered directly to your inbox.</p>
                    <form class="newsletter-form" id="newsletterForm">
                        <input type="email" class="form-control" placeholder="Enter your email address" required>
                        <button type="submit" class="btn btn-custom w-100">
                            <i class="fas fa-envelope me-2"></i>Subscribe Now
                        </button>
                    </form>
                </div>
                <div class="col-lg-6" data-aos="slide-in-right">
                <div class="text-center">
                        <img src="../logo/akanyenyeri logo.png" alt="Akanyenyeri Logo" style="height: 120px; width: auto; margin-bottom: 1rem;">
                        <h4>Breaking News Alert</h4>
                        <p>Get instant notifications for major stories</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>
