<?php
/**
 * Archive Page Template
 * Displays posts from a specific archive (can be reused for tags, authors, dates, etc.)
 */

require_once 'config/database.php';
require_once 'includes/theme-helpers.php';
require_once 'includes/theme-breadcrumbs.php';
require_once 'includes/theme-widgets.php';

$pdo = getDB();

// Determine archive type and get posts
$archiveType = isset($_GET['type']) ? $_GET['type'] : 'recent';
$archiveValue = isset($_GET['value']) ? $_GET['value'] : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$posts = [];
$totalCount = 0;
$archiveTitle = 'Archive';

if ($archiveType === 'recent' || empty($archiveValue)) {
    // Recent posts archive
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE status = 'published'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalCount = intval($row['total'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT p.*, u.full_name as author_name, c.name as category_name, c.slug as category_slug
            FROM posts p
            LEFT JOIN users u ON p.author_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'published'
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$perPage, $offset]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $archiveTitle = 'Recent Posts';
    } catch (PDOException $e) {
        $posts = [];
    }
}

$categories = theme_get_categories($pdo);
$totalPages = ($perPage > 0) ? ceil($totalCount / $perPage) : 0;

include 'includes/theme-header.php';
?>

<div id="content" class="site-content">
    <div class="container-inner clearfix">
        
        <!-- Main Content -->
        <div id="primary" class="content-area">
            <main id="main" class="site-main">
                
                <?php theme_breadcrumbs(); ?>

                <header class="page-header" style="margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 15px;">
                    <h1 class="page-title"><?php echo htmlspecialchars($archiveTitle); ?></h1>
                </header>

                <?php if (!empty($posts)): ?>
                    <div class="ct-post-list clearfix">
                        <?php $delay = 0; ?>
                        <?php foreach($posts as $post): ?>
                            <?php $aos_delay = $delay; include 'includes/theme-parts/content.php'; $delay += 100; ?>
                        <?php endforeach; ?>
                    </div>

                    <?php
                    // Pagination links
                    if ($totalPages > 1): ?>
                        <nav class="pagination" style="text-align:center; margin: 30px 0; padding: 20px; border-top: 1px solid #eee;">
                            <div style="display:inline-flex; gap:8px; align-items:center;">
                                <?php if ($page > 1): ?>
                                    <a href="?type=<?php echo htmlspecialchars($archiveType); ?>&value=<?php echo htmlspecialchars($archiveValue); ?>&page=<?php echo $page - 1; ?>" class="prev" style="padding:8px 12px; border:1px solid #ddd; border-radius:4px; text-decoration:none; color:#333; background:#f9f9f9;">&laquo; Previous</a>
                                <?php endif; ?>

                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <?php if ($p == $page): ?>
                                        <span class="current" style="padding:8px 12px; border-radius:4px; background:#3182ce; color:#fff; font-weight:bold;"><?php echo $p; ?></span>
                                    <?php else: ?>
                                        <a href="?type=<?php echo htmlspecialchars($archiveType); ?>&value=<?php echo htmlspecialchars($archiveValue); ?>&page=<?php echo $p; ?>" style="padding:8px 12px; border:1px solid #ddd; border-radius:4px; text-decoration:none; color:#333; background:#f9f9f9;"><?php echo $p; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?type=<?php echo htmlspecialchars($archiveType); ?>&value=<?php echo htmlspecialchars($archiveValue); ?>&page=<?php echo $page + 1; ?>" class="next" style="padding:8px 12px; border:1px solid #ddd; border-radius:4px; text-decoration:none; color:#333; background:#f9f9f9;">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No posts found in this archive.</p>
                <?php endif; ?>

            </main>
        </div>

        <!-- Sidebar -->
        <aside id="secondary" class="widget-area">
            <div id="search-2" class="widget widget_search">
                <h2 class="widget-title">Search</h2>
                <form role="search" method="get" class="search-form" action="search.php" style="display: flex; gap: 8px;">
                    <input type="search" class="search-field" placeholder="Search..." value="" name="s" style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;" />
                    <button type="submit" class="search-submit" style="padding: 8px 16px; background: #3182ce; color: white; border: none; border-radius: 4px; cursor: pointer;">Search</button>
                </form>
            </div>
            <?php theme_widget_recent_posts($pdo, 5); ?>
            <?php theme_widget_categories($pdo, 10); ?>
        </aside>

    </div>
</div>

<?php include 'includes/theme-footer.php'; ?>
