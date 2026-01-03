<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Rectified Magazine
 */

get_header();
?>
    <div class="front-page-content-wrapper">

        <div id="primary" class="content-area">
            <main id="main" class="site-main">
                <?php
                /**
                 * rectified_magazine_action_front_page hook
                 * @package Rectified Magazine
                 *
                 * @hooked rectified_magazine_featured_section -  10
                 */
                do_action('rectified_magazine_action_front_page');
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
        ?>
    </div> <!-- .front-page-content-wrapper -->
<?php

get_footer();