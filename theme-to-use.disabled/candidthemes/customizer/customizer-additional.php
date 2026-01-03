<?php 
/**
 *  Rectified Magazine Additional Settings Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */
/*Extra Options*/
$wp_customize->add_section( 'rectified_magazine_extra_options', array(
    'priority'       => 75,
    'capability'     => 'edit_theme_options',
    'theme_supports' => '',
    'title'          => __( 'Extra Options', 'rectified-magazine' ),
    'panel'          => 'rectified_magazine_panel',
) );

/*Preloader Enable*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-extra-preloader]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-extra-preloader'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-extra-preloader]', array(
    'label'     => __( 'Enable Preloader', 'rectified-magazine' ),
    'description' => __( 'It will enable the preloader on the website.', 'rectified-magazine' ),
    'section'   => 'rectified_magazine_extra_options',
    'settings'  => 'rectified_magazine_options[rectified-magazine-extra-preloader]',
    'type'      => 'checkbox',
    'priority'  => 15,
) );

/*Home Page Content*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-front-page-content]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-front-page-content'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-front-page-content]', array(
    'label'     => __( 'Hide Front Page Content', 'rectified-magazine' ),
    'description' => __( 'Checked to hide the front page content from front page.', 'rectified-magazine' ),
    'section'   => 'rectified_magazine_extra_options',
    'settings'  => 'rectified_magazine_options[rectified-magazine-front-page-content]',
    'type'      => 'checkbox',
    'priority'  => 15,
) );

/*Hide Post Format Icons*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-extra-post-formats-icons]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-extra-post-formats-icons'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-extra-post-formats-icons]', array(
    'label'     => __( 'Hide Post Formats Icons', 'rectified-magazine' ),
    'description' => __( 'Icons like camera, photo, video, audio will hide.', 'rectified-magazine' ),
    'section'   => 'rectified_magazine_extra_options',
    'settings'  => 'rectified_magazine_options[rectified-magazine-extra-post-formats-icons]',
    'type'      => 'checkbox',
    'priority'  => 15,
) );


/*Hide Read More Time*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-extra-hide-read-time]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-extra-hide-read-time'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-extra-hide-read-time]', array(
    'label'     => __( 'Hide Reading Time', 'rectified-magazine' ),
    'description' => __( 'You can hide the reading time in whole site.', 'rectified-magazine' ),
    'section'   => 'rectified_magazine_extra_options',
    'settings'  => 'rectified_magazine_options[rectified-magazine-extra-hide-read-time]',
    'type'      => 'checkbox',
    'priority'  => 15,
) );

/*Font awesome version loading*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-font-awesome-version-loading]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-font-awesome-version-loading'],
    'sanitize_callback' => 'rectified_magazine_sanitize_select'
 ) );
 $wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-font-awesome-version-loading]', array(
   'choices' => array(
    'version-4'    => __('Current Theme Used Version 4','rectified-magazine'),
    'version-5'   => __('Fontawesome Version 5','rectified-magazine'),
    'version-6'   => __('New Fontawesome Version 6','rectified-magazine'),
 ),
   'label'     => __( 'Select the preferred fontawesome version', 'rectified-magazine' ),
   'description' => __('You can select the latest fontawesome version to get more options for icons', 'rectified-magazine'),
   'section'   => 'rectified_magazine_extra_options',
   'settings'  => 'rectified_magazine_options[rectified-magazine-font-awesome-version-loading]',
   'type'      => 'select',
   'priority'  => 15,
 ) );