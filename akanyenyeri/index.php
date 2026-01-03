<?php
/**
 * Main Homepage for Akanyenyeri Magazine
 * Dynamic PHP version with database-driven content
 */

require_once 'config/database.php';

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
<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Akanyenyeri Magazine - Your Trusted News Source</title>

        <meta name="description" content="Stay informed with Akanyenyeri Magazine - your trusted source for politics, technology, sports, business, and entertainment news." />
        <meta name="keywords" content="news, politics, technology, sports, business, entertainment, Akanyenyeri" />

        <!-- Open Graph -->
        <meta property="og:title" content="Akanyenyeri Magazine - Your Trusted News Source" />
        <meta property="og:description" content="Stay informed with the latest news and analysis from Akanyenyeri Magazine" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="<?php echo SITE_URL; ?>" />

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Crimson+Text:wght@400;600;700&family=Inter:wght@400;500;600;700&family=Mulish:wght@400;500;600;700&display=swap"
            rel="stylesheet"
        />

        <!-- Font Awesome -->
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        />

        <!-- Slick Carousel -->
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css"
        />
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css"
        />

        <!-- Custom Styles -->
        <link rel="stylesheet" href="header/header.css" />
        <link rel="stylesheet" href="css/visibility-improvements.css" />
        <link rel="stylesheet" href="css/frontend-enhancements.css" />
        <link rel="stylesheet" href="css/redesign.css" />
        
        <!-- AOS Animation Library -->
        <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    </head>
    <body class="right-sidebar rectified-magazine-fontawesome-version-6">

        <div id="page" class="site">
        <!-- Header -->
        <header id="masthead" class="site-header">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="container-inner">
                    <div class="top-left-col">
                        <span class="ct-clock"><i class="fa fa-clock"></i> <span id="current-date"></span></span>
                        <nav class="top-menu">
                            <ul>
                                <li><a href="about.php">About Us</a></li>
                                <li><a href="contact.php">Contact</a></li>
                                <li><a href="privacy.php">Privacy Policy</a></li>
                            </ul>
                        </nav>
                    </div>
                    <div class="top-right-col">
                        <div class="theme-switch-wrapper">
                            <label class="theme-switch" for="checkbox">
                                <input type="checkbox" id="checkbox" />
                                <div class="slider round">
                                    <i class="fa fa-sun"></i>
                                    <i class="fa fa-moon"></i>
                                    <div class="slider-toggle"></div>
                                </div>
                            </label>
                        </div>
                        <div class="search-icon-box">
                            <i class="fa fa-search"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Overlay -->
            <div class="top-bar-search">
                <button class="close"></button>
                <form class="search-form" action="search.php">
                    <input type="search" name="q" placeholder="Type to search..." />
                    <input type="submit" value="Search" />
                </form>
            </div>

            <!-- Site Branding -->
            <div class="site-branding">
                <div class="container-inner">
                    <div class="logo-wrapper float-left">
                        <h1 class="site-title">
                            <a href="index.php"><i class="fa fa-star" style="color: var(--color-primary);"></i> Akanyenyeri</a>
                        </h1>
                        <p class="site-description">
                            Your Trusted News Source
                        </p>
                    </div>
                    <div class="logo-right-wrapper float-right">
                         <!-- Advertisement or Banner -->
                    </div>
                </div>
            </div>

            <!-- Primary Navigation -->
            <nav id="site-navigation" class="main-navigation rectified-magazine-header-block">
                <div class="rectified-magazine-menu-container">
                    <div class="container-inner ct-show-search">
                        <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                            <span></span>
                        </button>
                        <div class="main-navigation">
                            <ul id="primary-menu" class="navbar-nav">
                                <li class="current-menu-item"><a href="index.php">Home</a></li>
                                <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                                <li><a href="category.php?slug=<?php echo htmlspecialchars($category['slug']); ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                                <?php endforeach; ?>
                                <li><a href="contact.php">Contact</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Breaking News Ticker -->
        <div class="breaking-news-bar">
            <div class="container-inner">
                <div class="breaking-title"><i class="fa fa-bolt"></i> Trending</div>
                <div class="breaking-ticker-wrapper">
                    <div class="js-marquee breaking-ticker-content">
                        <?php foreach ($recentPosts as $post): ?>
                            <span class="ticker-item">
                                <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="site-content">
            <div class="container-inner">
                <?php if (!empty($featuredPosts)): ?>
                <!-- Enhanced Featured Section (Bento Grid) -->
                <div class="enhanced-featured">
                    <!-- Main Featured Article -->
                    <?php if (isset($featuredPosts[0])): $post = $featuredPosts[0]; ?>
                    <div class="main-featured-article" data-aos="fade-up">
                        <img 
                            src="<?php echo htmlspecialchars($post['featured_image'] ?: 'https://via.placeholder.com/800x600'); ?>" 
                            alt="<?php echo htmlspecialchars($post['title']); ?>"
                            class="main-featured-img"
                        />
                        <div class="main-featured-content">
                            <span class="category-badge"><?php echo htmlspecialchars($post['category_name']); ?></span>
                            <h2 class="main-featured-title">
                                <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                            </h2>
                            <div class="post-meta" style="color: rgba(255,255,255,0.8);">
                                <span class="posted-on"><i class="fa fa-calendar"></i> <?php echo formatDate($post['created_at']); ?></span>
                                <span class="byline" style="margin-left: 10px;"><i class="fa fa-user"></i> <?php echo htmlspecialchars($post['author_name']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Side Featured Articles -->
                    <div class="featured-sidebar" data-aos="fade-left" data-aos-delay="200">
                        <?php for($i=1; $i<=3; $i++): if(isset($featuredPosts[$i])): $post = $featuredPosts[$i]; ?>
                        <div class="featured-card">
                            <img 
                                src="<?php echo htmlspecialchars($post['featured_image'] ?: 'https://via.placeholder.com/400x300'); ?>" 
                                alt="<?php echo htmlspecialchars($post['title']); ?>"
                                class="featured-card-img"
                            />
                            <div class="featured-card-content">
                                <span class="category-badge" style="font-size: 0.7rem; padding: 2px 8px;"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                <h3 class="featured-card-title">
                                    <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                                </h3>
                                <div class="post-meta" style="font-size: 0.8rem; color: var(--text-muted);">
                                    <span class="posted-on"><i class="fa fa-clock"></i> <?php echo timeAgo($post['created_at']); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endif; endfor; ?>
                    </div>
                </div>
                <?php endif; ?>

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
    <script src="js/theme.js"></script>
</body>
</html>
