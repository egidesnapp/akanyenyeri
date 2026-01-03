<?php
/**
 * Rectified Magazine Theme Customizer default values
 *
 * @package Rectified Magazine
 */
if ( !function_exists('rectified_magazine_default_theme_options_values') ) :
    function rectified_magazine_default_theme_options_values() {
        $default_theme_options = array(

             /*General Colors*/
            'rectified-magazine-primary-color' => '#3F51B5',
            'rectified-magazine-site-title-hover'=> '',
            'rectified-magazine-site-tagline'=> '',
            

            /*Logo Section Colors*/
            'rectified-magazine-logo-section-background' => '#4a4a50',

            /*logo position*/
            'rectified-magazine-custom-logo-position'=> 'left',

            /*Site Layout Options*/
            'rectified-magazine-site-layout-options'=>'full-width',
            'rectified-magazine-boxed-width-options'=> 1500,

            /*Top Header Section Default Value*/
            'rectified-magazine-enable-top-header'=> true,
            'rectified-magazine-enable-top-header-social'=> true,
            'rectified-magazine-enable-top-header-menu'=> true,
            'rectified-magazine-enable-top-header-date' => true,
            'rectified-magazine-top-header-date-format'=>'default-date',
            
            /*Treding News*/
            'rectified-magazine-enable-trending-news' => true,
            'rectified-magazine-enable-trending-news-text'=> esc_html__('Top News','rectified-magazine'),
            'rectified-magazine-trending-news-category'=> 0,

            /*Menu Section*/
            'rectified-magazine-enable-menu-section-search'=> true,
            'rectified-magazine-enable-sticky-primary-menu'=> true,
            'rectified-magazine-enable-menu-home-icon' => true,
            'rectified-magazine-enable-weather' => '',

            /*Header Ads Default Value*/
            'rectified-magazine-enable-ads-header'=> false,
            'rectified-magazine-header-ads-image'=> '',
            'rectified-magazine-header-ads-image-link'=> 'https://www.candidthemes.com/themes/rectified-magazine-pro/',

            /*Slider Section Default Value*/
            'rectified-magazine-enable-slider' => true,
            'rectified-magazine-select-category'=> 0,
            'rectified-magazine-select-category-featured-right' => 0,
            'rectified-magazine-slider-post-date'=> false,
            'rectified-magazine-slider-post-author'=> false,
            'rectified-magazine-slider-post-category'=> true,
            'rectified-magazine-slider-post-read-time'=> false,
            

            /*Sidebars Default Value*/
            'rectified-magazine-sidebar-blog-page'=>'right-sidebar',
            'rectified-magazine-sidebar-front-page' => 'right-sidebar',
            'rectified-magazine-sidebar-archive-page'=> 'right-sidebar',

            /*Blog Page Default Value*/
            'rectified-magazine-content-show-from'=>'excerpt',
            'rectified-magazine-excerpt-length'=>25,
            'rectified-magazine-pagination-options'=>'numeric',
            'rectified-magazine-read-more-text'=> esc_html__('','rectified-magazine'),
            'rectified-magazine-enable-blog-author'=> true,
            'rectified-magazine-enable-blog-category'=> true,
            'rectified-magazine-enable-blog-date'=> true,
            'rectified-magazine-enable-blog-comment'=> true,
            'rectified-magazine-enable-blog-tags'=> false,

            /*Single Page Default Value*/
            'rectified-magazine-single-page-featured-image'=> true,
            'rectified-magazine-single-page-related-posts'=> true,
            'rectified-magazine-single-page-related-posts-title'=> esc_html__('Related Posts','rectified-magazine'),
            'rectified-magazine-enable-single-category' => true,
            'rectified-magazine-enable-single-date' => true,
            'rectified-magazine-enable-single-author' => true,
            

            /*Sticky Sidebar Options*/
            'rectified-magazine-enable-sticky-sidebar'=> true,

            /*Social Share Options*/
            'rectified-magazine-enable-single-sharing'=> true,
            'rectified-magazine-enable-blog-sharing'=> false,
            'rectified-magazine-enable-static-page-sharing' => false,

            /*Footer Section*/
            'rectified-magazine-footer-copyright' =>  esc_html__('All Rights Reserved 2025.','rectified-magazine'),
            'rectified-magazine-go-to-top'=> true,
            
            
            /*Extra Options*/
            'rectified-magazine-extra-breadcrumb'=> true,
            'rectified-magazine-breadcrumb-text'=>  esc_html__('You are Here','rectified-magazine'),
            'rectified-magazine-extra-preloader'=> true,
            'rectified-magazine-front-page-content' => false,
            'rectified-magazine-extra-hide-read-time' => false,
            'rectified-magazine-extra-post-formats-icons'=> true,
            'rectified-magazine-enable-category-color' => false,
            'rectified-magazine-font-awesome-version-loading' => 'version-6',

            'rectified-magazine-breadcrumb-display-from-option'=> 'theme-default',
            'rectified-magazine-breadcrumb-display-from-plugins'=> 'yoast',

        );
        return apply_filters( 'rectified_magazine_default_theme_options_values', $default_theme_options );
    }
endif;

/**
 *  Rectified Magazine Theme Options and Settings
 *
 * @since Rectified Magazine 1.0.0
 *
 * @param null
 * @return array rectified_magazine_get_options_value
 *
 */
if ( !function_exists('rectified_magazine_get_options_value') ) :
    function rectified_magazine_get_options_value() {
        $rectified_magazine_default_theme_options_values = rectified_magazine_default_theme_options_values();
        $rectified_magazine_get_options_value = get_theme_mod( 'rectified_magazine_options');
        if( is_array( $rectified_magazine_get_options_value )){
            return array_merge( $rectified_magazine_default_theme_options_values, $rectified_magazine_get_options_value );
        }
        else{
            return $rectified_magazine_default_theme_options_values;
        }
    }
endif;
