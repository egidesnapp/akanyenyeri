<?php
/**
 *  Rectified Magazine Sticky Sidebar Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */

/*Sticky Sidebar*/
$wp_customize->add_section( 'rectified_magazine_sticky_sidebar', array(
    'priority'       => 76,
    'capability'     => 'edit_theme_options',
    'theme_supports' => '',
    'title'          => __( 'Sticky Sidebar', 'rectified-magazine' ),
    'panel' 		 => 'rectified_magazine_panel',
) );
/*Sticky Sidebar Setting*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-sticky-sidebar]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-sticky-sidebar'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-sticky-sidebar]', array(
    'label'     => __( 'Sticky Sidebar Option', 'rectified-magazine' ),
    'description' => __('Enable and Disable sticky sidebar from this section.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_sticky_sidebar',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-sticky-sidebar]',
    'type'      => 'checkbox',
    'priority'  => 15,
) );