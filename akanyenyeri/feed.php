<?php
/**
 * RSS Feed Generator
 * Generates RSS 2.0 feed for the magazine
 */

require_once 'config/database.php';

header('Content-Type: application/rss+xml; charset=UTF-8');

$pdo = getDB();
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);

// Fetch recent posts
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.created_at, p.featured_image, u.full_name as author_name, c.name as category_name
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'published'
        ORDER BY p.created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $posts = [];
}

$lastBuildDate = !empty($posts) ? date('r', strtotime($posts[0]['created_at'])) : date('r');

echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title>Akanyenyeri Magazine</title>
        <link><?php echo htmlspecialchars($baseUrl); ?>/index.php</link>
        <description>Breaking News, Analysis, and Stories</description>
        <language>en-us</language>
        <lastBuildDate><?php echo $lastBuildDate; ?></lastBuildDate>
        <image>
            <title>Akanyenyeri Magazine</title>
            <url><?php echo htmlspecialchars($baseUrl); ?>/assets/theme/rectified/images/logo.png</url>
            <link><?php echo htmlspecialchars($baseUrl); ?>/index.php</link>
        </image>
        
        <?php foreach ($posts as $post): ?>
        <item>
            <title><?php echo htmlspecialchars($post['title']); ?></title>
            <link><?php echo htmlspecialchars($baseUrl); ?>/single.php?slug=<?php echo htmlspecialchars($post['slug']); ?></link>
            <guid><?php echo htmlspecialchars($baseUrl); ?>/single.php?slug=<?php echo htmlspecialchars($post['slug']); ?></guid>
            <pubDate><?php echo date('r', strtotime($post['created_at'])); ?></pubDate>
            <category><?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?></category>
            <creator><?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></creator>
            <description><?php echo htmlspecialchars($post['excerpt'] ? substr($post['excerpt'], 0, 200) : substr(strip_tags($post['content']), 0, 200)); ?></description>
            <content:encoded><![CDATA[
                <?php if ($post['featured_image']): ?>
                <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" />
                <?php endif; ?>
                <?php echo $post['content']; ?>
            ]]></content:encoded>
        </item>
        <?php endforeach; ?>
    </channel>
</rss>
