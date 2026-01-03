<?php
/**
 *  Rectified Magazine Social Share Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */
/*Top Header Options*/
$wp_customize->add_section( 'rectified_magazine_social_share_section', array(
   'priority'       => 87,
   'capability'     => 'edit_theme_options',
   'theme_supports' => '',
   'title'          => __( 'Social Share Options', 'rectified-magazine' ),
   'panel'     => 'rectified_magazine_panel',
) );

/*Blog Page Social Share*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-blog-sharing]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-blog-sharing'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-blog-sharing]', array(
    'label'     => __( 'Enable Social Sharing', 'rectified-magazine' ),
    'description' => __('Checked to Enable Social Sharing In Home Page,  Search Page and Archive page.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_social_share_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-blog-sharing]',
    'type'      => 'checkbox',
    'priority'  => 10,
) );

/* Single Page social sharing*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-single-sharing]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-single-sharing'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-single-sharing]', array(
    'label'     => __( 'Social Sharing on Blog Page', 'rectified-magazine' ),
    'description' => __('Checked to Enable Social Sharing In Single post and page.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_social_share_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-single-sharing]',
    'type'      => 'checkbox',
    'priority'  => 20,
) );

/* Single Page social sharing*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-single-sharing]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-single-sharing'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-single-sharing]', array(
    'label'     => __( 'Social Sharing on Single Post', 'rectified-magazine' ),
    'description' => __('Checked to Enable Social Sharing In Single post and page.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_social_share_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-single-sharing]',
    'type'      => 'checkbox',
    'priority'  => 20,
) );

/* Static Front Page social sharing*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-static-page-sharing]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-static-page-sharing'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-static-page-sharing]', array(
    'label'     => __( 'Social Sharing on Static Front Page', 'rectified-magazine' ),
    'description' => __('Checked to Enable Social Sharing In static front page.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_social_share_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-static-page-sharing]',
    'type'      => 'checkbox',
    'priority'  => 20,
) );