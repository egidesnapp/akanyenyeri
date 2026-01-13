<?php
/**
 * Breadcrumb Navigation Helper
 * Generates breadcrumb links for better UX and SEO
 */

if (!function_exists('theme_breadcrumbs')) {
    function theme_breadcrumbs() {
        $breadcrumbs = [];
        
        // Always start with home
        $breadcrumbs[] = ['title' => 'Home', 'url' => 'index.php'];
        
        // Detect current page
        $script = basename($_SERVER['SCRIPT_NAME']);
        
        if ($script === 'single.php' && isset($_GET['slug'])) {
            $breadcrumbs[] = ['title' => 'Posts', 'url' => '#'];
            $breadcrumbs[] = ['title' => htmlspecialchars($_GET['slug']), 'url' => null];
        } elseif ($script === 'category.php' && isset($_GET['slug'])) {
            $breadcrumbs[] = ['title' => 'Categories', 'url' => '#'];
            $breadcrumbs[] = ['title' => htmlspecialchars($_GET['slug']), 'url' => null];
        } elseif ($script === 'search.php') {
            $breadcrumbs[] = ['title' => 'Search Results', 'url' => null];
        } elseif ($script === 'archive.php') {
            $breadcrumbs[] = ['title' => 'Archives', 'url' => null];
        } elseif ($script === 'page.php') {
            $breadcrumbs[] = ['title' => 'Page', 'url' => null];
        }
        
        // Render breadcrumbs
        echo '<nav class="breadcrumbs" style="margin-bottom:20px; font-size:13px; color:#666;">';
        foreach ($breadcrumbs as $i => $crumb) {
            if ($crumb['url']) {
                echo '<a href="' . htmlspecialchars($crumb['url']) . '" style="color:#3182ce; text-decoration:none; margin:0 4px;">' . htmlspecialchars($crumb['title']) . '</a>';
            } else {
                echo '<span style="margin:0 4px;">' . htmlspecialchars($crumb['title']) . '</span>';
            }
            if ($i < count($breadcrumbs) - 1) {
                echo ' <span>/</span> ';
            }
        }
        echo '</nav>';
    }
}
