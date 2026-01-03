<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Rectified Magazine
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php rectified_magazine_do_microdata('article'); ?>>
    <?php
    global $rectified_magazine_theme_options;
    $rectified_magazine_show_image = 1;
    if(is_singular()) {
        $rectified_magazine_show_image = $rectified_magazine_theme_options['rectified-magazine-single-page-featured-image'];
    }
    $rectified_magazine_show_content = $rectified_magazine_theme_options['rectified-magazine-content-show-from'];
    $rectified_magazine_thumbnail = (has_post_thumbnail() && ($rectified_magazine_show_image == 1)) ? 'rectified-magazine-has-thumbnail' : 'rectified-magazine-no-thumbnail';

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
                <?php endif; ?>
                
                <?php

                if (is_singular()) :
                    the_title('<h1 class="entry-title" ' . rectified_magazine_get_microdata("heading") . '>', '</h1>');
                else :
                    the_title('<h2 class="entry-title" ' . rectified_magazine_get_microdata("heading") . '><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>');
                endif;
                ?>               
            </header><!-- .entry-header -->
            
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

            <div class="entry-content">
                <?php
                if (is_singular()) :
                    the_content();
                else :
                    if ($rectified_magazine_show_content == 'excerpt') {
                        the_excerpt();
                    } else {
                        the_content();
                    }
                endif;

                wp_link_pages(array(
                    'before' => '<div class="page-links">' . esc_html__('Pages:', 'rectified-magazine'),
                    'after' => '</div>',
                ));
                ?>

                <?php
                $rectified_magazine_read_more_text = $rectified_magazine_theme_options['rectified-magazine-read-more-text'];
                if ((!is_single()) && ($rectified_magazine_show_content == 'excerpt')) {
                    if (!empty($rectified_magazine_read_more_text)) { ?>
                        <p><a href="<?php the_permalink(); ?>" class="read-more-text">
                                <?php echo esc_html($rectified_magazine_read_more_text); ?>

                            </a></p>
                        <?php
                    }
                }
                ?>
            </div>
            <!-- .entry-content -->

            <footer class="entry-footer">
                <?php rectified_magazine_entry_footer(); ?>
            </footer><!-- .entry-footer -->

            <?php
            /**
             * rectified_magazine_social_sharing hook
             * @since 1.0.0
             *
             * @hooked rectified_magazine_constuct_social_sharing -  10
             */
            do_action('rectified_magazine_social_sharing', get_the_ID());
            ?>
        </div> <!-- .rectified-magazine-content-area -->
    </div> <!-- .rectified-magazine-content-container -->
</article><!-- #post-<?php the_ID(); ?> -->
