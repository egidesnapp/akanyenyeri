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
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
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

// Get active hero content image
function getActiveHeroContent($pdo) {
    if (!$pdo) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM advertisements
            WHERE type = 'content' AND is_active = 1
            AND (start_date IS NULL OR start_date <= NOW())
            AND (end_date IS NULL OR end_date >= NOW())
            ORDER BY display_order ASC, created_at DESC
            LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}
$hero_content = getActiveHeroContent($pdo);
?>

<?php include 'includes/head.php'; ?>

<!-- Categories Sidebar Styles -->
<style>
    .categories-sidebar {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 40px);
        overflow-y: auto;
    }

    .category-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .category-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .category-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .category-header {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 1rem;
    }

    .category-icon {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .category-card:hover .category-icon {
        transform: scale(1.1);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    .category-icon i {
        font-size: 1.5rem;
    }

    .category-info {
        flex: 1;
        min-width: 0;
    }

    .category-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0 0 0.25rem 0;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .category-count {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 500;
    }

    .category-action {
        display: flex;
        align-items: center;
    }

    .btn-outline-custom {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        background: transparent;
    }

    .btn-outline-custom:hover {
        transform: scale(1.1);
        background: rgba(255, 255, 255, 0.9);
    }

    .category-progress {
        height: 4px;
        background: #e2e8f0;
        border-radius: 2px;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        transition: width 0.5s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Permanent Dark Theme */
    .categories-sidebar {
        background: rgba(30, 41, 59, 0.9);
        border-color: rgba(255, 255, 255, 0.1);
    }

    .category-card {
        background: #1e293b;
        border-color: rgba(255, 255, 255, 0.1);
    }

    .category-icon {
        background: rgba(15, 23, 42, 0.9);
    }

    .category-title {
        color: #f8fafc;
    }

    .category-progress {
        background: #334155;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .categories-sidebar {
            position: static;
            max-height: none;
            margin-top: 2rem;
        }
    }

    @media (max-width: 768px) {
        .category-header {
            gap: 1rem;
        }
        
        .category-icon {
            width: 50px;
            height: 50px;
        }
        
        .category-icon i {
            font-size: 1.25rem;
        }
        
        .category-title {
            font-size: 1rem;
        }
    }
</style>


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
                <?php if ($hero_content): ?>
                    <!-- Custom Hero Content Image -->
                    <div class="hero-custom-image fade-in" data-aos="fade-up">
                        <img src="<?php echo SITE_URL . htmlspecialchars($hero_content['image_path']); ?>" alt="<?php echo htmlspecialchars($hero_content['title']); ?>" style="max-width: 100%; max-height: 300px; object-fit: contain; margin-bottom: 2rem;">
                    </div>
                <?php else: ?>
                    <!-- Default Hero Text -->
                    <h1 class="hero-title fade-in" data-aos="fade-up">Urakazaneza ku AKANYENYERI</h1>
                    <p class="hero-subtitle typewriter" data-aos="fade-up" data-aos-delay="200">
                        Your trusted source for breaking news, in-depth analysis, and compelling stories from around the globe.
                    </p>
                <?php endif; ?>
                <a href="#news" class="btn-custom fade-in" data-aos="fade-up" data-aos-delay="400">
                    <img src="<?php echo SITE_URL; ?>logo/akanyenyeri_logo.png" alt="Akanyenyeri Logo" style="height: 20px; width: auto; margin-right: 8px;">Explore Latest News
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
                <img src="../logo/akanyenyeri_logo.png" alt="Akanyenyeri Logo" style="height: 80px; width: auto; margin-bottom: 1rem; opacity: 0.5;">
                <h3>No posts available</h3>
                <p>Please check back later for the latest news.</p>
            </div>
            <?php else: ?>

            <!-- Special Layout for First Three Cards -->
            <div class="row mb-5">

                <!-- First Article - Large (Half Page) -->
                <div class="col-lg-6 mb-4">
                    <?php $post = $featured_posts[0]; ?>
                    <div class="article-large">
                        <img src="<?php echo !empty($post['featured_image']) ? SITE_URL . 'uploads/images/' . htmlspecialchars($post['featured_image']) : 'https://via.placeholder.com/800x400/3498db/ffffff?text=No+Image'; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width: 100%; height: 350px; object-fit: cover; border-radius: 15px; margin-bottom: 1rem;">
                        <div class="article-content">
                            <div class="mb-2">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                <small class="text-muted ms-2">
                                    <i class="fas fa-calendar-alt me-1"></i><?php echo formatDate($post['created_at']); ?> |
                                    <i class="fas fa-clock me-1"></i><?php echo estimateReadTime($post['content']); ?>
                                </small>
                            </div>
                            <h4 class="article-title animated-heading" style="color: #2563eb; cursor: pointer;" onclick="window.location.href='<?php echo SITE_URL; ?>public/single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>'"><?php echo htmlspecialchars($post['title']); ?></h4>
                            <p class="article-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                            <a href="<?php echo SITE_URL; ?>public/single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="btn btn-custom">
                                <i class="fas fa-arrow-right me-2"></i>Read More
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Second and Third Cards - Stacked on Right Side -->
                <div class="col-lg-6 d-flex flex-column" style="gap: 1rem;">
                    <!-- Second Article -->
                    <div class="flex-fill" style="flex: 1 1 45%;">
                        <?php $post = $featured_posts[1]; ?>
                        <div class="article-horizontal">
                            <div class="row g-0 h-100">
                                <div class="col-md-5 p-0">
                                    <img src="<?php echo !empty($post['featured_image']) ? SITE_URL . 'uploads/images/' . htmlspecialchars($post['featured_image']) : 'https://via.placeholder.com/400x300/27ae60/ffffff?text=No+Image'; ?>" class="w-100 h-100" alt="<?php echo htmlspecialchars($post['title']); ?>" style="object-fit: cover; border-radius: 15px 0 0 15px;">
                                </div>
                                <div class="col-md-7 d-flex">
                                    <div class="article-body p-3 w-100">
                                        <div class="mb-2">
                                            <span class="badge bg-success"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                        </div>
                    <h6 class="article-title animated-heading mb-2" style="font-size: 0.95rem; line-height: 1.3; color: #2563eb; cursor: pointer;" onclick="window.location.href='<?php echo SITE_URL; ?>public/single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>'"><?php echo htmlspecialchars($post['title']); ?></h6>
                    <p class="article-excerpt small mb-2" style="font-size: 0.8rem; line-height: 1.4;"><?php echo htmlspecialchars(substr($post['excerpt'], 0, 60)) . (strlen($post['excerpt']) > 60 ? '...' : ''); ?></p>
                    <a href="<?php echo SITE_URL; ?>public/single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="btn btn-custom btn-sm" style="font-size: 0.75rem; padding: 0.4rem 0.8rem;">
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
                    <div class="flex-fill" style="flex: 1 1 45%;">
                        <?php $post = $featured_posts[2]; ?>
                        <div class="article-horizontal">
                            <div class="row g-0 h-100">
                                <div class="col-md-5 p-0">
                                    <img src="<?php echo !empty($post['featured_image']) ? SITE_URL . 'uploads/images/' . htmlspecialchars($post['featured_image']) : 'https://via.placeholder.com/400x300/17a2b8/ffffff?text=No+Image'; ?>" class="w-100 h-100" alt="<?php echo htmlspecialchars($post['title']); ?>" style="object-fit: cover; border-radius: 15px 0 0 15px;">
                                </div>
                                <div class="col-md-7 d-flex">
                                    <div class="article-body p-3 w-100">
                                        <div class="mb-2">
                                            <span class="badge bg-info"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                        </div>
                                        <h6 class="article-title animated-heading mb-2" style="font-size: 0.95rem; line-height: 1.3; color: #2563eb; cursor: pointer;" onclick="window.location.href='<?php echo SITE_URL; ?>public/single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>'"><?php echo htmlspecialchars($post['title']); ?></h6>
                                        <p class="article-excerpt small mb-2" style="font-size: 0.8rem; line-height: 1.4;"><?php echo htmlspecialchars(substr($post['excerpt'], 0, 60)) . (strlen($post['excerpt']) > 60 ? '...' : ''); ?></p>
                        <a href="<?php echo SITE_URL; ?>public/single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="btn btn-custom btn-sm" style="font-size: 0.75rem; padding: 0.4rem 0.8rem;">
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

            <!-- Posts and Categories Layout -->
            <div class="row">
                <!-- Main Content Area -->
                <div class="col-lg-8">
                    <!-- Remaining Articles - Standard Grid -->
                    <div class="row">
                        <?php for($i = 3; $i < count($featured_posts); $i++): $post = $featured_posts[$i]; ?>
                        <div class="col-lg-6 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="<?php echo ($i - 2) * 100; ?>">
                            <div class="article-grid">
                                <img src="<?php echo !empty($post['featured_image']) ? SITE_URL . 'uploads/images/' . htmlspecialchars($post['featured_image']) : 'https://via.placeholder.com/400x250/e74c3c/ffffff?text=No+Image'; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 1rem;">
                                <div class="article-content">
                                    <div class="mb-2">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                        <small class="text-muted ms-2">
                                            <i class="fas fa-calendar-alt me-1"></i><?php echo formatDate($post['created_at']); ?> |
                                            <i class="fas fa-clock me-1"></i><?php echo estimateReadTime($post['content']); ?>
                                        </small>
                                    </div>
                                    <h5 class="article-title animated-heading" style="color: #2563eb; cursor: pointer;" onclick="window.location.href='<?php echo SITE_URL; ?>public/single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>'">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </h5>
                                    <p class="article-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                    <a href="<?php echo SITE_URL; ?>public/single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="btn btn-custom">
                                        <i class="fas fa-arrow-right me-2"></i>Read More
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Load More Button -->
                    <div class="text-center mt-4 mb-5">
                        <button id="loadMoreBtn" type="button" class="btn btn-custom px-5 py-3">
                            <i class="fas fa-spinner fa-spin me-2 d-none" id="loadMoreSpinner"></i>
                            <span id="loadMoreText">Load More News</span>
                        </button>
                    </div>
                </div>

                <!-- Categories Sidebar -->
                <div class="col-lg-4">
                    <div class="categories-sidebar">
                        <h2 class="section-title" data-aos="fade-up">Explore by Category</h2>
                        
                        <?php if (!empty($categories)): ?>
                            <div class="category-list">
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
                                    'Politics' => '#3b82f6',
                                    'Sports' => '#f59e0b',
                                    'Technology' => '#22d3ee',
                                    'Business' => '#22c55e',
                                    'Entertainment' => '#ef4444',
                                    'Health' => '#a78bfa'
                                ];

                                foreach ($categories as $index => $category):
                                    $icon = $categoryIcons[$category['name']] ?? 'fas fa-folder';
                                    $color = $categoryColors[$category['name']] ?? '#64748b';
                                ?>
                                <div class="category-card" data-aos="fade-left" data-aos-delay="<?php echo $index * 50; ?>">
                                    <div class="category-header">
                                        <div class="category-icon">
                                            <i class="<?php echo $icon; ?>" style="color: <?php echo $color; ?>;"></i>
                                        </div>
                                        <div class="category-info">
                                            <h6 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h6>
                                            <span class="category-count"><?php echo $category['post_count']; ?> articles</span>
                                        </div>
                                        <div class="category-action">
                                            <a href="<?php echo SITE_URL; ?>public/category.php?slug=<?php echo htmlspecialchars($category['slug']); ?>" class="btn btn-outline-custom" style="border-color: <?php echo $color; ?>; color: <?php echo $color; ?>;">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                                        </div>
                                    </div>
                                    <div class="category-progress">
                                        <div class="progress-bar" style="background-color: <?php echo $color; ?>; width: <?php echo min(100, ($category['post_count'] / max(array_column($categories, 'post_count')) * 100)); ?>%;"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p>No categories available.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
                        <img src="../logo/akanyenyeri_logo.png" alt="Akanyenyeri Logo" style="height: 120px; width: auto; margin-bottom: 1rem;">
                        <h4>Breaking News Alert</h4>
                        <p>Get instant notifications for major stories</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Load More Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            const loadMoreSpinner = document.getElementById('loadMoreSpinner');
            const loadMoreText = document.getElementById('loadMoreText');
            const postsContainer = document.querySelector('.col-lg-8 .row'); // Target the posts grid
            
            let currentPage = 1; // Start from page 1 (initial load covers page 1 logic implicitly, but API handles offset)
            // Actually, initial PHP loads 6 featured posts. API should start fetching from offset 6?
            // The API uses page and limit. PHP loads featured_posts (limit 10).
            // Let's check getFeaturedPosts limit. It's 10.
            // But the loop shows 0, 1, 2, then 3 to count(featured_posts).
            // So we displayed all fetched posts.
            // We should start fetching from page 2?
            // If API page 1 limit 10 returns the *same* posts, we duplicate.
            // We need to know how many posts are already displayed.
            
            let loadedCount = <?php echo count($featured_posts); ?>;
            let page = 2; // Assuming initial load is page 1
            const limit = 6;

            if(loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    // Show star loader animation
                    showStarLoader();
                    
                    // Hide the button spinner and update text
                    loadMoreSpinner.classList.add('d-none');
                    loadMoreText.textContent = 'Loading...';
                    loadMoreBtn.disabled = true;

                    // Fetch more posts
                    // Use a relative path to avoid domain issues
                    const apiUrl = '<?php echo SITE_URL; ?>api/posts.php?page=' + page + '&limit=' + limit;
                    console.log('Fetching posts from:', apiUrl);
                    
                    fetch(apiUrl)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok: ' + response.statusText);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if(data.success && data.posts.length > 0) {
                                data.posts.forEach(post => {
                                    // Check if post is already displayed (by slug or id) to avoid duplicates?
                                    // For simplicity, just append.
                                    
                                    const postHtml = `
                                        <div class="col-lg-6 col-md-6 mb-4" data-aos="fade-up">
                                            <div class="article-grid">
                                                <img src="${post.featured_image ? '<?php echo SITE_URL; ?>uploads/images/' + post.featured_image : 'https://via.placeholder.com/400x250/e74c3c/ffffff?text=No+Image'}" alt="${post.title}" style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 1rem;">
                                                <div class="article-content">
                                                    <div class="mb-2">
                                                        <span class="badge bg-primary">${post.category_name || 'News'}</span>
                                                        <small class="text-muted ms-2">
                                                            <i class="fas fa-calendar-alt me-1"></i>${new Date(post.created_at).toLocaleDateString()}
                                                        </small>
                                                    </div>
                                                    <h5 class="article-title animated-heading" style="color: #2563eb; cursor: pointer;" onclick="window.location.href='<?php echo SITE_URL; ?>public/single.php?slug=${post.slug}'">
                                                        ${post.title}
                                                    </h5>
                                                    <p class="article-excerpt">${post.excerpt ? post.excerpt.substring(0, 100) + '...' : ''}</p>
                                                    <a href="<?php echo SITE_URL; ?>public/single.php?slug=${post.slug}" class="btn btn-custom">
                                                        <i class="fas fa-arrow-right me-2"></i>Read More
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                    postsContainer.insertAdjacentHTML('beforeend', postHtml);
                                });
                                
                                page++;
                                loadedCount += data.posts.length;
                            } else {
                                loadMoreBtn.textContent = 'No More Posts';
                                loadMoreBtn.disabled = true;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            loadMoreText.textContent = 'Error Loading';
                        })
                        .finally(() => {
                            // Hide star loader animation
                            hideStarLoader();
                            
                            if(loadMoreBtn.textContent !== 'No More Posts') {
                                loadMoreSpinner.classList.add('d-none');
                                loadMoreText.textContent = 'Load More News';
                                loadMoreBtn.disabled = false;
                            }
                        });
                });
            }
        });
    </script>
<?php include 'includes/footer.php'; ?>
