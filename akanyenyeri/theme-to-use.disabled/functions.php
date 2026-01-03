<?php

/**
 * Rectified Magazine functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Rectified Magazine
 */

 if (!defined('_S_VERSION')) {
	// Replace the version number of the theme on each release.
	define('_S_VERSION', '1.0.0');
}

if (!function_exists('rectified_magazine_setup')) :
    /**
     * Sets up theme defaults and registers support for various WordPress features.
     *
     * Note that this function is hooked into the after_setup_theme hook, which
     * runs before the init hook. The init hook is too late for some features, such
     * as indicating support for post thumbnails.
     */
    function rectified_magazine_setup()
    {
        /*
         * Make theme available for translation.
         * Translations can be filed in the /languages/ directory.
         * If you're building a theme based on Rectified Magazine, use a find and replace
         * to change 'rectified-magazine' to the name of your theme in all the template files.
         */
        load_theme_textdomain('rectified-magazine');

        // Add default posts and comments RSS feed links to head.
        add_theme_support('automatic-feed-links');

        /*
         * Let WordPress manage the document title.
         * By adding theme support, we declare that this theme does not use a
         * hard-coded <title> tag in the document head, and expect WordPress to
         * provide it for us.
         */
        add_theme_support('title-tag');

        /*
         * Enable support for Post Thumbnails on posts and pages.
         *
         * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
         */
        add_theme_support('post-thumbnails');
        add_image_size('rectified-magazine-carousel-img', 783, 450, true);
        add_image_size('rectified-magazine-carousel-img-landscape', 783, 225, true);
        add_image_size('rectified-magazine-carousel-large-img', 1000, 574, true);
        add_image_size('rectified-magazine-carousel-large-img-landscape', 1000, 287, true);
        add_image_size('rectified-magazine-large-thumb', 1170, 9999);
        add_image_size('rectified-magazine-small-thumb', 350, 220, true);

        // This theme uses wp_nav_menu() in one location.
        register_nav_menus(array(
            'menu-1' => esc_html__('Primary', 'rectified-magazine'),
            'top-menu' => esc_html__('Top Menu', 'rectified-magazine'),
            'social-menu' => esc_html__('Social Menu', 'rectified-magazine'),
        ));

        /*
         * Switch default core markup for search form, comment form, and comments
         * to output valid HTML5.
         */
        add_theme_support('html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
        ));

        // Set up the WordPress core custom background feature.
        add_theme_support('custom-background', apply_filters('rectified_magazine_custom_background_args', array(
            'default-color' => 'fafafa ',
            'default-image' => '',
        )));

        // Add theme support for selective refresh for widgets.
        add_theme_support('customize-selective-refresh-widgets');

        /**
         * Add support for core custom logo.
         *
         * @link https://codex.wordpress.org/Theme_Logo
         */
        add_theme_support('custom-logo', array(
            'height' => 400,
            'width' => 250,
            'flex-width' => true,
            'flex-height' => true,
        ));

        /*
        * Enable support for Post Formats.
        *
        * See: https://codex.wordpress.org/Post_Formats
        */
        add_theme_support('post-formats', array(
            'image',
            'video',
            'gallery',
            'audio',
        ));

        /*
        * Add theme support for gutenberg block
        */
        add_theme_support('align-wide');

        // Add theme support for selective refresh for widgets.
        add_theme_support('customize-selective-refresh-widgets');

        // Add support for responsive embedded content.
        add_theme_support('responsive-embeds');

        // Add support for default block styles.
        add_theme_support('wp-block-styles');

        // Add custom CSS for the block editor
        add_theme_support('editor-styles');
        add_editor_style('editor-style.css');

        // Add support for Yoast SEO Breadcrumbs.
        add_theme_support('yoast-seo-breadcrumbs');
        $rectified_magazine_theme_options = rectified_magazine_get_options_value();
        $GLOBALS['rectified_magazine_theme_options'] = $rectified_magazine_theme_options;
    }
endif;
add_action('after_setup_theme', 'rectified_magazine_setup');

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function rectified_magazine_content_width()
{
    // This variable is intended to be overruled from themes.
    // Open WPCS issue: {@link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1043}.
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
    $GLOBALS['content_width'] = apply_filters('rectified_magazine_content_width', 640);
}

add_action('after_setup_theme', 'rectified_magazine_content_width', 0);

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function rectified_magazine_widgets_init()
{
    register_sidebar(array(
        'name' => esc_html__('Sidebar', 'rectified-magazine'),
        'id' => 'sidebar-1',
        'description' => esc_html__('Add widgets here.', 'rectified-magazine'),
        'before_widget' => '<div class="sidebar-widget-container"><section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section></div> ',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Home Page Widget Area', 'rectified-magazine'),
        'id'            => 'rectified-magazine-home-widget-area',
        'description'   => esc_html__('Add widgets here for home page.', 'rectified-magazine'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Home Page Full Width Area', 'rectified-magazine'),
        'id'            => 'rectified-magazine-home-full-width-area',
        'description'   => esc_html__('This area is just below the featured section and with full width.', 'rectified-magazine'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    register_sidebar(array(
        'name' => esc_html__('Footer Widget 1', 'rectified-magazine'),
        'id' => 'footer-1',
        'description' => esc_html__('Add widgets here.', 'rectified-magazine'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ));

    register_sidebar(array(
        'name' => esc_html__('Footer Widget 2', 'rectified-magazine'),
        'id' => 'footer-2',
        'description' => esc_html__('Add widgets here.', 'rectified-magazine'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ));

    register_sidebar(array(
        'name' => esc_html__('Footer Widget 3', 'rectified-magazine'),
        'id' => 'footer-3',
        'description' => esc_html__('Add widgets here.', 'rectified-magazine'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ));

    register_sidebar(array(
        'name' => esc_html__('Above Footer(Full Width)', 'rectified-magazine'),
        'id' => 'above-footer',
        'description' => esc_html__('Add widgets here.', 'rectified-magazine'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ));
}
add_action('widgets_init', 'rectified_magazine_widgets_init');

/**
 * Enqueue scripts and styles.
 */
function rectified_magazine_scripts()
{

    /*google font  */
    global $rectified_magazine_theme_options;

    wp_enqueue_style('google-fonts', '//fonts.googleapis.com/css?family=Mulish:wght@200..1000|Crimson+Text:ital,wght@0,400;0,600;0,700;1,400|Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900');

        /*Font-Awesome-master-rectified-magazine*/
        $rectified_magazine_fontawesome = !empty($rectified_magazine_theme_options['rectified-magazine-font-awesome-version-loading']) ? esc_attr($rectified_magazine_theme_options['rectified-magazine-font-awesome-version-loading']) : 'version-4';

        if ($rectified_magazine_fontawesome == 'version-5') {
            /*Font-Awesome-master*/
            wp_enqueue_style('font-awesome-5', get_template_directory_uri() . '/candidthemes/assets/framework/font-awesome-5/css/all.min.css', array(), _S_VERSION);
        } elseif ($rectified_magazine_fontawesome == 'version-6') {
            /*Font-Awesome-master*/
            wp_enqueue_style('font-awesome-6', get_template_directory_uri() . '/candidthemes/assets/framework/font-awesome-6/css/all.min.css', array(), _S_VERSION);
        } else {
            /*Font-Awesome-master*/
            wp_enqueue_style('font-awesome-4', get_template_directory_uri() . '/candidthemes/assets/framework/Font-Awesome/css/font-awesome.min.css', array(), _S_VERSION);
        }
    
    wp_enqueue_style('slick-css', get_template_directory_uri() . '/candidthemes/assets/framework/slick/slick.css');

    wp_enqueue_style('slick-theme-css', get_template_directory_uri() . '/candidthemes/assets/framework/slick/slick-theme.css');

    wp_enqueue_script('slick', get_template_directory_uri() . '/candidthemes/assets/framework/slick/slick.min.js', array('jquery'), '20151217', true);

    wp_enqueue_style('rectified-magazine-style', get_stylesheet_uri());

    wp_style_add_data('rectified-magazine-style', 'rtl', 'replace');

    wp_enqueue_script('jquery-ui-tabs');

    wp_enqueue_script('rectified-magazine-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '20151215', true);

    wp_enqueue_script('marquee', get_template_directory_uri() . '/candidthemes/assets/framework/marquee/jquery.marquee.js', array(), '20151215', true);
    wp_enqueue_script('rectified-magazine-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20151215', true);

    /*Sticky Sidebar*/
    if (!empty($rectified_magazine_theme_options['rectified-magazine-enable-sticky-sidebar']) && absint($rectified_magazine_theme_options['rectified-magazine-enable-sticky-sidebar']) == 1) {
        wp_enqueue_script('theia-sticky-sidebar', get_template_directory_uri() . '/candidthemes/assets/js/theia-sticky-sidebar.js', array(), '20151215', true);
    }

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }

    wp_enqueue_script('rectified-magazine-custom', get_template_directory_uri() . '/candidthemes/assets/js/rectified-magazine-custom.js', array('jquery'), '20151215', true);
}
add_action('wp_enqueue_scripts', 'rectified_magazine_scripts');


/**
 * Enqueue fonts for the backend editor
 */
function rectified_magazine_block_styles()
{
    wp_enqueue_style('google-fonts', '//fonts.googleapis.com/css?family=Mulish:wght@200..1000|Crimson+Text:ital,wght@0,400;0,600;0,700;1,400');
    wp_enqueue_style('custom-editor-styles', get_stylesheet_directory_uri() . '/editor-style.css', array(), _S_VERSION);

    $rectified_magazine_custom_css = '
    .edit-post-visual-editor.editor-styles-wrapper, 
    body.block-editor-page,
    .editor-styles-wrapper { font-family: "Mulish", serif;}

    .editor-post-title__block .editor-post-title__input,
    .editor-styles-wrapper h1,
    .editor-styles-wrapper h2,
    .editor-styles-wrapper h3,
    .editor-styles-wrapper h4,
    .editor-styles-wrapper h5,
    .editor-styles-wrapper h6 {font-family: "Inter", sans-serif;} 
    ';

    wp_add_inline_style('rectified-magazine-editor-styles', $rectified_magazine_custom_css);
}

add_action('enqueue_block_editor_assets', 'rectified_magazine_block_styles');

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if (defined('JETPACK__VERSION')) {
    require get_template_directory() . '/inc/jetpack.php';
}

/**
 * Load Core File
 */
require get_template_directory() . '/candidthemes/core.php';

/**
 * For Admin Page
 */
if (is_admin()) {
    require get_template_directory() . '/candidthemes/notice/admin-notice.php';
}