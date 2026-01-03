<?php
require_once 'config/database.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = "Test Post - " . date('Y-m-d H:i:s');
    $content = "<p>This is a test post created on " . date('Y-m-d H:i:s') . "</p>";
    
    $stmt = $pdo->prepare("
        INSERT INTO posts (
            title, slug, content, excerpt, featured_image,
            author_id, category_id, status, is_featured,
            meta_title, meta_description, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $title,
        'test-post-' . time(),
        $content,
        'Test excerpt',
        '',
        1,
        1,
        'published',
        0,
        $title,
        'Test excerpt'
    ]);
    
    if ($result) {
        $post_id = $pdo->lastInsertId();
        echo "<div style='background: #90EE90; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h2>✓ Test Post Created!</h2>";
        echo "<p>Post ID: <strong>" . $post_id . "</strong></p>";
        echo "<p>Title: <strong>" . htmlspecialchars($title) . "</strong></p>";
        echo "<p>Status: <strong style='color: green;'>published</strong></p>";
        echo "</div>";
    } else {
        echo "Failed to create post";
    }
}
?>

<h2>Create Test Post</h2>
<form method="POST">
    <button type="submit" style="padding: 10px 20px; font-size: 16px; background: #3182ce; color: white; border: none; border-radius: 4px; cursor: pointer;">
        Create Test Post with Status = Published
    </button>
</form>

<h2>Posts on Website (via Query):</h2>
<?php
$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.status, p.slug, p.created_at
    FROM posts p
    WHERE p.status = 'published' OR p.status = 'draft'
    ORDER BY p.created_at DESC
    LIMIT 10
");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total posts (published or draft): " . count($posts) . "<br><br>";

if (count($posts) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Title</th><th>Status</th><th>Slug</th><th>Created</th></tr>";
    
    foreach ($posts as $p) {
        $status_color = $p['status'] === 'published' ? '#90EE90' : '#FFB6C6';
        echo "<tr>";
        echo "<td>" . $p['id'] . "</td>";
        echo "<td>" . htmlspecialchars($p['title']) . "</td>";
        echo "<td style='background: " . $status_color . "; font-weight: bold;'>" . $p['status'] . "</td>";
        echo "<td>" . htmlspecialchars($p['slug']) . "</td>";
        echo "<td>" . $p['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<strong style='color: red;'>No posts found with status 'published' or 'draft'!</strong>";
}
?>
