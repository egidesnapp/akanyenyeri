<?php
session_start();
require_once 'database/config/database.php';

$pdo = getDB();

// Check posts in database
echo "<h2>Posts in Database:</h2>";
$stmt = $pdo->query("SELECT id, title, status, created_at FROM posts ORDER BY created_at DESC LIMIT 10");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Created</th></tr>";
foreach ($posts as $post) {
    echo "<tr>";
    echo "<td>" . $post['id'] . "</td>";
    echo "<td>" . htmlspecialchars($post['title']) . "</td>";
    echo "<td><strong>" . $post['status'] . "</strong></td>";
    echo "<td>" . $post['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check what the index.php query returns
echo "<h2>Recent Posts Query Result:</h2>";
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name as author_name, c.name as category_name, c.slug as category_slug
    FROM posts p
    LEFT JOIN users u ON p.author_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'published' OR p.status = 'draft'
    ORDER BY p.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($recent) . " posts<br>";
if (count($recent) > 0) {
    echo "<pre>";
    print_r($recent[0]);
    echo "</pre>";
} else {
    echo "No posts found!";
}
?>
