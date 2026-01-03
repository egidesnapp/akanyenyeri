<?php
require_once 'config/database.php';

$pdo = getDB();

echo "<h2>System Status Check</h2>";

// Check categories
echo "<h3>Categories in Database:</h3>";
$stmt = $pdo->query("SELECT id, name FROM categories");
$cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total: " . count($cats) . "<br>";
if (count($cats) > 0) {
    echo "<ul>";
    foreach ($cats as $c) {
        echo "<li>ID: " . $c['id'] . " - " . htmlspecialchars($c['name']) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<span style='color: red;'><strong>WARNING: No categories found!</strong></span>";
}

// Check users
echo "<h3>Users in Database:</h3>";
$stmt = $pdo->query("SELECT id, username, full_name FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total: " . count($users) . "<br>";
if (count($users) > 0) {
    echo "<ul>";
    foreach ($users as $u) {
        echo "<li>ID: " . $u['id'] . " - " . htmlspecialchars($u['full_name']) . " (" . htmlspecialchars($u['username']) . ")</li>";
    }
    echo "</ul>";
}

// Check posts with all details
echo "<h3>All Posts (last 5):</h3>";
$stmt = $pdo->query("
    SELECT p.id, p.title, p.status, p.slug, p.author_id, p.category_id, c.name as cat_name, u.full_name as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    ORDER BY p.id DESC
    LIMIT 5
");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total posts in database: " . $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn() . "<br><br>";

foreach ($posts as $p) {
    echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0;'>";
    echo "ID: " . $p['id'] . " | ";
    echo "Title: " . htmlspecialchars($p['title']) . " | ";
    echo "Status: <strong style='color: " . ($p['status'] === 'published' ? 'green' : 'red') . ";'>" . $p['status'] . "</strong> | ";
    echo "Category: " . ($p['cat_name'] ? htmlspecialchars($p['cat_name']) : '<span style="color:red;">NONE</span>') . " | ";
    echo "Author: " . ($p['author_name'] ? htmlspecialchars($p['author_name']) . " (ID: " . $p['author_id'] . ")" : '<span style="color:red;">NONE</span>');
    echo "</div>";
}

echo "<h2>Frontend Homepage Posts</h2>";
// Simulate what index.php does
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
$home_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Posts found by homepage query: " . count($home_posts) . "<br><br>";

if (count($home_posts) > 0) {
    foreach ($home_posts as $p) {
        echo "<div style='background: #f9f9f9; border: 2px solid #3182ce; padding: 10px; margin: 10px 0;'>";
        echo "<strong>Title:</strong> " . htmlspecialchars($p['title']) . "<br>";
        echo "<strong>Status:</strong> " . $p['status'] . "<br>";
        echo "<strong>Slug:</strong> " . htmlspecialchars($p['slug']) . "<br>";
        echo "<strong>Author:</strong> " . ($p['author_name'] ? htmlspecialchars($p['author_name']) : 'Unknown') . "<br>";
        echo "<strong>Category:</strong> " . ($p['category_name'] ? htmlspecialchars($p['category_name']) : 'Uncategorized') . "<br>";
        echo "</div>";
    }
}
?>
