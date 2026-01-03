<?php
/**
 * Single Post Template for Akanyenyeri Magazine
 * Updated to use Rectified Magazine Theme Structure
 */

require_once 'config/database.php';
require_once 'includes/theme-helpers.php';
require_once 'includes/theme-breadcrumbs.php';
require_once 'includes/theme-post-meta.php';
require_once 'includes/theme-widgets.php';

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

// If post not found, redirect to home
if (!$post) {
    header("Location: index.php");
    exit;
}

// Fetch related posts (same category, excluding current post)
$relatedPosts = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, title, slug, excerpt, featured_image
        FROM posts
        WHERE category_id = ? AND id != ? AND status = 'published'
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$post['category_id'], $post['id']]);
    $relatedPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $relatedPosts = [];
}

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
                
                <article id="post-<?php echo $post['id']; ?>" class="post type-post status-publish format-standard has-post-thumbnail hentry">
                    
                    <header class="entry-header">
                        <?php theme_display_post_meta($post); ?>
                        <h1 class="entry-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                    </header>

                    <?php if($post['featured_image']): ?>
                    <div class="post-thumb" style="margin-bottom: 30px;">
                        <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="attachment-full size-full wp-post-image" style="width: 100%; height: auto; border-radius: 4px;">
                    </div>
                    <?php endif; ?>

                    <div class="entry-content">
                        <?php echo $post['content']; // Content is stored as HTML from editor ?>
                    </div>

                    <footer class="entry-footer" style="border-top:1px solid #eee; padding-top:20px; margin-top:30px;">
                        <p style="color:#666; font-size:12px;"><strong>Category:</strong> <a href="category.php?slug=<?php echo htmlspecialchars($post['category_slug']); ?>" style="color:#3182ce;"><?php echo htmlspecialchars($post['category_name']); ?></a></p>
                    </footer>
                    
                    <?php if (!empty($relatedPosts)): ?>
                    <div class="related-posts" style="margin-top:40px; padding-top:20px; border-top:2px solid #eee;">
                        <h3 style="font-size:18px; margin-bottom:20px;">Related Posts</h3>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px;">
                            <?php foreach ($relatedPosts as $relPost): ?>
                            <article style="border:1px solid #eee; border-radius:4px; overflow:hidden;">
                                <?php if ($relPost['featured_image']): ?>
                                <img src="<?php echo htmlspecialchars($relPost['featured_image']); ?>" alt="<?php echo htmlspecialchars($relPost['title']); ?>" style="width:100%; height:150px; object-fit:cover;">
                                <?php endif; ?>
                                <div style="padding:12px;">
                                    <h4 style="margin:0 0 8px; font-size:14px;"><a href="single.php?slug=<?php echo htmlspecialchars($relPost['slug']); ?>" style="color:#333; text-decoration:none;"><?php echo htmlspecialchars($relPost['title']); ?></a></h4>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </article>

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
            
            <?php theme_widget_recent_posts($pdo, 5); ?>
            <?php theme_widget_categories($pdo, 10); ?>
        </aside>

    </div>
</div>

<?php include 'includes/theme-footer.php'; ?>
