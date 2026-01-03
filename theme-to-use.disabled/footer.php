<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Rectified Magazine
 */

?>
</div> <!-- .container-inner -->
</div><!-- #content -->
<?php
if (is_active_sidebar('above-footer')) {
    ?>
    <div class="ct-above-footer">
        <div class="container-inner">
            <?php dynamic_sidebar('above-footer'); ?>
        </div>
    </div>
    <?php
}
?>
<?php

/**
 * rectified_magazine_before_footer hook.
 *
 * @since 1.0.0
 *
 */
do_action('rectified_magazine_before_footer');


/**
 * rectified_magazine_header hook.
 *
 * @since 1.0.0
 *
 * @hooked rectified_magazine_footer_start - 5
 * @hooked rectified_magazine_footer_socials - 10
 * @hooked rectified_magazine_footer_widget - 15
 * @hooked rectified_magazine_footer_siteinfo - 20
 * @hooked rectified_magazine_footer_end - 25
 */
do_action('rectified_magazine_footer');
?>

<?php
/**
 * rectified_magazine_construct_gototop hook
 *
 * @since 1.0.0
 *
 */
do_action('rectified_magazine_gototop');

?>

<?php

/**
 * rectified_magazine_after_footer hook.
 *
 * @since 1.0.0
 *
 */
do_action('rectified_magazine_after_footer');
?>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
