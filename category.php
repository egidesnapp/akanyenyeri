<?php
/**
 * Category Archive Template for Akanyenyeri Magazine
 * Updated to use Rectified Magazine Theme Structure
 */

require_once 'config/database.php';
require_once 'includes/theme-helpers.php';
require_once 'includes/theme-breadcrumbs.php';
require_once 'includes/theme-widgets.php';

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

// Fetch posts for this category with pagination
function getCategoryPosts($pdo, $categoryId, $limit = 10, $page = 1) {
    try {
        $offset = ($page - 1) * $limit;
        $stmt = $pdo->prepare("
            SELECT p.*, u.full_name as author_name, c.name as category_name, c.slug as category_slug
            FROM posts p
            LEFT JOIN users u ON p.author_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.category_id = ? AND p.status = 'published'
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$categoryId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { return []; }
}

function getCategoryPostsCount($pdo, $categoryId) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM posts WHERE category_id = ? AND status = 'published'");
        $stmt->execute([$categoryId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($row['total'] ?? 0);
    } catch (PDOException $e) { return 0; }
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$posts = getCategoryPosts($pdo, $category['id'], $perPage, $page);
$totalPosts = getCategoryPostsCount($pdo, $category['id']);

// Fetch categories for menu
function getCategories($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { return []; }
}
$categories = getCategories($pdo);

// Include Header
include 'includes/theme-header.php';
?>

<div id="content" class="site-content">
    <div class="container-inner clearfix">
        
        <?php theme_breadcrumbs(); ?>

        <!-- Main Content -->
        <div id="primary" class="content-area">
            <main id="main" class="site-main">
                
                <header class="page-header" style="margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 15px;">
                    <h1 class="page-title"><?php echo htmlspecialchars($category['name']); ?></h1>
                    <?php if($category['description']): ?>
                    <div class="taxonomy-description"><?php echo htmlspecialchars($category['description']); ?></div>
                    <?php endif; ?>
                </header>

                <?php if (!empty($posts)): ?>
                    <div class="ct-post-list clearfix">
                        <?php $delay = 0; ?>
                        <?php foreach($posts as $post): ?>
                        <article id="post-<?php echo $post['id']; ?>" class="post type-post status-publish format-standard has-post-thumbnail hentry" style="margin-bottom: 40px;" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                            <div class="rectified-magazine-content-container <?php echo $post['featured_image'] ? 'rectified-magazine-has-thumbnail' : 'rectified-magazine-no-thumbnail'; ?>">
                                
                                <div class="rectified-magazine-content-area">
                                    <header class="entry-header">
                                        <div class="entry-meta">
                                            <span class="posted-on">
                                                <i class="fa fa-clock-o"></i> <time class="entry-date published"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></time>
                                            </span>
                                            <span class="byline"> <i class="fa fa-user"></i> <span class="author vcard"><?php echo htmlspecialchars($post['author_name']); ?></span></span>
                                        </div>
                                        <h2 class="entry-title" style="font-size: 24px; margin: 10px 0;"><a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" rel="bookmark"><?php echo htmlspecialchars($post['title']); ?></a></h2>
                                    </header>

                                    <?php if($post['featured_image']): ?>
                                    <div class="post-thumb" style="margin-bottom: 20px;">
                                        <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="attachment-rectified-magazine-large-thumb size-rectified-magazine-large-thumb wp-post-image" style="width: 100%; height: auto; border-radius: 4px; transition: transform 0.3s ease;">
                                        </a>
                                    </div>
                                    <?php endif; ?>

                                    <div class="entry-content">
                                        <?php 
                                            $excerpt = strip_tags($post['content']);
                                            if(strlen($excerpt) > 150) $excerpt = substr($excerpt, 0, 150) . '...';
                                            echo "<p>$excerpt</p>";
                                        ?>
                                        <p><a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="read-more-text" style="text-transform: uppercase; font-weight: bold; font-size: 14px;">Read More</a></p>
                                    </div>
                                </div>
                            </div>
                        </article>
                        <?php $delay += 100; ?>
                        <?php endforeach; ?>
                    </div>

                    <?php
                    // Pagination links
                    $totalPages = ($perPage > 0) ? ceil($totalPosts / $perPage) : 0;
                    if ($totalPages > 1): ?>
                        <nav class="pagination" style="text-align:center; margin: 30px 0; padding: 20px; border-top: 1px solid #eee;">
                            <div style="display:inline-flex; gap:8px; align-items:center;">
                                <?php if ($page > 1): ?>
                                    <a href="?slug=<?php echo htmlspecialchars($slug); ?>&page=<?php echo $page - 1; ?>" class="prev" style="padding:8px 12px; border:1px solid #ddd; border-radius:4px; text-decoration:none; color:#333; background:#f9f9f9;">&laquo; Previous</a>
                                <?php endif; ?>

                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <?php if ($p == $page): ?>
                                        <span class="current" style="padding:8px 12px; border-radius:4px; background:#3182ce; color:#fff; font-weight:bold;"><?php echo $p; ?></span>
                                    <?php else: ?>
                                        <a href="?slug=<?php echo htmlspecialchars($slug); ?>&page=<?php echo $p; ?>" style="padding:8px 12px; border:1px solid #ddd; border-radius:4px; text-decoration:none; color:#333; background:#f9f9f9;"><?php echo $p; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?slug=<?php echo htmlspecialchars($slug); ?>&page=<?php echo $page + 1; ?>" class="next" style="padding:8px 12px; border:1px solid #ddd; border-radius:4px; text-decoration:none; color:#333; background:#f9f9f9;">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No posts found in this category.</p>
                <?php endif; ?>

            </main>
        </div>

        <!-- Sidebar -->
        <aside id="secondary" class="widget-area">
            <section class="widget widget_search">
                <form role="search" method="get" class="search-form" action="search.php">
                    <label>
                        <span class="screen-reader-text">Search for:</span>
                        <input type="search" class="search-field" placeholder="Search &hellip;" value="" name="s" />
                    </label>
                    <input type="submit" class="search-submit" value="Search" />
                </form>
            </section>
            
            <section class="widget widget_categories">
                <h2 class="widget-title">Categories</h2>
                <ul>
                    <?php foreach($categories as $cat): ?>
                    <li class="cat-item"><a href="category.php?slug=<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </aside>

    </div>
</div>

<?php include 'includes/theme-footer.php'; ?>
