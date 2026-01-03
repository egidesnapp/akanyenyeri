<?php
/**
 * Template part for displaying results in search pages
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
        <div class="rectified-magazine-content-area">
            <header class="entry-header">
                <?php
                if ('post' === get_post_type()) :
                    ?>
                    <div class="entry-meta">
                        <?php
                        rectified_magazine_posted_on();
                        rectified_magazine_read_time_words_count(get_the_ID());
                        rectified_magazine_posted_by();
                        ?>
                    </div><!-- .entry-meta -->
                <?php endif;
                ?>
                <?php the_title(sprintf('<h2 class="entry-title" '.rectified_magazine_get_microdata("heading").'><a href="%s" rel="bookmark">', esc_url(get_permalink())), '</a></h2>'); ?>
            </header>

                <?php
                if ($rectified_magazine_thumbnail == 'rectified-magazine-has-thumbnail'):
                    ?>
                    <div class="post-thumb">
                        <div class="post-meta">
                            <?php
                            rectified_magazine_list_category(get_the_ID());
                            ?>
                        </div>
                        <?php
                        rectified_magazine_post_formats(get_the_ID());
                        rectified_magazine_post_thumbnail();
                        ?>
                    </div>
                <?php
                endif;
                ?>

            <div class="entry-summary">
                <?php
                $rectified_magazine_show_content = 'excerpt';
                if ( $rectified_magazine_show_content == 'excerpt' ) {
                    the_excerpt();
                } else {
                    the_content();
                }
                ?>
            </div><!-- .entry-summary -->

            <footer class="entry-footer">
                <?php rectified_magazine_entry_footer(); ?>
            </footer><!-- .entry-footer -->
        </div> <!-- .rectified-magazine-content-area -->
        <?php
        /**
         * rectified_magazine_social_sharing hook
         * @since 1.0.0
         *
         * @hooked rectified_magazine_constuct_social_sharing -  10
         */
        do_action( 'rectified_magazine_social_sharing' ,get_the_ID() );
        ?>
    </div> <!-- .rectified-magazine-content-container -->
</article><!-- #post-<?php the_ID(); ?> -->
