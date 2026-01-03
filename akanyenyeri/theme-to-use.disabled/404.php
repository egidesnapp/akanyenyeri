<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package Rectified Magazine
 */

get_header();
?>

    <div class="rectified-magazine-content-container rectified-magazine-no-thumbnail">
        <div class="rectified-magazine-content-area">

            <section class="error-404 not-found text-center">
                <header class="page-header">
                    <h1 class="error-404-title"> <?php esc_html_e('404', 'rectified-magazine'); ?> </h1>
                    <h2 class="page-title"><?php esc_html_e('Oops! That page can&rsquo;t be found.', 'rectified-magazine'); ?></h2>
                </header><!-- .page-header -->

                <div class="page-content">
                    <p><?php esc_html_e('It looks like nothing was found at this location. Maybe try one of the links below or a search?', 'rectified-magazine'); ?></p>

                    <?php
                    get_search_form();
                    ?>

                </div><!-- .page-content -->
            </section><!-- .error-404 -->

        </div><!-- .rectified-magazine-content-area -->
    </div><!-- .rectified-magazine-content-container  -->

<?php
get_footer();
