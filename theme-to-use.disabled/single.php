<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Rectified Magazine
 */

get_header();
global $rectified_magazine_theme_options;
?>

    <div id="primary" class="content-area">
        <main id="main" class="site-main">

            <?php
            /**
             * rectified_magazine_before_main_content hook.
             *
             * @since 0.1
             */
            do_action('rectified_magazine_before_main_content');

            /**
             * rectified_magazine_breadcrumb hook.
             *
             * @since 1.0
             * @hooked rectified_magazine_construct_breadcrumb -  10
             *
             */
            do_action('rectified_magazine_breadcrumb');
            ?>
            <?php
            if( is_active_sidebar( 'single-above-title' )) {
                ?>
                <div class="single-top-sidebar">
                    <?php dynamic_sidebar( 'single-above-title' ); ?>
                </div>
                <?php
            }
            ?>
            <?php

            while (have_posts()) :
                the_post();

                get_template_part('template-parts/content', get_post_type());

                the_post_navigation();

                /**
                 * rectified_magazine_after_single_post_navigation hook
                 * @since Rectified Magazine 1.0.0
                 *
                 */
                do_action('rectified_magazine_after_single_post_navigation');


                if (is_active_sidebar('single-below-content')) {
                    ?>
                    <div class="single-bottom-sidebar">
                        <?php dynamic_sidebar('single-below-content'); ?>
                    </div>
                    <?php
                }
                /**
                 * rectified_magazine_related_posts hook
                 * @since Rectified Magazine 1.0.0
                 *
                 * @hooked rectified_magazine_related_posts -  10
                 */
                do_action('rectified_magazine_related_posts', get_the_ID());

                // If comments are open or we have at least one comment, load up the comment template.
                if ((comments_open() || get_comments_number()) ) :
                    comments_template();
                endif;

            endwhile; // End of the loop.

            /**
             * rectified_magazine_after_main_content hook.
             *
             * @since 0.1
             */
            do_action('rectified_magazine_after_main_content');
            ?>

        </main><!-- #main -->
    </div><!-- #primary -->

<?php
/**
 * rectified_magazine_sidebar hook
 * @since Rectified Magazine 1.0.0
 *
 * @hooked rectified_magazine_sidebar -  10
 */
do_action('rectified_magazine_sidebar');

get_footer();
