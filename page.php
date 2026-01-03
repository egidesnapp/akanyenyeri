<?php
/**
 * Generic Page Loader
 * Attempts to load a static page file by `slug` or a DB `pages` record.
 */
require_once 'config/database.php';

$slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-\_]/i','', $_GET['slug']) : '';

// If a local file exists (e.g., contact.php), include it
if ($slug && file_exists(__DIR__ . '/' . $slug . '.php')) {
    include __DIR__ . '/' . $slug . '.php';
    exit;
}

$pdo = getDB();
$page = null;
if ($slug) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$slug]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $page = null;
    }
}

if (!$page) {
    // fallback to 404
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

// Render page using theme
include 'includes/theme-header.php';
?>
<div id="content" class="site-content">
    <div class="container-inner clearfix">
        <div id="primary" class="content-area">
            <main id="main" class="site-main">
                <?php $page_data = [
                    'id' => $page['id'],
                    'title' => $page['title'],
                    'content' => $page['content'],
                    'created_at' => $page['created_at'],
                    'author_name' => isset($page['author_name']) ? $page['author_name'] : ''
                ];
                $page = $page_data; include 'includes/theme-parts/content-page.php'; ?>
            </main>
        </div>
    </div>
</div>
<?php include 'includes/theme-footer.php'; ?>
