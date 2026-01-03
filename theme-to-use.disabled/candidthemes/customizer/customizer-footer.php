<?php
/**
 *  Rectified Magazine Footer Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */
/*Footer Options*/
$wp_customize->add_section( 'rectified_magazine_footer_section', array(
   'priority'       => 85,
   'capability'     => 'edit_theme_options',
   'theme_supports' => '',
   'title'          => __( 'Footer Options', 'rectified-magazine' ),
   'panel' 		 => 'rectified_magazine_panel',
) );
/*Copyright Setting*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-footer-copyright]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-footer-copyright'],
    'sanitize_callback' => 'sanitize_text_field'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-footer-copyright]', array(
    'label'     => __( 'Copyright Text', 'rectified-magazine' ),
    'description' => __('Enter your own copyright text.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_footer_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-footer-copyright]',
    'type'      => 'text',
    'priority'  => 15,
) );
/*Go to Top Setting*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-go-to-top]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-go-to-top'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-go-to-top]', array(
    'label'     => __( 'Enable Go to Top', 'rectified-magazine' ),
    'description' => __('Checked to Enable Go to Top', 'rectified-magazine'),
    'section'   => 'rectified_magazine_footer_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-go-to-top]',
    'type'      => 'checkbox',
    'priority'  => 15,
) );