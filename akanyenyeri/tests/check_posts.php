<?php
require_once 'config/database.php';

$pdo = getDB();

// Show last 5 posts
echo "<h2>Last 5 Posts in Database:</h2>";
$stmt = $pdo->query("
    SELECT id, title, status, is_featured, featured_image, created_at 
    FROM posts 
    ORDER BY id DESC 
    LIMIT 5
");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($posts as $post) {
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<strong>ID:</strong> " . $post['id'] . "<br>";
    echo "<strong>Title:</strong> " . htmlspecialchars($post['title']) . "<br>";
    echo "<strong>Status:</strong> <span style='background: " . ($post['status'] === 'published' ? '#90EE90' : '#FFB6C6') . "; padding: 2px 5px;'>" . $post['status'] . "</span><br>";
    echo "<strong>Featured:</strong> " . ($post['is_featured'] ? 'Yes' : 'No') . "<br>";
    echo "<strong>Featured Image:</strong> " . (empty($post['featured_image']) ? 'None' : htmlspecialchars(substr($post['featured_image'], 0, 50))) . "<br>";
    echo "<strong>Created:</strong> " . $post['created_at'] . "<br>";
    echo "</div>";
}

echo "<h2>Frontend Query Test:</h2>";
$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.status, p.slug, u.full_name, c.name as category_name
    FROM posts p
    LEFT JOIN users u ON p.author_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'published' OR p.status = 'draft'
    ORDER BY p.created_at DESC
    LIMIT 5
");
$stmt->execute();
$frontend_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($frontend_posts) . " posts with status 'published' or 'draft'<br><br>";

foreach ($frontend_posts as $p) {
    echo "<div style='border: 2px solid #0066cc; padding: 10px; margin: 10px 0;'>";
    echo "<strong>ID:</strong> " . $p['id'] . " | ";
    echo "<strong>Title:</strong> " . htmlspecialchars($p['title']) . " | ";
    echo "<strong>Status:</strong> " . $p['status'] . " | ";
    echo "<strong>Slug:</strong> " . htmlspecialchars($p['slug']) . "<br>";
    echo "</div>";
}
?>
