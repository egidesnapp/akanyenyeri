<?php
/**
 * API endpoint: returns paginated published posts as JSON
 */
require_once __DIR__ . '/../database/config/database.php';
// Try PHP gzip output for API responses
if (!headers_sent()) {
    @ob_start('ob_gzhandler');
}

// Simple inline cache helper to avoid path issues
function api_cache_get($key, $ttl = 10) {
    $dir = __DIR__ . '/../cache';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $file = $dir . '/' . preg_replace('/[^a-z0-9_\-]/i','_', $key) . '.json';
    if (!file_exists($file)) return false;
    $meta = json_decode(@file_get_contents($file), true);
    if (!$meta) return false;
    if (time() - ($meta['ts'] ?? 0) > $ttl) { @unlink($file); return false; }
    return $meta['data'] ?? false;
}

function api_cache_set($key, $data) {
    $dir = __DIR__ . '/../cache';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $file = $dir . '/' . preg_replace('/[^a-z0-9_\-]/i','_', $key) . '.json';
    @file_put_contents($file, json_encode(['ts' => time(), 'data' => $data]));
}

header('Content-Type: application/json');

try {
    $pdo = getDB();
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, intval($_GET['limit'] ?? 10));
    $offset = ($page - 1) * $limit;

    // Try to serve from cache when available
    $cacheKey = 'api_posts_' . md5($page . '_' . $limit);
    $posts = api_cache_get($cacheKey, 10); // 10s TTL for API
    if ($posts === false) {
        // Use integer interpolation for LIMIT/OFFSET to avoid binding issues
        $sql = "SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.featured_image, p.created_at, u.full_name as author_name, c.name as category_name
                FROM posts p
                LEFT JOIN users u ON p.author_id = u.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'published'
                ORDER BY p.is_featured DESC, p.created_at DESC
                LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        api_cache_set($cacheKey, $posts);
    }

    echo json_encode(['success' => true, 'page' => $page, 'limit' => $limit, 'posts' => $posts]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
