<?php
/**
 * Check PHP Error Log and Test Database
 */

// Check where error logs are written
echo "<h2>PHP Configuration</h2>";
echo "<p><strong>Error Log Path:</strong> " . ini_get('error_log') . "</p>";
echo "<p><strong>Display Errors:</strong> " . (ini_get('display_errors') ? 'On' : 'Off') . "</p>";

// Read recent error log
echo "<h2>Recent Errors (if any)</h2>";
$error_log_path = ini_get('error_log');
if ($error_log_path && file_exists($error_log_path)) {
    $lines = file($error_log_path);
    $recent_lines = array_slice($lines, -30);
    echo "<pre style='background: #f5f5f5; padding: 10px; overflow: auto; max-height: 400px;'>";
    foreach ($recent_lines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "Error log file not found at: " . htmlspecialchars($error_log_path);
}

// Test Database Connection
echo "<h2>Database Connection Test</h2>";
try {
    require_once 'config/database.php';
    $pdo = getDB();
    echo "✓ Database connection successful\n";
    
    // List all tables
    echo "<h3>Database Tables:</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage();
}

// Check if we can manually test post creation
echo "<h2>Manual Test Form</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_insert'])) {
    try {
        require_once 'config/database.php';
        $pdo = getDB();
        
        $stmt = $pdo->prepare("
            INSERT INTO posts (
                title, slug, content, excerpt, featured_image,
                author_id, category_id, status, is_featured,
                meta_title, meta_description, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            'Test Post ' . date('Y-m-d H:i:s'),
            'test-post-' . time(),
            '<p>Test content</p>',
            'Test excerpt',
            '',
            1,
            1,
            'draft',
            0,
            'Test Post',
            'Test excerpt'
        ]);
        
        if ($result) {
            $post_id = $pdo->lastInsertId();
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 4px; margin: 10px 0;'>";
            echo "✓ <strong>Test post created successfully!</strong> Post ID: $post_id";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0;'>";
        echo "✗ <strong>Error creating test post:</strong> " . htmlspecialchars($e->getMessage());
        echo "</div>";
    }
}

echo "<form method='POST' style='margin: 10px 0;'>";
echo "<button type='submit' name='test_insert' value='1' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;'>";
echo "Test Manual Post Creation";
echo "</button>";
echo "</form>";

?>
