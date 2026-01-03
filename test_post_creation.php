<?php
/**
 * Test Post Creation
 */

session_start();
require_once "config/database.php";

// Get database connection
$pdo = getDB();

// Test 1: Check posts table structure
echo "=== Posts Table Structure ===\n";
try {
    $stmt = $pdo->query("DESCRIBE posts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test 2: Check if posts table exists
echo "\n=== Checking Posts Table Existence ===\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM posts");
    $count = $stmt->fetchColumn();
    echo "Posts table exists. Current post count: " . $count . "\n";
} catch (Exception $e) {
    echo "Posts table may not exist: " . $e->getMessage() . "\n";
}

// Test 3: Try inserting a test post
echo "\n=== Testing Post Insertion ===\n";
try {
    $test_data = [
        'Test Post Title',
        'test-post-title',
        '<p>This is test content</p>',
        'Test excerpt...',
        '',
        1, // author_id
        1, // category_id
        'draft',
        0, // is_featured
        'Test Post Title',
        'Test excerpt...'
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO posts (
            title, slug, content, excerpt, featured_image,
            author_id, category_id, status, is_featured,
            meta_title, meta_description, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute($test_data);
    
    if ($result) {
        $post_id = $pdo->lastInsertId();
        echo "Success! Test post inserted with ID: " . $post_id . "\n";
        
        // Verify insertion
        $verify = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
        $verify->execute([$post_id]);
        $post = $verify->fetch(PDO::FETCH_ASSOC);
        echo "Verified: " . json_encode($post) . "\n";
    } else {
        echo "Failed to insert post\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test 4: Check if there's an admin user
echo "\n=== Checking Admin Users ===\n";
try {
    $stmt = $pdo->query("SELECT id, username, full_name FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        echo "Admin user found: " . json_encode($admin) . "\n";
    } else {
        echo "No admin user found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test 5: Check categories
echo "\n=== Checking Categories ===\n";
try {
    $stmt = $pdo->query("SELECT id, name FROM categories LIMIT 3");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Categories found: " . count($categories) . "\n";
    foreach ($categories as $cat) {
        echo "  - " . $cat['id'] . ": " . $cat['name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>
