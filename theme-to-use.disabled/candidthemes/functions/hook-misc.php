<?php
/**
 * Single Post Hook Element.
 *
 * @package Rectified Magazine
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
/**
 * Display sidebar
 *
 * @param none
 * @return void
 *
 * @since Rectified Magazine 1.0.0
 *
 */
if (!function_exists('rectified_magazine_construct_sidebar')) :

    function rectified_magazine_construct_sidebar()
    {
        /*  * Adds sidebar based on customizer option
      *
           * @since Rectified Magazine 1.0.0
      */
        global $rectified_magazine_theme_options;
        $sidebar = $rectified_magazine_theme_options['rectified-magazine-sidebar-archive-page'] ? $rectified_magazine_theme_options['rectified-magazine-sidebar-archive-page'] : 'right-sidebar';
        if (is_singular()) {
            $sidebar = $rectified_magazine_theme_options['rectified-magazine-sidebar-blog-page'] ? $rectified_magazine_theme_options['rectified-magazine-sidebar-blog-page'] : 'right-sidebar';
            global $post;
            $single_sidebar = get_post_meta($post->ID, 'rectified_magazine_sidebar_layout', true);
            if (('default-sidebar' != $single_sidebar) && (!empty($single_sidebar))) {
                $sidebar = $single_sidebar;
            }
        }
        if (is_front_page()) {
            $sidebar = $rectified_magazine_theme_options['rectified-magazine-sidebar-front-page'] ? $rectified_magazine_theme_options['rectified-magazine-sidebar-front-page'] : 'right-sidebar';
        }
        if (($sidebar == 'right-sidebar') || ($sidebar == 'left-sidebar')) {
            get_sidebar();
        }
    }
endif;
add_action('rectified_magazine_sidebar', 'rectified_magazine_construct_sidebar', 10);

/**
 * Display Breadcrumb
 *
 * @param none
 * @return void
 *
 * @since Rectified Magazine 1.0.0
 *
 */
if (!function_exists('rectified_magazine_construct_breadcrumb')) :

    function rectified_magazine_construct_breadcrumb()
    {
        global $rectified_magazine_theme_options;
        //Check if breadcrumb is enabled from customizer
        if ($rectified_magazine_theme_options['rectified-magazine-extra-breadcrumb'] == 1):
            /**
             * Adds Breadcrumb based on customizer option
             *
             * @since Rectified Magazine 1.0.0
             */
            $breadcrumb_type = $rectified_magazine_theme_options['rectified-magazine-breadcrumb-display-from-option'];
            if ($breadcrumb_type == 'plugin-breadcrumb') {
                $breadcrumb_plugin = $rectified_magazine_theme_options['rectified-magazine-breadcrumb-display-from-plugins'];


                ?>
                <div class="breadcrumbs">
                    <?php
                    if ((function_exists('yoast_breadcrumb')) && ($breadcrumb_plugin == 'yoast')) {
                        yoast_breadcrumb();
                    } elseif ((function_exists('rank_math_the_breadcrumbs')) && ($breadcrumb_plugin == 'rank-math')) {
                        rank_math_the_breadcrumbs();
                    } elseif ((function_exists('bcn_display')) && ($breadcrumb_plugin == 'navxt')) {
                        bcn_display();
                    }
                    ?>
                </div>
                <?php
            } else {
                ?>
                <div class="breadcrumbs">
                    <?php
                    $breadcrumb_args = array(
                        'container' => 'div',
                        'show_browse' => false
                    );

                    $rectified_magazine_you_are_here_text = esc_html($rectified_magazine_theme_options['rectified-magazine-breadcrumb-text']);
                    if (!empty($rectified_magazine_you_are_here_text)) {
                        $rectified_magazine_you_are_here_text = "<span class='breadcrumb'>" . $rectified_magazine_you_are_here_text . "</span>";
                    }
                    echo "<div class='breadcrumbs init-animate clearfix'>" . $rectified_magazine_you_are_here_text . "<div id='rectified-magazine-breadcrumbs' class='clearfix'>";
                    breadcrumb_trail($breadcrumb_args);
                    echo "</div></div>";
                    ?>
                </div>
                <?php
            }
        endif;
    }
endif;
add_action('rectified_magazine_breadcrumb', 'rectified_magazine_construct_breadcrumb', 10);


/**
 * Filter to change excerpt lenght size
 *
 * @since 1.0.0
 */
if (!function_exists('rectified_magazine_alter_excerpt')) :
    function rectified_magazine_alter_excerpt($length)
    {
        if (is_admin()) {
            return $length;
        }
        global $rectified_magazine_theme_options;
        $rectified_magazine_excerpt_length = $rectified_magazine_theme_options['rectified-magazine-excerpt-length'];
        if (!empty($rectified_magazine_excerpt_length)) {
            return $rectified_magazine_excerpt_length;
        }
        return 50;
    }
endif;
add_filter('excerpt_length', 'rectified_magazine_alter_excerpt');


/**
 * Post Navigation Function
 *
 * @param null
 * @return void
 *
 * @since 1.0.0
 *
 */
if (!function_exists('rectified_magazine_posts_navigation')) :
    function rectified_magazine_posts_navigation()
    {
        global $rectified_magazine_theme_options;
        $rectified_magazine_pagination_option = $rectified_magazine_theme_options['rectified-magazine-pagination-options'];
        if ($rectified_magazine_pagination_option == 'default') {
            the_posts_navigation();
        } else {
            echo "<div class='candid-pagination'>";
            global $wp_query;
            $big = 999999999; // need an unlikely integer
            echo paginate_links(array(
                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format' => '?paged=%#%',
                'current' => max(1, get_query_var('paged')),
                'total' => $wp_query->max_num_pages,
                'prev_text' => __('&laquo; Prev', 'rectified-magazine'),
                'next_text' => __('Next &raquo;', 'rectified-magazine'),
            ));
            echo "</div>";
        }
    }
endif;
add_action('rectified_magazine_action_navigation', 'rectified_magazine_posts_navigation', 10);


/**
 * Social Sharing Hook *
 * @param int $post_id
 * @return void
 *
 * @since 1.0.0
 *
 */
if (!function_exists('rectified_magazine_constuct_social_sharing')) :
    function rectified_magazine_constuct_social_sharing($post_id)
    {
        global $rectified_magazine_theme_options;
        $rectified_magazine_enable_blog_sharing = $rectified_magazine_theme_options['rectified-magazine-enable-blog-sharing'];
        $rectified_magazine_enable_single_sharing = $rectified_magazine_theme_options['rectified-magazine-enable-single-sharing'];
        $rectified_magazine_enable_front_sharing = $rectified_magazine_theme_options['rectified-magazine-enable-static-page-sharing'];
        if (($rectified_magazine_enable_blog_sharing != 1) && (!is_singular())) {
            return;
        }
        if (($rectified_magazine_enable_single_sharing != 1) && (is_singular())) {
            return;
        }
        if (($rectified_magazine_enable_front_sharing != 1) && (is_front_page()) && ('page' == get_option('show_on_front'))) {
            return;
        }
        $rectified_magazine_url = get_the_permalink($post_id);
        $rectified_magazine_title = get_the_title($post_id);
        $rectified_magazine_image = get_the_post_thumbnail_url($post_id);

        //sharing url
        $rectified_magazine_twitter_sharing_url = esc_url('http://twitter.com/share?text=' . $rectified_magazine_title . '&url=' . $rectified_magazine_url);
        $rectified_magazine_facebook_sharing_url = esc_url('https://www.facebook.com/sharer/sharer.php?u=' . $rectified_magazine_url);
        $rectified_magazine_pinterest_sharing_url = esc_url('http://pinterest.com/pin/create/button/?url=' . $rectified_magazine_url . '&media=' . $rectified_magazine_image . '&description=' . $rectified_magazine_title);
        $rectified_magazine_linkedin_sharing_url = esc_url('http://www.linkedin.com/shareArticle?mini=true&title=' . $rectified_magazine_title . '&url=' . $rectified_magazine_url);

        ?>
        <div class="meta_bottom">
            <div class="text_share header-text"><?php _e('Share', 'rectified-magazine'); ?></div>
            <div class="post-share">
                    <a target="_blank" href="<?php echo $rectified_magazine_facebook_sharing_url; ?>">
                        <i class="fa fa-facebook"></i>
                        <?php esc_html_e('Facebook', 'rectified-magazine'); ?>
                    </a>
                    <a target="_blank" href="<?php echo $rectified_magazine_twitter_sharing_url; ?>">
                        <i class="fa fa-twitter"></i>                        
                        <?php esc_html_e('Twitter', 'rectified-magazine'); ?>
                    </a>
                    <a target="_blank" href="<?php echo $rectified_magazine_pinterest_sharing_url; ?>">
                        <i class="fa fa-pinterest"></i>
                        
                        <?php esc_html_e('Pinterest', 'rectified-magazine'); ?>
                    </a>
                    <a target="_blank" href="<?php echo $rectified_magazine_linkedin_sharing_url; ?>">
                        <i class="fa fa-linkedin"></i>
                        <?php esc_html_e('Linkedin', 'rectified-magazine'); ?>
                        
                    </a>
            </div>
        </div>
        <?php
    }
endif;
add_action('rectified_magazine_social_sharing', 'rectified_magazine_constuct_social_sharing', 10);

if (!function_exists('rectified_magazine_excerpt_words')) :
    function rectified_magazine_excerpt_words($post_id, $word_count = 25, $read_more = '')
    {
        $content = get_the_content($post_id);
        $trimmed_content = wp_trim_words($content, $word_count, $read_more);
        return $trimmed_content;

    }
endif;


if (!function_exists('rectified_magazine_main_carousel')) :
    function rectified_magazine_main_carousel($cat_id = '')
    {
        global $rectified_magazine_theme_options;
        $rectified_magazine_site_layout = $rectified_magazine_theme_options['rectified-magazine-site-layout-options'];

        $rectified_magazine_enable_date = $rectified_magazine_theme_options['rectified-magazine-slider-post-date'];
        $rectified_magazine_enable_author = $rectified_magazine_theme_options['rectified-magazine-slider-post-author'];

        $rectified_magazine_slider_args = array();
        if(is_rtl()){
            $rectified_magazine_slider_args['rtl']      = true;
        }
        $rectified_magazine_slider_args_encoded = wp_json_encode( $rectified_magazine_slider_args );

        $query_args = array(
            'post_type' => 'post',
            'ignore_sticky_posts' => true,
            'posts_per_page' => 4,
            'cat' => $cat_id
        );

        $query = new WP_Query($query_args);
        $count = $query->post_count;
        if ($query->have_posts()) :
            ?>

                        <div class="rectified-magazine-col">
                <ul class="ct-post-carousel slider hover-prev-next" data-slick='<?php echo $rectified_magazine_slider_args_encoded; ?>'>
                <?php
            while ($query->have_posts()) :
                $query->the_post();
                ?>
                    <li>
                            <div class="featured-section-inner ct-post-overlay">
                                <?php
                                if (has_post_thumbnail()) {
                                    ?>
                                    <div class="post-thumb">
                                        <?php
                                        rectified_magazine_post_formats(get_the_ID());
                                        ?>
                                        <a href="<?php the_permalink(); ?>">
                                            <?php
                                            if ($rectified_magazine_site_layout == 'boxed') {
                                                the_post_thumbnail('rectified-magazine-carousel-img');
                                            } else {
                                                the_post_thumbnail('rectified-magazine-carousel-large-img');
                                            }
                                            ?>
                                        </a>
                                    </div>
                                    <?php
                                }else{
                                    ?>
                                    <div class="post-thumb">
                                        <?php
                                        rectified_magazine_post_formats(get_the_ID());
                                        ?>
                                        <a href="<?php the_permalink(); ?>">
                                            <?php
                                            if ($rectified_magazine_site_layout == 'boxed') {
                                                ?>
                                                <img src="<?php echo esc_url(get_template_directory_uri()).'/candidthemes/assets/images/rectified-mag-carousel.jpg' ?>" alt="<?php the_title(); ?>">
                                                <?php
                                            } else {
                                                ?>
                                                <img src="<?php echo esc_url(get_template_directory_uri()).'/candidthemes/assets/images/rectified-mag-carousel-large.jpg' ?>" alt="<?php the_title_attribute(); ?>">
                                                <?php
                                            }
                                            ?>
                                        </a>
                                    </div>
                                    <?php
                                }
                                ?>
                                <div class="featured-section-details post-content">
                                    <div class="post-meta">
                                        <?php
                                        rectified_magazine_featured_list_category(get_the_ID());
                                        ?>
                                    </div>
                                    <h3 class="post-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                        <div class="post-meta">
                                            <?php
                                            if ($rectified_magazine_enable_date) {
                                                rectified_magazine_widget_posted_on();
                                            }
                                            rectified_magazine_read_time_slider(get_the_ID());
                                            if ($rectified_magazine_enable_author) {
                                                rectified_magazine_widget_posted_by();
                                            }
                                            ?>
                                        </div>
                                </div>
                            </div> <!-- .featured-section-inner -->
                    </li>
                <?php
            endwhile;
            wp_reset_postdata();
                ?>
                </ul>
                        </div><!--.rectified-magazine-col-->
        <?php
        endif;

    }
endif;

if (!function_exists('rectified_magazine_is_blog')) :
function rectified_magazine_is_blog () {
    global  $post;
    $posttype = get_post_type($post );
    return ( ((is_archive()) || (is_author()) || (is_category()) || (is_home()) || (is_single()) || (is_tag())) && ( $posttype == 'post')  ) ? true : false ;
}

endif;