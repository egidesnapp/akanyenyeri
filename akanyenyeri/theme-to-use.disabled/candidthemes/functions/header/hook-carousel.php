<?php
/**
 * Main Navigation Hook Element.
 *
 * @package Rectified Magazine
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!function_exists('rectified_magazine_constuct_carousel')) {
    /**
     * Add carousel on header
     *
     * @since 1.0.0
     */
    function rectified_magazine_constuct_carousel() 
    {

        if (is_front_page()) {
            global $rectified_magazine_theme_options;
            $rectified_magazine_site_layout = $rectified_magazine_theme_options['rectified-magazine-site-layout-options'];
            $slider_cat = $rectified_magazine_theme_options['rectified-magazine-select-category'];
            $featured_cat = $rectified_magazine_theme_options['rectified-magazine-select-category-featured-right'];
            $rectified_magazine_enable_date = $rectified_magazine_theme_options['rectified-magazine-slider-post-date'];
            $rectified_magazine_enable_author = $rectified_magazine_theme_options['rectified-magazine-slider-post-author'];
            $rectified_magazine_enable_read_time = $rectified_magazine_theme_options['rectified-magazine-slider-post-read-time'];
            $rectified_magazine_pagination_class = "";
            ?>
            
            <div class="rectified-magazine-featured-block rectified-magazine-ct-row clearfix">
                <?php

                rectified_magazine_main_carousel($slider_cat);


                $query_args = array(
                    'post_type' => 'post',
                    'ignore_sticky_posts' => true,
                    'posts_per_page' => 3,
                    'cat' => $featured_cat
                );

                $query = new WP_Query($query_args);
                if ($query->have_posts()) :
                    ?>
                    <div class="rectified-magazine-col rectified-magazine-col-2">
                        <div class="rectified-magazine-inner-row clearfix">
                            <?php
                            $i=1;
                            while ($query->have_posts()) :
                                $query->the_post();



                                $col_class = '';
                                if ($i == 1) {
                                    $col_class = 'rectified-magazine-col-full';

                                }
                                ?>
                                <div class="rectified-magazine-col <?php echo $col_class; ?>">
                                    <div class="featured-section-inner ct-post-overlay">
                                        <?php
                                        if (has_post_thumbnail()) {
                                            if($i == 1) {
                                                ?>
                                                <div class="post-thumb">
                                                    <?php
                                                    rectified_magazine_post_formats(get_the_ID());
                                                    ?>
                                                    <a href="<?php the_permalink(); ?>">
                                                        <?php
                                                        if ($rectified_magazine_site_layout == 'boxed') {
                                                            the_post_thumbnail('rectified-magazine-carousel-img-landscape');
                                                        } else {
                                                            the_post_thumbnail('rectified-magazine-carousel-large-img-landscape');
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
                                                            the_post_thumbnail('rectified-magazine-carousel-img');
                                                        } else {
                                                            the_post_thumbnail('rectified-magazine-carousel-large-img');
                                                        }
                                                        ?>
                                                    </a>
                                                </div>
                                                <?php
                                            }
                                        }else{
                                            if($i == 1) {
                                                ?>
                                                <div class="post-thumb">
                                                    <?php
                                                    rectified_magazine_post_formats(get_the_ID());
                                                    ?>
                                                    <a href="<?php the_permalink(); ?>">
                                                        <?php
                                                        if ($rectified_magazine_site_layout == 'boxed') {
                                                         ?>
                                                         <img src="<?php echo esc_url(get_template_directory_uri()).'/candidthemes/assets/images/rectified-mag-carousel-landscape.jpg' ?>" alt="<?php the_title(); ?>">
                                                         <?php
                                                     } else {
                                                        ?>
                                                        <img src="<?php echo esc_url(get_template_directory_uri()).'/candidthemes/assets/images/rectified-mag-carousel-large-landscape.jpg' ?>" alt="<?php the_title(); ?>">
                                                        <?php
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
                                                        <img src="<?php echo esc_url(get_template_directory_uri()).'/candidthemes/assets/images/rectified-mag-carousel-large.jpg' ?>" alt="<?php the_title(); ?>">
                                                        <?php
                                                    }
                                                    ?>
                                                </a>
                                            </div>
                                            <?php
                                        }
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
                            </div><!--.rectified-magazine-col-->
                            <?php
                            $i++;

                        endwhile;
                        wp_reset_postdata()
                        ?>

                    </div>
                </div><!--.rectified-magazine-col-->
                <?php
            endif;
            ?>

        </div><!-- .rectified-magazine-ct-row-->
        <?php


        }//is_front_page
    }
}
add_action('rectified_magazine_carousel', 'rectified_magazine_constuct_carousel', 10);