<?php
/**
 * Smoke Tests for Akanyenyeri Frontend
 * Verifies that key pages load and display expected content from DB
 */

require_once 'config/database.php';

$pdo = getDB();
$baseUrl = 'http://localhost' . dirname($_SERVER['SCRIPT_NAME']);
$tests = [];
$passed = 0;
$failed = 0;

function test_endpoint($name, $url, $expectedSnippets = []) {
    global $passed, $failed, $tests;
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $result = ['name' => $name, 'url' => $url, 'status' => 'PASS', 'issues' => []];
    
    if ($httpCode !== 200) {
        $result['status'] = 'FAIL';
        $result['issues'][] = "HTTP {$httpCode}";
        $failed++;
    } else {
        foreach ($expectedSnippets as $snippet) {
            if (stripos($response, $snippet) === false) {
                $result['status'] = 'FAIL';
                $result['issues'][] = "Missing: {$snippet}";
                $failed++;
            }
        }
        if ($result['status'] === 'PASS') {
            $passed++;
        }
    }
    
    $tests[] = $result;
    return $result['status'] === 'PASS';
}

echo "=== Akanyenyeri Frontend Smoke Tests ===\n";
echo "Base URL: {$baseUrl}\n\n";

// Test 1: Homepage
echo "Test 1: Homepage (index.php)...\n";
test_endpoint(
    'Homepage',
    "{$baseUrl}/index.php",
    ['Akanyenyeri Magazine', 'site-main', 'post']
);

// Test 2: Search functionality
echo "Test 2: Search (search.php)...\n";
test_endpoint(
    'Search Results',
    "{$baseUrl}/search.php?s=test",
    ['Search Results', 'site-main']
);

// Test 3: Check if we have any published posts to test single view
try {
    $stmt = $pdo->query("SELECT slug FROM posts WHERE status = 'published' LIMIT 1");
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post) {
        echo "Test 3: Single Post (single.php)...\n";
        test_endpoint(
            'Single Post',
            "{$baseUrl}/single.php?slug={$post['slug']}",
            ['entry-content', 'entry-header', $post['slug']]
        );
    }
} catch (Exception $e) {
    echo "Test 3: Single Post - SKIPPED (no posts in DB)\n";
}

// Test 4: Check if we have categories
try {
    $stmt = $pdo->query("SELECT slug FROM categories LIMIT 1");
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cat) {
        echo "Test 4: Category Archive (category.php)...\n";
        test_endpoint(
            'Category',
            "{$baseUrl}/category.php?slug={$cat['slug']}",
            ['page-title', 'site-main']
        );
    }
} catch (Exception $e) {
    echo "Test 4: Category - SKIPPED\n";
}

// Test 5: 404 page
echo "Test 5: 404 Page...\n";
test_endpoint(
    '404 Not Found',
    "{$baseUrl}/nonexistent-page-xyz.php",
    ['404', 'not found']
);

// Test 6: Admin Dashboard (without auth, should redirect or show login)
echo "Test 6: Admin Dashboard...\n";
test_endpoint(
    'Admin Dashboard',
    "{$baseUrl}/admin/dashboard.php",
    []
);

// Summary
echo "\n=== Test Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total: " . ($passed + $failed) . "\n\n";

foreach ($tests as $test) {
    $status = str_pad($test['status'], 6);
    echo "[{$status}] {$test['name']}\n";
    if (!empty($test['issues'])) {
        foreach ($test['issues'] as $issue) {
            echo "        - {$issue}\n";
        }
    }
}

echo "\n✓ Smoke tests complete.\n";
exit($failed > 0 ? 1 : 0);
