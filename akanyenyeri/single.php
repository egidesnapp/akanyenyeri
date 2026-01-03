<?php
/**
 * Single Post Template for Akanyenyeri Magazine
 */

require_once 'config/database.php';

// Get database connection
$pdo = getDB();

// Get post slug
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

// Fetch post details
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

        // Increment views
        if ($post) {
            $updateStmt = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
            $updateStmt->execute([$post['id']]);
        }
    } catch (PDOException $e) {
        // Handle error silently
    }
}

// If post not found, redirect to home or show error (simplifying to redirect for now)
if (!$post) {
    header("Location: index.php");
    exit;
}

// Fetch categories for menu
function getCategories($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}
$categories = getCategories($pdo);

// Fetch recent posts for sidebar
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
    } catch (PDOException $e) {
        return [];
    }
}
$recentPosts = getRecentPosts($pdo);

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
    <title><?php echo htmlspecialchars($post['title']); ?> - Akanyenyeri Magazine</title>
    
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
        /* Specific styles for single post */
        .entry-content { font-family: 'Crimson Text', serif; font-size: 1.2rem; line-height: 1.8; color: var(--text-main); }
        .entry-content h2 { font-family: 'Inter', sans-serif; margin-top: 2rem; margin-bottom: 1rem; color: var(--text-main); }
        .entry-content p { margin-bottom: 1.5rem; }
        .entry-content blockquote { border-left: 4px solid var(--color-primary); padding-left: 1.5rem; margin: 2rem 0; font-style: italic; color: #555; }
        [data-theme="dark"] .entry-content blockquote { color: #ccc; }
        .post-thumbnail img { width: 100%; height: auto; border-radius: 8px; margin-bottom: 2rem; }
        .entry-meta { display: flex; gap: 1rem; color: #777; font-size: 0.9rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .entry-meta i { margin-right: 0.3rem; }
        
        /* Sidebar styles */
        .widget { margin-bottom: 2rem; }
        .widget-title { font-size: 1.2rem; border-bottom: 2px solid var(--color-primary); padding-bottom: 0.5rem; margin-bottom: 1rem; color: var(--text-main); }
        .widget ul { list-style: none; padding: 0; }
        .widget li { margin-bottom: 0.8rem; }
        .widget a { color: var(--text-main); text-decoration: none; transition: color 0.3s; }
        .widget a:hover { color: var(--color-primary); }
    </style>
</head>
<body class="single-post right-sidebar rectified-magazine-fontawesome-version-6">
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
                        <h1 class="site-title"><a href="index.php"><i class="fa fa-star" style="color: var(--color-primary);"></i> Akanyenyeri</a></h1>
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
                                <li><a href="category.php?slug=<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
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
                    <!-- Breadcrumbs -->
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
                            <?php 
                            // Display content - in a real scenario we might want to allow some HTML tags
                            // For now we assume the content is safe or just text
                            echo nl2br(htmlspecialchars_decode($post['content'])); 
                            ?>
                        </div>
                    </article>
                </main>

                <!-- Sidebar -->
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
