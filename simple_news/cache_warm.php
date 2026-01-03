<?php
/**
 * Cache warm endpoint - prebuild frequently used caches.
 * Run in browser or CLI to populate caches after deployment.
 */
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain; charset=utf-8');
echo "Warming caches...\n";

$pdo = getDB();

$jobs = [];

// Featured
try{ $f = $pdo->prepare("SELECT id,title,slug,featured_image,created_at FROM posts WHERE status='published' AND is_featured=1 ORDER BY created_at DESC LIMIT 3"); $f->execute(); $featured = $f->fetchAll(PDO::FETCH_ASSOC); cache_set('featured_posts',$featured); echo "- featured_posts cached\n"; } catch(Throwable $e){ echo "- featured failed\n"; }

// Categories
try{ $c = $pdo->query("SELECT id,name,slug FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC); cache_set('site_categories',$c); echo "- site_categories cached\n"; } catch(Throwable $e){ echo "- categories failed\n"; }

// Tags
try{ $t = $pdo->query("SELECT id,name,slug FROM tags ORDER BY name")->fetchAll(PDO::FETCH_ASSOC); cache_set('site_tags',$t); echo "- site_tags cached\n"; } catch(Throwable $e){ echo "- tags failed\n"; }

// Warm first 3 pages for default listing
for($p=1;$p<=3;$p++){
    $key = 'posts_' . md5(json_encode(['','', '', $p, 8]));
    try{
        $limit = 8;
        $offset = ($p-1) * $limit;
        // Use integer-interpolated LIMIT/OFFSET to avoid driver issues with bound params
        $sql = "SELECT p.id,p.title,p.slug,p.excerpt,p.featured_image,p.created_at,u.full_name as author,c.name as category FROM posts p LEFT JOIN users u ON p.author_id=u.id LEFT JOIN categories c ON p.category_id=c.id WHERE p.status='published' ORDER BY p.created_at DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        cache_set($key, $rows);
        echo "- cached page $p (" . count($rows) . " items)\n";
    } catch(Throwable $e){ echo "- failed page $p: " . $e->getMessage() . "\n"; }
}

// Total count
try{ $count = intval($pdo->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn() ?: 0); cache_set('count_' . md5(json_encode(['','',''])), $count); echo "- total count cached: $count\n"; } catch(Throwable $e){ echo "- count failed\n"; }

echo "Done.\n";
