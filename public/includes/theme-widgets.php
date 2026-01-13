<?php
/**
 * Sidebar Widgets
 * Reusable widgets for sidebars
 */

if (!function_exists('theme_widget_recent_posts')) {
    function theme_widget_recent_posts($pdo, $limit = 5) {
        try {
            $stmt = $pdo->prepare("
                SELECT id, title, slug, created_at
                FROM posts
                WHERE status = 'published'
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($posts)) {
                echo '<section class="widget widget_recent_entries" style="margin-bottom:30px;">';
                echo '<h2 class="widget-title" style="font-size:16px; font-weight:600; margin-bottom:15px; border-bottom:2px solid #eee; padding-bottom:10px;">Recent Posts</h2>';
                echo '<ul style="list-style:none; padding:0; margin:0;">';
                foreach ($posts as $post) {
                    echo '<li style="margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid #f0f0f0;">';
                    echo '<a href="single.php?slug=' . htmlspecialchars($post['slug']) . '" style="color:#3182ce; text-decoration:none; font-weight:500;">' . htmlspecialchars($post['title']) . '</a>';
                    echo '<div style="font-size:12px; color:#a0aec0; margin-top:4px;">' . date('M d, Y', strtotime($post['created_at'])) . '</div>';
                    echo '</li>';
                }
                echo '</ul>';
                echo '</section>';
            }
        } catch (PDOException $e) {
            // Silently fail
        }
    }
}

if (!function_exists('theme_widget_categories')) {
    function theme_widget_categories($pdo, $limit = 10) {
        try {
            $stmt = $pdo->prepare("
                SELECT c.id, c.name, c.slug, COUNT(p.id) as post_count
                FROM categories c
                LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published'
                GROUP BY c.id, c.name, c.slug
                ORDER BY post_count DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($categories)) {
                echo '<section class="widget widget_categories" style="margin-bottom:30px;">';
                echo '<h2 class="widget-title" style="font-size:16px; font-weight:600; margin-bottom:15px; border-bottom:2px solid #eee; padding-bottom:10px;">Categories</h2>';
                echo '<ul style="list-style:none; padding:0; margin:0;">';
                foreach ($categories as $cat) {
                    echo '<li style="margin-bottom:8px;"><a href="category.php?slug=' . htmlspecialchars($cat['slug']) . '" style="color:#3182ce; text-decoration:none;">' . htmlspecialchars($cat['name']) . ' <span style="color:#a0aec0;">(' . intval($cat['post_count']) . ')</span></a></li>';
                }
                echo '</ul>';
                echo '</section>';
            }
        } catch (PDOException $e) {
            // Silently fail
        }
    }
}

if (!function_exists('theme_widget_tags')) {
    function theme_widget_tags($pdo, $limit = 20) {
        try {
            // Assuming tags are stored in a post_tags junction table or as a JSON field
            $stmt = $pdo->query("
                SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(tags, ',', numbers.n), ',', -1) as tag
                FROM (SELECT 1 as n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) numbers
                INNER JOIN posts ON FIND_IN_SET(SUBSTRING_INDEX(SUBSTRING_INDEX(tags, ',', numbers.n), ',', -1), tags)
                WHERE posts.status = 'published' AND tags IS NOT NULL AND tags != ''
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($tags)) {
                echo '<section class="widget widget_tag_cloud" style="margin-bottom:30px;">';
                echo '<h2 class="widget-title" style="font-size:16px; font-weight:600; margin-bottom:15px; border-bottom:2px solid #eee; padding-bottom:10px;">Tags</h2>';
                echo '<div style="display:flex; flex-wrap:wrap; gap:8px;">';
                foreach ($tags as $tag) {
                    $tagName = trim($tag['tag']);
                    if (!empty($tagName)) {
                        echo '<a href="search.php?s=' . urlencode($tagName) . '" style="display:inline-block; padding:6px 10px; border:1px solid #ddd; border-radius:4px; color:#3182ce; text-decoration:none; font-size:12px; transition:all 0.2s;">' . htmlspecialchars($tagName) . '</a>';
                    }
                }
                echo '</div>';
                echo '</section>';
            }
        } catch (PDOException $e) {
            // Silently fail
        }
    }
}
