<?php
// Enable gzip output when possible
if (!headers_sent()) {
    @ob_start('ob_gzhandler');
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/cache.php';

$pdo = getDB();

// Query params: search, category, tag
$q = trim($_GET['s'] ?? '');
$category = trim($_GET['category'] ?? '');
$tag = trim($_GET['tag'] ?? '');

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 8;
$offset = ($page - 1) * $perPage;

// Build cache key
$cacheKey = 'posts_' . md5(json_encode([$q, $category, $tag, $page, $perPage]));
$posts = cache_get($cacheKey, 30);
if ($posts === false) {
    try {
        $where = ['p.status = \'published\''];
        $params = [];

        if ($q !== '') {
            $where[] = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
            $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
        }
        if ($category !== '') {
            $where[] = 'c.slug = ?';
            $params[] = $category;
        }
        $joinTag = '';
        if ($tag !== '') {
            $joinTag = ' INNER JOIN post_tags pt ON pt.post_id = p.id INNER JOIN tags t ON t.id = pt.tag_id ';
            $where[] = 't.slug = ?';
            $params[] = $tag;
        }

        $sql = "SELECT p.id, p.title, p.slug, p.excerpt, p.featured_image, p.created_at, u.full_name as author, c.name as category
                FROM posts p
                LEFT JOIN users u ON p.author_id = u.id
                LEFT JOIN categories c ON p.category_id = c.id " . $joinTag .
               " WHERE " . implode(' AND ', $where) . " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";

        $params[] = $perPage; $params[] = $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        cache_set($cacheKey, $posts);
    } catch (Exception $e) {
        $posts = [];
    }
}

// Featured
$featured = cache_get('featured_posts', 120);
if ($featured === false) {
    try {
        $f = $pdo->prepare("SELECT id,title,slug,featured_image,created_at FROM posts WHERE status='published' AND is_featured=1 ORDER BY created_at DESC LIMIT 3");
        $f->execute();
        $featured = $f->fetchAll(PDO::FETCH_ASSOC);
        cache_set('featured_posts', $featured);
    } catch (Exception $e) { $featured = []; }
}

// Categories and tags for filters
$categories = cache_get('site_categories', 300);
if ($categories === false) {
    try { $categories = $pdo->query("SELECT id,name,slug FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC); cache_set('site_categories',$categories); } catch(Exception $e){ $categories = []; }
}

$tags = cache_get('site_tags', 300);
if ($tags === false) {
    try { $tags = $pdo->query("SELECT id,name,slug FROM tags ORDER BY name")->fetchAll(PDO::FETCH_ASSOC); cache_set('site_tags',$tags); } catch(Exception $e){ $tags = []; }
}

// Total published posts (respecting filters)
$countCacheKey = 'count_' . md5(json_encode([$q,$category,$tag]));
$totalPosts = cache_get($countCacheKey, 30);
if ($totalPosts === false) {
    try {
        $whereClause = ['status = \'published\''];
        $countParams = [];
        if ($q !== '') { $whereClause[] = '(title LIKE ? OR excerpt LIKE ? OR content LIKE ?)'; $countParams[] = "%$q%"; $countParams[] = "%$q%"; $countParams[] = "%$q%"; }
        if ($category !== '') { $whereClause[] = 'c.slug = ?'; $countParams[] = $category; }
        if ($tag !== '') { $joinCountTag = ' INNER JOIN post_tags pt ON pt.post_id = p.id INNER JOIN tags t ON t.id = pt.tag_id '; $whereClause[] = 't.slug = ?'; $countParams[] = $tag; } else { $joinCountTag = ''; }
        $sqlCount = "SELECT COUNT(DISTINCT p.id) as total FROM posts p LEFT JOIN categories c ON p.category_id = c.id " . $joinCountTag . " WHERE " . implode(' AND ', $whereClause);
        $stmtCount = $pdo->prepare($sqlCount);
        $stmtCount->execute($countParams);
        $totalPosts = intval($stmtCount->fetchColumn() ?: 0);
        cache_set($countCacheKey, $totalPosts);
    } catch (Exception $e) { $totalPosts = 0; }
}

$totalPages = ($perPage>0) ? ceil($totalPosts / $perPage) : 0;

function excerpt($text, $len = 30){
    $t = strip_tags($text);
    $words = preg_split('/\s+/', $t);
    if (count($words) > $len) return implode(' ', array_slice($words,0,$len)) . '...';
    return $t;
}
?>

<?php include_once __DIR__ . '/header.php'; ?>

<div class="site-content">
    <div class="container-fluid px-4">
        <!-- Loading Spinner -->
        <div class="spinner-overlay" id="spinnerOverlay">
            <div class="text-center">
                <div class="spinner mb-3"></div>
                <p class="text-primary fw-bold">Loading…</p>
            </div>
        </div>

        <!-- Search Form at Top -->
        <div class="row g-4 mt-4 mb-5">
            <div class="col-12">
                <div class="sidebar-widget">
                    <form method="get" role="search" class="search-form">
                        <div class="d-flex gap-2">
                            <input type="search" name="s" placeholder="Search news..." value="<?php echo htmlspecialchars($q); ?>" class="form-control">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Featured Posts Section -->
        <?php if (!empty($featured)): ?>
        <section class="featured-container">
            <h2 class="mb-4">Featured Stories</h2>
            <div class="row g-4">
                <?php foreach($featured as $f): ?>
                <div class="col-lg-4 col-md-6">
                    <a href="single.php?slug=<?php echo urlencode($f['slug']); ?>" class="text-decoration-none">
                        <div class="featured-card">
                            <?php if(!empty($f['featured_image'])): ?>
                            <img src="<?php echo htmlspecialchars($f['featured_image']); ?>" alt="<?php echo htmlspecialchars($f['title']); ?>" class="card-img-top">
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($f['title']); ?></h3>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Main Content & Sidebar -->
        <div class="row g-4 mt-4">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Articles Grid -->
                <?php if (empty($posts)): ?>
                <div class="alert alert-info text-center py-5">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p class="mb-0">No posts found. Try adjusting your filters.</p>
                </div>
                <?php else: ?>
                <div class="row g-4 mb-5">
                    <?php foreach($posts as $p): ?>
                    <div class="col-lg-6">
                        <div class="article-card">
                            <?php if (!empty($p['featured_image'])): ?>
                            <a href="single.php?slug=<?php echo urlencode($p['slug']); ?>" class="card-img text-decoration-none">
                                <img src="<?php echo htmlspecialchars($p['featured_image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>">
                            </a>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="single.php?slug=<?php echo urlencode($p['slug']); ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($p['title']); ?>
                                    </a>
                                </h5>
                                <div class="card-meta">
                                    <span><?php echo htmlspecialchars($p['author'] ?? 'Unknown'); ?></span>
                                    <span>•</span>
                                    <span><?php echo htmlspecialchars($p['category'] ?? 'Uncategorized'); ?></span>
                                    <span>•</span>
                                    <span><?php echo date('M j, Y', strtotime($p['created_at'])); ?></span>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars(excerpt($p['excerpt'] ? $p['excerpt'] : $p['content'], 20)); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mb-5">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['page'=>1])); ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page-1])); ?>">← Previous</a>
                        </li>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['page'=>$p])); ?>"><?php echo $p; ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page+1])); ?>">Next →</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['page'=>$totalPages])); ?>">Last</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Categories Widget -->
                <div class="sidebar-widget">
                    <h5><i class="fas fa-list"></i> Categories</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php">All Categories</a></li>
                        <?php foreach($categories as $c): ?>
                        <li><a href="?category=<?php echo urlencode($c['slug']); ?>"><?php echo htmlspecialchars($c['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Tags Widget -->
                <div class="sidebar-widget">
                    <h5><i class="fas fa-tags"></i> Popular Tags</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach($tags as $t): ?>
                        <a href="?tag=<?php echo urlencode($t['slug']); ?>" class="tag-badge">
                            <?php echo htmlspecialchars($t['name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <!-- Spinner Overlay -->
    <div id="spinnerOverlay" class="spinner-overlay" aria-hidden="true">
        <div class="spinner"></div>
    </div>

    <script>
        (function(){
            var overlay = document.getElementById('spinnerOverlay');
            function show(){ if(overlay){ overlay.style.display='flex'; overlay.setAttribute('aria-hidden','false'); } }
            function hide(){ if(overlay){ overlay.style.display='none'; overlay.setAttribute('aria-hidden','true'); } }

            document.addEventListener('click', function(e){
                var a = e.target.closest && e.target.closest('a');
                if (!a) return;
                var href = a.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('javascript:')) return;
                show();
            }, true);

            document.addEventListener('submit', function(e){ show(); }, true);
            window.addEventListener('load', function(){ setTimeout(hide, 200); });
        })();
    </script>

<?php include_once __DIR__ . '/footer.php'; ?>
