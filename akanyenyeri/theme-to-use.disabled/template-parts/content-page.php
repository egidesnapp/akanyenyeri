<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Rectified Magazine
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php rectified_magazine_do_microdata('article'); ?>>
    <?php
    $rectified_magazine_thumbnail = (has_post_thumbnail()) ? 'rectified-magazine-has-thumbnail' : 'rectified-magazine-no-thumbnail';
    ?>
    <div class="rectified-magazine-content-container <?php echo $rectified_magazine_thumbnail; ?>">
        <?php
        if (has_post_thumbnail()):
            the_post_thumbnail();
        endif;
        ?>
        <div class="rectified-magazine-content-area">
            <header class="entry-header">
                <?php the_title('<h1 class="entry-title" '.rectified_magazine_get_microdata("heading").'>', '</h1>'); ?>
            </header><!-- .entry-header -->

            <div class="entry-content">
                <?php
                the_content();

                wp_link_pages(array(
                    'before' => '<div class="page-links">' . esc_html__('Pages:', 'rectified-magazine'),
                    'after' => '</div>',
                ));
                ?>
            </div><!-- .entry-content -->

            <?php if (get_edit_post_link()) : ?>
                <footer class="entry-footer">
                    <?php
                    edit_post_link(
                        sprintf(
                            wp_kses(
                            /* translators: %s: Name of current post. Only visible to screen readers */
                                __('Edit <span class="screen-reader-text">%s</span>', 'rectified-magazine'),
                                array(
                                    'span' => array(
                                        'class' => array(),
                                    ),
                                )
                            ),
                            get_the_title()
                        ),
                        '<span class="edit-link">',
                        '</span>'
                    );
                    ?>
                </footer><!-- .entry-footer -->
            <?php endif; ?>
            <?php
            /**
             * rectified_magazine_social_sharing hook
             * @since 1.0.0
             *
             * @hooked rectified_magazine_constuct_social_sharing -  10
             */
            do_action( 'rectified_magazine_social_sharing' ,get_the_ID() );
            ?>
        </div> <!-- .rectified-magazine-content-area -->
    </div> <!-- .rectified-magazine-content-container -->
</article><!-- #post-<?php the_ID(); ?> -->
