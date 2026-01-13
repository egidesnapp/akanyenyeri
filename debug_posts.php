<?php
require_once 'database/config/database.php';

echo "<h1>Database Debug Information</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{color:red;} .success{color:green;}</style>";

try {
    $pdo = getDB();
    echo "<p class='success'>✓ Database connection successful</p>";

    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tables in database: " . implode(', ', $tables) . "</p>";

    // Check total posts
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM posts');
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total posts in database: <strong>" . $total['total'] . "</strong></p>";

    // Check published posts
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM posts WHERE status = "published"');
    $published = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Published posts: <strong>" . $published['total'] . "</strong></p><br>";

    // Get all posts with details
    $stmt = $pdo->query('SELECT id, title, status, created_at, author_id, category_id FROM posts ORDER BY created_at DESC');
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>All Posts in Database:</h2>";
    if (count($posts) > 0) {
        foreach ($posts as $post) {
            echo "<div style='border:1px solid #ddd; padding:10px; margin:5px 0;'>";
            echo "<strong>ID:</strong> {$post['id']} | ";
            echo "<strong>Title:</strong> " . htmlspecialchars(substr($post['title'], 0, 60)) . "... | ";
            echo "<strong>Status:</strong> {$post['status']} | ";
            echo "<strong>Author ID:</strong> {$post['author_id']} | ";
            echo "<strong>Category ID:</strong> {$post['category_id']} | ";
            echo "<strong>Created:</strong> {$post['created_at']}";
            echo "</div>";
        }
    } else {
        echo "<p class='error'>No posts found in the database!</p>";
    }

    // Test the getRecentPosts function
    echo "<h2>Testing getRecentPosts Function:</h2>";
    function getRecentPosts($pdo, $limit = 6) {
        try {
            $stmt = $pdo->query("
                SELECT p.id, p.title, p.slug, p.excerpt, p.featured_image, p.created_at, p.views,
                       u.full_name as author_name, c.name as category_name, c.color as category_color
                FROM posts p
                LEFT JOIN users u ON p.author_id = u.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'published'
                ORDER BY p.created_at DESC
                LIMIT " . intval($limit)
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "<p class='error'>SQL Error in getRecentPosts: " . $e->getMessage() . "</p>";
            return [];
        }
    }

    $recentPosts = getRecentPosts($pdo, 6);
    echo "<p>getRecentPosts returned <strong>" . count($recentPosts) . "</strong> posts</p>";

    if (!empty($recentPosts)) {
        foreach ($recentPosts as $index => $post) {
            echo "<p>Post " . ($index + 1) . ": " . htmlspecialchars($post['title']) . " (ID: {$post['id']})</p>";
        }
    } else {
        echo "<p class='error'>No posts returned from getRecentPosts function</p>";
    }

    // Check users and categories
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM users');
    $users = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total users: <strong>" . $users['total'] . "</strong></p>";

    $stmt = $pdo->query('SELECT COUNT(*) as total FROM categories');
    $cats = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total categories: <strong>" . $cats['total'] . "</strong></p>";

} catch (Exception $e) {
    echo "<p class='error'>Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>Database name in config/database.php</li>";
    echo "<li>MySQL server is running</li>";
    echo "<li>Database exists</li>";
    echo "</ul>";
}
?>

<h2>Next Steps:</h2>
<p>If no posts exist, you need to:</p>
<ol>
    <li><a href="setup_database.php" target="_blank">Run setup_database.php</a> to create tables and sample data</li>
    <li>Or create posts through the <a href="admin/post-new.php" target="_blank">admin panel</a></li>
</ol>

<p><a href="public/index.php">← Back to Home Page</a></p>
