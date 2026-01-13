<?php
/**
 * Common theme utility functions
 * Shared across non-WP theme parts
 */

if (!function_exists('theme_get_excerpt')) {
    function theme_get_excerpt($content, $length = 40) {
        $text = strip_tags($content);
        $words = preg_split('/\s+/', $text);
        if (count($words) > $length) {
            return implode(' ', array_slice($words, 0, $length)) . '...';
        }
        return $text;
    }
}

if (!function_exists('theme_get_base_path')) {
    function theme_get_base_path() {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        if ($base === '/' || $base === '\\' || $base === '.') {
            return '';
        }
        return $base;
    }
}

if (!function_exists('theme_get_post_url')) {
    function theme_get_post_url($slug) {
        return 'single.php?slug=' . htmlspecialchars($slug);
    }
}

if (!function_exists('theme_get_category_url')) {
    function theme_get_category_url($slug) {
        return 'category.php?slug=' . htmlspecialchars($slug);
    }
}

if (!function_exists('theme_format_date')) {
    function theme_format_date($datetime, $format = 'M d, Y') {
        return date($format, strtotime($datetime));
    }
}

if (!function_exists('theme_get_categories')) {
    function theme_get_categories($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM categories WHERE status = 'published' ORDER BY name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

if (!function_exists('theme_render_post')) {
    function theme_render_post($post, $template = 'content') {
        // Render a post using the appropriate template part
        include __DIR__ . "/theme-parts/{$template}.php";
    }
}
