<?php
/**
 * Dynamic CSS elements.
 *
 * @package Rectified Magazine
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


if (!function_exists('rectified_magazine_dynamic_css')) :
    /**
     * Dynamic CSS
     *
     * @since 1.0.0
     *
     * @param null
     * @return null
     *
     */
    function rectified_magazine_dynamic_css()
    {

        global $rectified_magazine_theme_options;

        $rectified_magazine_header_color = get_header_textcolor();
        $rectified_magazine_custom_css = '';

        if (!empty($rectified_magazine_header_color)) {
            $rectified_magazine_custom_css .= ".site-branding h1, .site-branding p.site-title,.ct-dark-mode .site-title a, .site-title, .site-title a, .site-title a:hover, .site-title a:visited:hover { color: #{$rectified_magazine_header_color}; }";
        }

        if (!empty($rectified_magazine_theme_options['rectified-magazine-site-title-hover'])) {
            $rectified_magazine_site_title_hover_color = esc_attr($rectified_magazine_theme_options['rectified-magazine-site-title-hover']);
            $rectified_magazine_custom_css .= ".ct-dark-mode .site-title a:hover,.site-title a:hover, .site-title a:visited:hover, .ct-dark-mode .site-title a:visited:hover { color: {$rectified_magazine_site_title_hover_color}; }";
        }


        if (!empty($rectified_magazine_theme_options['rectified-magazine-site-tagline'])) {
            $rectified_magazine_site_desc_color = esc_attr($rectified_magazine_theme_options['rectified-magazine-site-tagline']);
            $rectified_magazine_custom_css .= ".ct-dark-mode .site-branding  .site-description, .site-branding  .site-description { color: {$rectified_magazine_site_desc_color}; }";
        }

        /* Primary Color Section */
        if (!empty($rectified_magazine_theme_options['rectified-magazine-primary-color'])) {
            $rectified_magazine_primary_color = esc_attr($rectified_magazine_theme_options['rectified-magazine-primary-color']);

            //font-color
            $rectified_magazine_custom_css .= ".entry-content a, .entry-title a:hover, .related-title a:hover, .posts-navigation .nav-previous a:hover, .post-navigation .nav-previous a:hover, .posts-navigation .nav-next a:hover, .post-navigation .nav-next a:hover, #comments .comment-content a:hover, #comments .comment-author a:hover, .offcanvas-menu nav ul.top-menu li a:hover, .offcanvas-menu nav ul.top-menu li.current-menu-item > a, .error-404-title, #rectified-magazine-breadcrumbs a:hover, .entry-content a.read-more-text:hover, a:hover, a:visited:hover, .widget_rectified_magazine_category_tabbed_widget.widget ul.ct-nav-tabs li a  { color : {$rectified_magazine_primary_color}; }";

            //background-color
            $rectified_magazine_custom_css .= ".candid-rectified-post-format, .rectified-magazine-featured-block .rectified-magazine-col-2 .candid-rectified-post-format, .cat-links a,.top-bar,.main-navigation ul li a:hover, .main-navigation ul li.current-menu-item > a, .main-navigation ul li a:hover, .main-navigation ul li.current-menu-item > a, .trending-title, .search-form input[type=submit], input[type=\"submit\"], ::selection, #toTop, .breadcrumbs span.breadcrumb, article.sticky .rectified-magazine-content-container, .candid-pagination .page-numbers.current, .candid-pagination .page-numbers:hover, .ct-title-head, .widget-title:before, .widget ul.ct-nav-tabs:before, .widget ul.ct-nav-tabs li.ct-title-head:hover, .widget ul.ct-nav-tabs li.ct-title-head.ui-tabs-active, .wp-block-search__button { background-color : {$rectified_magazine_primary_color}; }";


            //border-color
            $rectified_magazine_custom_css .= ".candid-rectified-post-format, .rectified-magazine-featured-block .rectified-magazine-col-2 .candid-rectified-post-format, blockquote, .search-form input[type=\"submit\"], input[type=\"submit\"], .candid-pagination .page-numbers { border-color : {$rectified_magazine_primary_color}; }";

            $rectified_magazine_custom_css .= ".cat-links a:focus{ outline : 1px dashed {$rectified_magazine_primary_color}; }";
        }

        $rectified_magazine_custom_css .= ".ct-post-overlay .post-content, .ct-post-overlay .post-content a, .widget .ct-post-overlay .post-content a, .widget .ct-post-overlay .post-content a:visited, .ct-post-overlay .post-content a:visited:hover, .slide-details:hover .cat-links a { color: #fff; }";

        if(!empty($rectified_magazine_theme_options['rectified-magazine-enable-category-color'])){
            $enable_category_color = $rectified_magazine_theme_options['rectified-magazine-enable-category-color'];
            if ($enable_category_color == 1) {
                $args = array(
                    'orderby' => 'id',
                    'hide_empty' => 0
                );
                $categories = get_categories($args);
                $wp_category_list = array();
                $i = 1;
                foreach ($categories as $category_list) {
                    $wp_category_list[$category_list->cat_ID] = $category_list->cat_name;

                    $cat_color = 'cat-' . esc_attr(get_cat_id($wp_category_list[$category_list->cat_ID]));


                    if (array_key_exists($cat_color, $rectified_magazine_theme_options)) {
                        $cat_color_code = $rectified_magazine_theme_options[$cat_color];
                        $rectified_magazine_custom_css .= "
                    .cat-{$category_list->cat_ID} .ct-title-head,
                    .cat-{$category_list->cat_ID}.widget-title:before,
                     .cat-{$category_list->cat_ID} .widget-title:before,
                      .ct-cat-item-{$category_list->cat_ID}{
                    background: {$cat_color_code}!important;
                    }
                    ";
                        $rectified_magazine_custom_css .= "
                    .widget_rectified_magazine_category_tabbed_widget.widget ul.ct-nav-tabs li a.ct-tab-{$category_list->cat_ID} {
                    color: {$cat_color_code}!important;
                    }
                    ";
                    }


                    $i++;
                }
            }
        }

        if(!empty($rectified_magazine_theme_options['rectified-magazine-logo-section-background'])){
            $logo_section_color = esc_attr( $rectified_magazine_theme_options['rectified-magazine-logo-section-background'] );
            $rectified_magazine_custom_css .= ".logo-wrapper-block{background-color : {$logo_section_color}; }";
        }

        if(!empty($rectified_magazine_theme_options['rectified-magazine-boxed-width-options'])){
            $box_width = absint($rectified_magazine_theme_options['rectified-magazine-boxed-width-options']);
            $rectified_magazine_custom_css .= "@media (min-width: 1600px){.ct-boxed #page{max-width : {$box_width}px; }}";

            if($box_width < 1370){
                $rectified_magazine_custom_css .= "@media (min-width: 1450px){.ct-boxed #page{max-width : {$box_width}px; }}";
            }
        }

        wp_add_inline_style('rectified-magazine-style', $rectified_magazine_custom_css);
    }
endif;
add_action('wp_enqueue_scripts', 'rectified_magazine_dynamic_css', 99);