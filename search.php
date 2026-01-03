<?php
/**
 * Search Results Page
 * Queries `posts` table for the search term and displays results using theme layout
 */

require_once 'config/database.php';
require_once 'includes/theme-helpers.php';
require_once 'includes/theme-breadcrumbs.php';
require_once 'includes/theme-widgets.php';

$pdo = getDB();

$q = isset($_GET['s']) ? trim($_GET['s']) : '';

function get_excerpt($content, $length = 40) {
    $text = strip_tags($content);
    $words = preg_split('/\s+/', $text);
    if (count($words) > $length) {
        return implode(' ', array_slice($words, 0, $length)) . '...';
    }
    return $text;
}

// Perform search with pagination
$results = [];
$totalResults = 0;
if ($q !== '') {
    try {
        $like = '%' . $q . '%';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Count total matching results
        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) as total
             FROM posts p
             WHERE p.status = 'published' AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)");
        $countStmt->execute([$like, $like, $like]);
        $row = $countStmt->fetch(PDO::FETCH_ASSOC);
        $totalResults = intval($row['total'] ?? 0);

        // Fetch paginated results
        $stmt = $pdo->prepare(
            "SELECT p.*, u.full_name as author_name, c.name as category_name, c.slug as category_slug
             FROM posts p
             LEFT JOIN users u ON p.author_id = u.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.status = 'published' AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$like, $like, $like, $perPage, $offset]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $results = [];
    }
}

// Fetch categories for header/menu
function getCategories($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { return []; }
}
$categories = getCategories($pdo);

// Include theme header
include 'includes/theme-header.php';
?>

    <div id="content" class="site-content">
        <div class="container-inner clearfix">
            <div id="primary" class="content-area">
                <main id="main" class="site-main">

                    <?php theme_breadcrumbs(); ?>

                    <header class="page-header" style="margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 15px;">
                        <h1 class="page-title">Search Results for: <?php echo htmlspecialchars($q); ?></h1>
                    </header>

            <?php if(!empty($results)): ?>
                <div class="ct-post-list clearfix">
                    <?php $delay = 0; ?>
                    <?php foreach($results as $post): ?>
                        <article id="post-<?php echo $post['id']; ?>" class="post type-post status-publish format-standard has-post-thumbnail hentry" style="margin-bottom: 40px;" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                            <div class="rectified-magazine-content-container <?php echo $post['featured_image'] ? 'rectified-magazine-has-thumbnail' : 'rectified-magazine-no-thumbnail'; ?>">
                                <div class="rectified-magazine-content-area">
                                    <header class="entry-header">
                                        <div class="entry-meta">
                                            <span class="posted-on"><i class="fa fa-clock-o"></i> <time class="entry-date published"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></time></span>
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
                                        <p><?php echo get_excerpt($post['excerpt'] ? $post['excerpt'] : $post['content'], 40); ?></p>
                                        <p><a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="read-more-text" style="text-transform: uppercase; font-weight: bold; font-size: 14px;">Read More</a></p>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php $delay += 100; endforeach; ?>
                </div>

                    <?php
                    // Pagination links for search
                    if (!empty($results)) {
                        $totalPages = ($perPage > 0) ? ceil($totalResults / $perPage) : 0;
                        if ($totalPages > 1): ?>
                            <nav class="pagination" style="text-align:center; margin: 30px 0; padding: 20px; border-top: 1px solid #eee;">
                                <div style="display:inline-flex; gap:8px; align-items:center;">
                                    <?php if ($page > 1): ?>
                                        <a href="?s=<?php echo urlencode($q); ?>&page=<?php echo $page - 1; ?>" class="prev" style="padding:8px 12px; border:1px solid #ddd; border-radius:4px; text-decoration:none; color:#333; background:#f9f9f9; transition:all 0.2s;">&laquo; Previous</a>
                                    <?php endif; ?>

                                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                        <?php if ($p == $page): ?>
                                            <span class="current" style="padding:8px 12px; border-radius:4px; background:#3182ce; color:#fff; font-weight:bold;"><?php echo $p; ?></span>
                                        <?php else: ?>
                                            <a href="?s=<?php echo urlencode($q); ?>&page=<?php echo $p; ?>" style="padding:8px 12px; border:1px solid #ddd; border-radius:4px; text-decoration:none; color:#333; background:#f9f9f9; transition:all 0.2s;"><?php echo $p; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <a href="?s=<?php echo urlencode($q); ?>&page=<?php echo $page + 1; ?>" class="next" style="padding:8px 12px; border:1px solid #ddd; border-radius:4px; text-decoration:none; color:#333; background:#f9f9f9; transition:all 0.2s;">Next &raquo;</a>
                                    <?php endif; ?>
                                </div>
                            </nav>
                        <?php endif;
                    }
                    ?>

                            <?php else: ?>
                    <p style="padding: 2rem;">No results found for "<?php echo htmlspecialchars($q); ?>".</p>
                <?php endif; ?>

                </main>
            </div>

            <!-- Sidebar -->
            <aside id="secondary" class="widget-area">
                <div id="search-2" class="widget widget_search">
                    <h2 class="widget-title">Search</h2>
                    <form role="search" method="get" action="search.php" style="display: flex; gap: 8px;">
                        <input type="search" class="search-field" placeholder="Search..." value="<?php echo htmlspecialchars($q); ?>" name="s" style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;" />
                        <button type="submit" class="search-submit" style="padding: 8px 16px; background: #3182ce; color: white; border: none; border-radius: 4px; cursor: pointer;">Search</button>
                    </form>
                </div>
                <?php theme_widget_recent_posts($pdo, 5); ?>
                <?php theme_widget_categories($pdo, 10); ?>
            </aside>
        </div>
    </div>

<?php include 'includes/theme-footer.php'; ?>

