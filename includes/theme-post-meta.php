<?php
/**
 * Post Metadata Utilities
 * Word count, reading time, etc.
 */

if (!function_exists('theme_get_word_count')) {
    function theme_get_word_count($content) {
        return str_word_count(strip_tags($content));
    }
}

if (!function_exists('theme_get_read_time')) {
    function theme_get_read_time($content, $wordsPerMinute = 200) {
        $wordCount = theme_get_word_count($content);
        $minutes = ceil($wordCount / $wordsPerMinute);
        return max(1, $minutes); // At least 1 minute
    }
}

if (!function_exists('theme_display_post_meta')) {
    function theme_display_post_meta($post) {
        $wordCount = theme_get_word_count($post['content']);
        $readTime = theme_get_read_time($post['content']);
        
        echo '<div class="post-meta-info" style="display:flex; gap:15px; font-size:12px; color:#a0aec0; margin:10px 0;">';
        echo '<span><i class="fa fa-calendar"></i> ' . date('M d, Y', strtotime($post['created_at'])) . '</span>';
        echo '<span><i class="fa fa-user"></i> ' . htmlspecialchars($post['author_name']) . '</span>';
        echo '<span><i class="fa fa-book"></i> ' . $wordCount . ' words</span>';
        echo '<span><i class="fa fa-clock-o"></i> ' . $readTime . ' min read</span>';
        echo '</div>';
    }
}
