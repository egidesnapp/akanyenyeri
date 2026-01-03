<?php
// Enable gzip output when possible
if (!headers_sent()) {
    @ob_start('ob_gzhandler');
}
require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: index.php'); exit;
}

try {
    $stmt = $pdo->prepare("SELECT p.*, u.full_name as author, c.name as category FROM posts p LEFT JOIN users u ON p.author_id=u.id LEFT JOIN categories c ON p.category_id=c.id WHERE p.slug = ? AND p.status = 'published' LIMIT 1");
    $stmt->execute([$slug]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $post = false; }

if (!$post) { http_response_code(404); echo 'Post not found.'; exit; }

// Related posts (same category)
$related = [];
if (!empty($post['category_id'])) {
    try {
        $r = $pdo->prepare("SELECT id,title,slug FROM posts WHERE category_id = ? AND status='published' AND id != ? ORDER BY created_at DESC LIMIT 4");
        $r->execute([$post['category_id'], $post['id']]);
        $related = $r->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $related = []; }
}

<?php include_once __DIR__ . '/header.php'; ?>

    <!-- Spinner Overlay -->
    <div id="spinnerOverlay" class="spinner-overlay" aria-hidden="true">
        <div class="spinner"></div>
    </div>

    <main id="content" class="py-5">
        <div class="container">
            <div class="row g-4">
                <!-- Main Post -->
                <div class="col-lg-8">
                    <article class="post-detail">
                        <header class="post-header mb-4">
                            <h1 class="post-title mb-3"><?php echo htmlspecialchars($post['title']); ?></h1>
                            <div class="post-meta text-muted">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author'] ?? 'Unknown'); ?></span>
                                <span class="ms-3"><i class="fas fa-folder"></i> <a href="index.php?category=<?php echo urlencode($post['category'] ?? ''); ?>" class="text-decoration-none"><?php echo htmlspecialchars($post['category'] ?? 'Uncategorized'); ?></a></span>
                                <span class="ms-3"><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                            </div>
                        </header>

                        <?php if(!empty($post['featured_image'])): ?>
                        <figure class="post-featured-image mb-4">
                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="img-fluid rounded">
                        </figure>
                        <?php endif; ?>

                        <div class="post-content">
                            <?php echo $post['content']; ?>
                        </div>

                        <div class="post-footer mt-5 pt-4 border-top">
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Back to News
                            </a>
                        </div>
                    </article>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Related Posts -->
                    <?php if (!empty($related)): ?>
                    <div class="sidebar-widget">
                        <h5><i class="fas fa-link"></i> Related Posts</h5>
                        <ul class="list-unstyled">
                            <?php foreach($related as $r): ?>
                            <li class="mb-2">
                                <a href="single.php?slug=<?php echo urlencode($r['slug']); ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($r['title']); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Categories Widget -->
                    <div class="sidebar-widget">
                        <h5><i class="fas fa-list"></i> Categories</h5>
                        <ul class="list-unstyled">
                            <li><a href="index.php" class="text-decoration-none">All Categories</a></li>
                            <?php 
                            // Fetch categories for sidebar
                            try {
                                $cstmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY name");
                                $sideCats = $cstmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach($sideCats as $cat): 
                            ?>
                            <li><a href="index.php?category=<?php echo urlencode($cat['slug']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                            <?php 
                                endforeach;
                            } catch (Exception $e) {} 
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        (function(){
            var overlay = document.getElementById('spinnerOverlay');
            function show(){ if(overlay){ overlay.style.display='flex'; overlay.setAttribute('aria-hidden','false'); } }
            function hide(){ if(overlay){ overlay.style.display='none'; overlay.setAttribute('aria-hidden','true'); } }
            document.addEventListener('click', function(e){ var a = e.target.closest && e.target.closest('a'); if (!a) return; var href = a.getAttribute('href'); if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('javascript:')) return; show(); }, true);
            document.addEventListener('submit', function(e){ show(); }, true);
            window.addEventListener('load', function(){ setTimeout(hide, 200); });
        })();
    </script>

<?php include_once __DIR__ . '/footer.php'; ?>

