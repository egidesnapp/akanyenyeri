<?php
/**
 * Category Archive Template for Akanyenyeri Magazine
 */

require_once 'config/database.php';

// Get database connection
$pdo = getDB();

// Get category slug
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

// Fetch category details
$category = null;
if ($slug) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle error silently
    }
}

// If category not found, redirect to home
if (!$category) {
    header("Location: index.php");
    exit;
}

// Fetch posts for this category
function getCategoryPosts($pdo, $categoryId) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.full_name as author_name
            FROM posts p
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.category_id = ? AND p.status = 'published'
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}
$posts = getCategoryPosts($pdo, $category['id']);

// Fetch all categories for menu
function getCategories($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}
$categories = getCategories($pdo);

// Helper date function
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - Akanyenyeri Magazine</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Text:wght@400;600;700&family=Inter:wght@400;500;600;700&family=Mulish:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="header/header.css">
    <link rel="stylesheet" href="css/visibility-improvements.css">
    <link rel="stylesheet" href="css/frontend-enhancements.css">
    
    <style>
        .archive-header { margin-bottom: 2rem; border-bottom: 1px solid #eee; padding-bottom: 1rem; }
        .archive-title { font-size: 2rem; color: var(--text-main); }
        .archive-description { color: #777; margin-top: 0.5rem; }
        
        .post-list { display: flex; flex-direction: column; gap: 2rem; }
        .post-item { display: flex; gap: 1.5rem; border-bottom: 1px solid #eee; padding-bottom: 2rem; }
        .post-item:last-child { border-bottom: none; }
        .post-item-thumbnail { width: 300px; flex-shrink: 0; }
        .post-item-thumbnail img { width: 100%; height: 200px; object-fit: cover; border-radius: 6px; }
        .post-item-content { flex: 1; }
        .post-item-title { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .post-item-title a { color: var(--text-main); text-decoration: none; transition: color 0.3s; }
        .post-item-title a:hover { color: var(--color-primary); }
        .post-item-meta { color: #888; font-size: 0.9rem; margin-bottom: 1rem; }
        .post-item-excerpt { color: var(--text-main); line-height: 1.6; }
        
        @media (max-width: 768px) {
            .post-item { flex-direction: column; }
            .post-item-thumbnail { width: 100%; }
        }
    </style>
</head>
<body class="archive category right-sidebar rectified-magazine-fontawesome-version-6">
    <div id="page" class="site">
        <header id="masthead" class="site-header">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="container-inner">
                    <div class="top-left-col">
                        <span class="ct-clock"><i class="fa fa-clock"></i> <span id="current-date"><?php echo date('F j, Y'); ?></span></span>
                        <nav class="top-menu">
                            <ul>
                                <li><a href="#">About Us</a></li>
                                <li><a href="contact.php">Contact</a></li>
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
                    </div>
                </div>
            </div>

            <!-- Site Branding -->
            <div class="site-branding">
                <div class="container-inner">
                    <div class="logo-wrapper">
                        <h1 class="site-title"><a href="index.php">Akanyenyeri</a></h1>
                        <p class="site-description">Your Trusted News Source</p>
                    </div>
                </div>
            </div>

            <!-- Primary Navigation -->
            <nav id="site-navigation" class="main-navigation rectified-magazine-header-block">
                <div class="rectified-magazine-menu-container">
                    <div class="container-inner">
                        <div class="main-navigation">
                            <ul id="primary-menu" class="navbar-nav">
                                <li><a href="index.php">Home</a></li>
                                <?php foreach ($categories as $cat): ?>
                                <li class="<?php echo ($cat['slug'] === $slug) ? 'current-menu-item' : ''; ?>">
                                    <a href="category.php?slug=<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></a>
                                </li>
                                <?php endforeach; ?>
                                <li><a href="contact.php">Contact</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <div class="ct-container-main">
            <div class="container-inner clearfix">
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
                                        <?php echo htmlspecialchars(substr($post['excerpt'], 0, 150)) . '...'; ?>
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
        
        <footer class="site-footer">
            <div class="container-inner">
                <div class="footer-copyright">
                    <p>&copy; <?php echo date('Y'); ?> Akanyenyeri Magazine. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
    
    <script src="js/theme.js"></script>
</body>
</html>
