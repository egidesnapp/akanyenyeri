<?php
/**
 *  Rectified Magazine Single Page Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */
/*Single Page Options*/
$wp_customize->add_section( 'rectified_magazine_single_page_section', array(
   'priority'       => 68,
   'capability'     => 'edit_theme_options',
   'theme_supports' => '',
   'title'          => __( 'Single Post Options', 'rectified-magazine' ),
   'panel' 		 => 'rectified_magazine_panel',
) );

/*Featured Image Option*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-single-page-featured-image]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-single-page-featured-image'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-single-page-featured-image]', array(
    'label'     => __( 'Enable Featured Image', 'rectified-magazine' ),
    'description' => __('You can hide or show featured image on single page.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_single_page_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-single-page-featured-image]',
    'type'      => 'checkbox',
    'priority'  => 15,
) );
/*Enable Category*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-single-category]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-single-category'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-single-category]', array(
    'label'     => __( 'Enable Category', 'rectified-magazine' ),
    'description' => __('Checked to Enable Category In Single post and page.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_single_page_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-single-category]',
    'type'      => 'checkbox',
    'priority'  => 20,
) );
/*Enable Date*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-single-date]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-single-date'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-single-date]', array(
    'label'     => __( 'Enable Date', 'rectified-magazine' ),
    'description' => __('Checked to Enable Date In Single post and page.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_single_page_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-single-date]',
    'type'      => 'checkbox',
    'priority'  => 20,
) );
/*Enable Author*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-single-author]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-single-author'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-single-author]', array(
    'label'     => __( 'Enable Author', 'rectified-magazine' ),
    'description' => __('Checked to Enable Author In Single post and page.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_single_page_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-single-author]',
    'type'      => 'checkbox',
    'priority'  => 20,
) );

/*Related Post Options*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-single-page-related-posts]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-single-page-related-posts'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-single-page-related-posts]', array(
    'label'     => __( 'Enable Related Posts', 'rectified-magazine' ),
    'description' => __('3 Post from similar category will display at the end of the page. More Options is in Premium Version', 'rectified-magazine'),
    'section'   => 'rectified_magazine_single_page_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-single-page-related-posts]',
    'type'      => 'checkbox',
    'priority'  => 20,
) );
/*callback functions related posts*/
if ( !function_exists('rectified_magazine_related_post_callback') ) :
    function rectified_magazine_related_post_callback(){
        global $rectified_magazine_theme_options;
        $rectified_magazine_theme_options = rectified_magazine_get_options_value();
        $related_posts = absint($rectified_magazine_theme_options['rectified-magazine-single-page-related-posts']);
        if( 1 == $related_posts ){
            return true;
        }
        else{
            return false;
        }
    }
endif;
/*Related Post Title*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-single-page-related-posts-title]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-single-page-related-posts-title'],
    'sanitize_callback' => 'sanitize_text_field'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-single-page-related-posts-title]', array(
    'label'     => __( 'Related Posts Title', 'rectified-magazine' ),
    'description' => __('Give the appropriate title for related posts', 'rectified-magazine'),
    'section'   => 'rectified_magazine_single_page_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-single-page-related-posts-title]',
    'type'      => 'text',
    'priority'  => 20,
    'active_callback'=>'rectified_magazine_related_post_callback'
) );

