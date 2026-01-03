<?php
/**
 * Header Hook Element.
 *
 * @package Rectified Magazine
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!function_exists('rectified_magazine_footer_start')) {
    /**
     * Add footer start tag.
     *
     * @param none
     * @return void
     *
     * @since 1.0.0
     *
     */
    function rectified_magazine_footer_start()
    {
        ?>
        <footer id="colophon" class="site-footer">
        <?php
    }
}
add_action('rectified_magazine_footer', 'rectified_magazine_footer_start', 5);

if (!function_exists('rectified_magazine_footer_widget')) {
    /**
     * Add footer top widget blocks
     *
     * @param none
     * @return void
     *
     * @since 1.0.0
     *
     */
    function rectified_magazine_footer_widget()
    {
        //Check if there are widgets on any footer sidebar
        if (is_active_sidebar('footer-1') || is_active_sidebar('footer-2') || is_active_sidebar('footer-3')) {
            ?>

            <div class="top-footer">
                <div class="container-inner clearfix">
                    <?php
                    $count = 0;
                    for ($i = 1; $i <= 3; $i++) {
                        if (is_active_sidebar('footer-' . $i)) {
                            $count++;
                        }
                    }
                    $column = $count;
                    $column_class = 'widget-column footer-active-' . absint($count);
                    for ($i = 1; $i <= 4; $i++) {
                        if (is_active_sidebar('footer-' . $i)) {
                            ?>
                            <div class="ct-col-<?php echo esc_attr($column); ?>">
                                <?php dynamic_sidebar('footer-' . $i); ?>
                            </div>
                            <?php
                        }
                    }

                    ?>
                </div> <!-- .container-inner -->
            </div> <!-- .top-footer -->
            <?php
        }
    }
}
add_action('rectified_magazine_footer', 'rectified_magazine_footer_widget', 15);


if (!function_exists('rectified_magazine_footer_siteinfo')) {
    /**
     * Add footer site info block
     *
     * @param none
     * @return void
     * @since 1.0.0
     *
     */
    function rectified_magazine_footer_siteinfo()
    {
        ?>

        <div class="site-info" <?php rectified_magazine_do_microdata('footer'); ?>>
            <div class="container-inner">
                <?php
                global $rectified_magazine_theme_options;
                $rectified_magazine_copyright = wp_kses_post($rectified_magazine_theme_options['rectified-magazine-footer-copyright']);
                if (!empty($rectified_magazine_copyright)):
                    ?>
                    <span class="copy-right-text"><?php echo $rectified_magazine_copyright; ?></span><br>
                <?php
                endif; //$rectified_magazine_copyright
                ?>

                <a href="<?php echo esc_url(__('https://wordpress.org/', 'rectified-magazine')); ?>" target="_blank">
                    <?php
                    /* translators: %s: CMS name, i.e. WordPress. */
                    printf(esc_html__('Proudly powered by %s', 'rectified-magazine'), 'WordPress');
                    ?>
                </a>
                <span class="sep"> | </span>
                <?php
                /* translators: 1: Theme name, 2: Theme author. */
                printf(esc_html__('Theme: %1$s by %2$s.', 'rectified-magazine'), 'Rectified Magazine', '<a href="https://www.candidthemes.com/" target="_blank">Candid Themes</a>');
                ?>
            </div> <!-- .container-inner -->
        </div><!-- .site-info -->
        <?php
    }
}
add_action('rectified_magazine_footer', 'rectified_magazine_footer_siteinfo', 20);


if (!function_exists('rectified_magazine_footer_end')) {
    /**
     * Add footer end tag.
     *
     * @param none
     * @return void
     *
     * @since 1.0.0
     *
     */
    function rectified_magazine_footer_end()
    {
        ?>
        </footer><!-- #colophon -->
        <?php
    }
}
add_action('rectified_magazine_footer', 'rectified_magazine_footer_end', 25);

if (!function_exists('rectified_magazine_construct_gototop')) {
    /**
     * Add Go to Top Button on Site.
     *
     * @param none
     * @return void
     *
     * @since 1.0.0
     *
     */
    function rectified_magazine_construct_gototop()
    {
        global $rectified_magazine_theme_options;
        if ($rectified_magazine_theme_options['rectified-magazine-go-to-top'] == 1):
            ?>
            <a id="toTop" class="go-to-top" href="#" title="<?php esc_attr_e('Go to Top', 'rectified-magazine'); ?>">
                <i class="fa fa-angle-up"></i>
            </a>
        <?php
        endif;

    }
}
add_action('rectified_magazine_gototop', 'rectified_magazine_construct_gototop', 10);