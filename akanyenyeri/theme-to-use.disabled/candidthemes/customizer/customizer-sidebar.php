<?php
/**
 *  Rectified Magazine Sidebar Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */
/*Blog Page Options*/
$wp_customize->add_section( 'rectified_magazine_sidebar_section', array(
   'priority'       => 40,
   'capability'     => 'edit_theme_options',
   'theme_supports' => '',
   'title'          => __( 'Sidebar Options', 'rectified-magazine' ),
   'panel' 		 => 'rectified_magazine_panel',
) );
/*Front Page Sidebar Layout*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-sidebar-blog-page]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-sidebar-blog-page'],
    'sanitize_callback' => 'rectified_magazine_sanitize_select'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-sidebar-blog-page]', array(
   'choices' => array(
    'right-sidebar'   => __('Right Sidebar','rectified-magazine'),
    'left-sidebar'    => __('Left Sidebar','rectified-magazine'),
    'no-sidebar'      => __('No Sidebar','rectified-magazine'),
    'middle-column'   => __('Middle Column','rectified-magazine')
),
   'label'     => __( 'Inner Pages Sidebar', 'rectified-magazine' ),
   'description' => __('This sidebar will work for all Pages', 'rectified-magazine'),
   'section'   => 'rectified_magazine_sidebar_section',
   'settings'  => 'rectified_magazine_options[rectified-magazine-sidebar-blog-page]',
   'type'      => 'select',
   'priority'  => 10,
) );

/*Front Page Sidebar Layout*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-sidebar-front-page]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-sidebar-front-page'],
    'sanitize_callback' => 'rectified_magazine_sanitize_select'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-sidebar-front-page]', array(
   'choices' => array(
    'right-sidebar'   => __('Right Sidebar','rectified-magazine'),
    'left-sidebar'    => __('Left Sidebar','rectified-magazine'),
    'no-sidebar'      => __('No Sidebar','rectified-magazine'),
    'middle-column'   => __('Middle Column','rectified-magazine')
),
   'label'     => __( 'Front Page Sidebar', 'rectified-magazine' ),
   'description' => __('This sidebar will work for Front Page', 'rectified-magazine'),
   'section'   => 'rectified_magazine_sidebar_section',
   'settings'  => 'rectified_magazine_options[rectified-magazine-sidebar-front-page]',
   'type'      => 'select',
   'priority'  => 10,
) );

/*Archive Page Sidebar Layout*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-sidebar-archive-page]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-sidebar-archive-page'],
    'sanitize_callback' => 'rectified_magazine_sanitize_select'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-sidebar-archive-page]', array(
   'choices' => array(
    'right-sidebar'   => __('Right Sidebar','rectified-magazine'),
    'left-sidebar'    => __('Left Sidebar','rectified-magazine'),
    'no-sidebar'      => __('No Sidebar','rectified-magazine'),
    'middle-column'   => __('Middle Column','rectified-magazine')
),
   'label'     => __( 'Archive Page Sidebar', 'rectified-magazine' ),
   'description' => __('This sidebar will work for all Archive Pages', 'rectified-magazine'),
   'section'   => 'rectified_magazine_sidebar_section',
   'settings'  => 'rectified_magazine_options[rectified-magazine-sidebar-archive-page]',
   'type'      => 'select',
   'priority'  => 10,
) );