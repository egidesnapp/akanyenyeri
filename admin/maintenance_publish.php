<?php
/**
 * Maintenance: backup posts and publish drafts / fix missing author/category
 * Access via browser: /admin/maintenance_publish.php
 */
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

// Create backups dir
$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
$ts = date('Ymd_His');
$backupFile = $backupDir . "/posts_backup_{$ts}.json";

// Fetch all posts
$stmt = $pdo->query("SELECT * FROM posts ORDER BY id DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents($backupFile, json_encode($posts, JSON_PRETTY_PRINT));

$report = [];
$report[] = "Backup saved to: " . $backupFile;

// Find a default author and category
$defaultAuthorId = 1;
$defaultCategoryId = 1;

try {
    $row = $pdo->query("SELECT id FROM users LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['id']) $defaultAuthorId = (int)$row['id'];
} catch (Exception $e) {}
try {
    $row = $pdo->query("SELECT id FROM categories LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['id']) $defaultCategoryId = (int)$row['id'];
} catch (Exception $e) {}

$report[] = "Default author id: {$defaultAuthorId}";
$report[] = "Default category id: {$defaultCategoryId}";

$changed = [];
foreach ($posts as $p) {
    $issues = [];
    if (empty($p['author_id']) || $p['author_id'] == 0) $issues[] = 'missing_author';
    if (empty($p['category_id']) || $p['category_id'] == 0) $issues[] = 'missing_category';
    if ($p['status'] !== 'published') $issues[] = 'not_published';

    if ($issues) {
        $updateFields = [];
        $params = [];
        if (in_array('missing_author', $issues)) {
            $updateFields[] = 'author_id = ?';
            $params[] = $defaultAuthorId;
        }
        if (in_array('missing_category', $issues)) {
            $updateFields[] = 'category_id = ?';
            $params[] = $defaultCategoryId;
        }
        if (in_array('not_published', $issues)) {
            $updateFields[] = "status = 'published'";
        }

        if ($updateFields) {
            $sql = "UPDATE posts SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $params[] = $p['id'];
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $changed[] = ['id' => $p['id'], 'title' => $p['title'], 'issues' => $issues];
            } catch (Exception $e) {
                $report[] = "Failed to update post {$p['id']}: " . $e->getMessage();
            }
        }
    }
}

$report[] = "Total posts checked: " . count($posts);
$report[] = "Total posts changed: " . count($changed);

// Show changed posts
if (count($changed) > 0) {
    $report[] = "Changed posts (id/title/issues):";
    foreach ($changed as $c) {
        $report[] = "- {$c['id']} - {$c['title']} - " . implode(',', $c['issues']);
    }
}

// Verify homepage query returns published posts
try {
    $stmt = $pdo->prepare("SELECT id, title, status FROM posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $pubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $report[] = "Published posts found: " . count($pubs);
    if (count($pubs) > 0) {
        foreach ($pubs as $pp) $report[] = "* {$pp['id']} - {$pp['title']} (status: {$pp['status']})";
    }
} catch (Exception $e) {
    $report[] = "Error querying published posts: " . $e->getMessage();
}

// Output HTML
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Maintenance - Publish Drafts</title></head>
<body style="font-family: Arial, sans-serif;">
    <h2>Maintenance Report</h2>
    <pre><?php echo htmlspecialchars(implode("\n", $report)); ?></pre>
    <p><a href="/akanyenyeri/">Open homepage</a></p>
</body>
</html>
