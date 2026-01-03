<?php
// DB diagnostic for simple_news
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getDB();
    if (!$pdo) throw new Exception('getDB() returned null');
    echo "Connected to database OK\n";

    // Check current database name
    $dbName = $pdo->query('select database()')->fetchColumn();
    echo "Database in use: " . ($dbName ?: '(none)') . "\n";

    // Count published posts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE status = 'published'");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo "Published posts count: " . intval($count) . "\n";

    // Show a sample row (id, title, slug)
    $s = $pdo->query("SELECT id,title,slug,created_at FROM posts WHERE status='published' ORDER BY created_at DESC LIMIT 5");
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        echo "\nRecent published posts:\n";
        foreach ($rows as $r) {
            echo sprintf("- %d | %s | %s | %s\n", $r['id'], $r['slug'], $r['title'], $r['created_at']);
        }
    } else {
        echo "\nNo published posts found (result set empty).\n";
    }

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    if (isset($pdo) && $pdo instanceof PDO) {
        echo "PDO error mode enabled.\n";
    }
}
