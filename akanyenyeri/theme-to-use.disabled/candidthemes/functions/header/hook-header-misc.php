<?php
/**
 * Header Hook Element.
 *
 * @package Rectified Magazine
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!function_exists('rectified_magazine_main_header_right_menu')) {
    /**
     * Add main header with menu on right
     *
     * @since 1.0.0
     */
    function rectified_magazine_main_header_right_menu()
    {
        $has_header_image = has_header_image();
        global $rectified_magazine_theme_options;
        $search_class = '';
        if ($rectified_magazine_theme_options['rectified-magazine-enable-menu-section-search'] == 1):
        $show_search = 1;
        $search_class = 'ct-show-search';
        else:
            $show_search = 0;
        endif;
        $sticky_header_option = $rectified_magazine_theme_options['rectified-magazine-enable-sticky-primary-menu'];
        if($sticky_header_option == 1){
            $sticky_header_class = 'sticky-header';

        }else{
            $sticky_header_class = '';
        }

        $menu_alignment = $rectified_magazine_theme_options['rectified-magazine-change-primary-menu-position'];
        if ($menu_alignment == 'default-menu-position') {
            $menu_alignment_class = "ct-center-aligned";
        }else{
            $menu_alignment_class = 'ct-left-aligned';
        }
        if(!empty($search_class)){
            $menu_class = $menu_alignment_class.' '.$search_class;
        }else{
            $menu_class = $menu_alignment_class;
        }
        ?>
        <div class="rectified-magazine-header-left-logo-wrapper logo-wrapper-block <?php echo $sticky_header_class; ?>">
        <div class="site-branding" <?php if (!empty($has_header_image)) { ?> style="background-image: url(<?php echo header_image(); ?>);" <?php } ?>>
            <div class="container-inner">
                <div class="rectified-magazine-header-block rectified-magazine-header-left-logo" id="site-navigation">
                    <div class="rectified-magazine-logo-main-container">
                        <div class="rectified-magazine-logo-container text-center">
                            <?php
                            if (function_exists('the_custom_logo')) {

                                the_custom_logo();

                            }
                            if (is_front_page() && is_home()) : ?>
                                <h1 class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>"
                                  rel="home"><?php bloginfo('name'); ?></a></h1>
                                  <?php else : ?>
                                    <p class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>"
                                       rel="home"><?php bloginfo('name'); ?></a></p>
                                       <?php
                                   endif;

                                   $description = get_bloginfo('description', 'display');
                                   if ($description || is_customize_preview()) : ?>
                                    <p class="site-description"><?php echo $description; /* WPCS: xss ok. */ ?></p>
                                    <?php
                                endif; ?>
                            </div> <!-- rectified-magazine-logo-container -->


                            <div class="navbar-header clear">
                                <button class="menu-toggle" aria-controls="primary-menu"
                                aria-expanded="false"><i class="fa fa-bars" aria-hidden="true"></i></button>
                            </div>
                        </div> <!-- .rectified-magazine-logo-main-container -->
                        <div class="rectified-magazine-menu-container clear <?php echo $menu_class; ?>">
                            <nav id="" class="main-navigation">
                                <ul id="primary-menu" class="nav navbar-nav nav-menu">
                                    <?php
                                    if ($rectified_magazine_theme_options['rectified-magazine-enable-menu-home-icon'] == 1):
                                        if (is_front_page()) {
                                            $home_class = 'current-menu-item';
                                        } else {
                                            $home_class = '';
                                        }

                                        ?>
                                        <li class="<?php echo $home_class; ?>"><a href="<?php echo esc_url(home_url('/')); ?>">
                                                <i class="fa fa-home"></i> </a></li>
                                    <?php
                                    endif;
                                    ?>
                                    <?php
                                    wp_nav_menu(array(
                                        'theme_location' => 'menu-1',
                                        'items_wrap' => '%3$s',
                                        'container' => false
                                    ));
                                    ?>
                                </ul>
                            </nav><!-- #site-navigation -->
                            <?php
                            if ($show_search == 1):
                                ?>
                                <div class="ct-menu-search"><a class="search-icon-box" href="#"> <i class="fa fa-search"></i>
                                    </a></div>
                                <div class="top-bar-search">
                                    <?php get_search_form(); ?>
                                    <button type="button" class="close">
                                    </button>
                                </div>
                            <?php
                            endif;
                            ?>
                        </div> <!-- rectified-magazine-menu-container -->
                    </div> <!-- .header-block -->
                </div>
            </div> <!-- .site-branding -->
            <?php
            //Check if header advertisement is enabled from customizer
            if ($rectified_magazine_theme_options['rectified-magazine-enable-ads-header'] == 1):
                ?>
                <div class="header-adv-section text-center">
                    <div class="container-inner clear">
                        <?php

                        /**
                         * rectified_magazine_header_ads hook.
                         *
                         * @since 1.0.0
                         *
                         */
                        do_action('rectified_magazine_header_ads');
                        ?>
                    </div>
                </div>
                <?php


            endif;
            ?>
        </div>
            <?php

        }
}
add_action('rectified_magazine_main_header_right_menu', 'rectified_magazine_main_header_right_menu', 10);