<?php
/**
 *  Rectified Magazine Menu Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */
/*Menu Options*/
$wp_customize->add_section( 'rectified_magazine_primary_menu_section', array(
   'priority'       => 25,
   'capability'     => 'edit_theme_options',
   'theme_supports' => '',
   'title'          => __( 'Menu Section Options', 'rectified-magazine' ),
   'panel'     => 'rectified_magazine_panel',
) );

/*Enable Search Icons In Header*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-sticky-primary-menu]', array(
   'capability'        => 'edit_theme_options',
   'transport' => 'refresh',
   'default'           => $default['rectified-magazine-enable-sticky-primary-menu'],
   'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-sticky-primary-menu]', array(
   'label'     => __( 'Enable Primary Menu Sticky', 'rectified-magazine' ),
   'description' => __('The main primary menu will be sticky.', 'rectified-magazine'),
   'section'   => 'rectified_magazine_primary_menu_section',
   'settings'  => 'rectified_magazine_options[rectified-magazine-enable-sticky-primary-menu]',
   'type'      => 'checkbox',
   'priority'  => 5,
) );

/*Enable Search Icons In Header*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-menu-section-search]', array(
   'capability'        => 'edit_theme_options',
   'transport' => 'refresh',
   'default'           => $default['rectified-magazine-enable-menu-section-search'],
   'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-menu-section-search]', array(
   'label'     => __( 'Enable Search Icons', 'rectified-magazine' ),
   'description' => __('You can show the search field in header.', 'rectified-magazine'),
   'section'   => 'rectified_magazine_primary_menu_section',
   'settings'  => 'rectified_magazine_options[rectified-magazine-enable-menu-section-search]',
   'type'      => 'checkbox',
   'priority'  => 5,
) );

/*Enable Home Icons In Menu*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-menu-home-icon]', array(
   'capability'        => 'edit_theme_options',
   'transport' => 'refresh',
   'default'           => $default['rectified-magazine-enable-menu-home-icon'],
   'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-menu-home-icon]', array(
   'label'     => __( 'Enable Home Icons', 'rectified-magazine' ),
   'description' => __('Home Icon will displayed in menu.', 'rectified-magazine'),
   'section'   => 'rectified_magazine_primary_menu_section',
   'settings'  => 'rectified_magazine_options[rectified-magazine-enable-menu-home-icon]',
   'type'      => 'checkbox',
   'priority'  => 5,
) );

/*Enable Weather Widgets in Header*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-weather]', array(
   'capability'        => 'edit_theme_options',
   'transport' => 'refresh',
   'default'           => $default['rectified-magazine-enable-weather'],
   'sanitize_callback' => 'wp_kses_post'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-weather]', array(
   'label'     => __( 'Weather App', 'rectified-magazine' ),
   'description' => __('Enter ShortCode from the Weather Plugin', 'rectified-magazine'),
   'section'   => 'rectified_magazine_primary_menu_section',
   'settings'  => 'rectified_magazine_options[rectified-magazine-enable-weather]',
   'type'      => 'text',
   'priority'  => 15,
) );