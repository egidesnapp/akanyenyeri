<?php
/**
 *  Rectified Magazine Top Header Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */
/*Top Header Options*/
$wp_customize->add_section( 'rectified_magazine_header_section', array(
   'priority'       => 15,
   'capability'     => 'edit_theme_options',
   'theme_supports' => '',
   'title'          => __( 'Top Header Options', 'rectified-magazine' ),
   'panel' 		 => 'rectified_magazine_panel',
) );
/*callback functions header section*/
if ( !function_exists('rectified_magazine_header_active_callback') ) :
  function rectified_magazine_header_active_callback(){
      global $rectified_magazine_theme_options;
      $rectified_magazine_theme_options = rectified_magazine_get_options_value();
      $enable_header = absint($rectified_magazine_theme_options['rectified-magazine-enable-top-header']);
      if( 1 == $enable_header ){
          return true;
      }
      else{
          return false;
      }
  }
endif;
/*Enable Top Header Section*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-top-header]', array(
   'capability'        => 'edit_theme_options',
   'transport' => 'refresh',
   'default'           => $default['rectified-magazine-enable-top-header'],
   'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-top-header]', array(
   'label'     => __( 'Enable Top Header', 'rectified-magazine' ),
   'description' => __('Checked to show the top header section like search and social icons', 'rectified-magazine'),
   'section'   => 'rectified_magazine_header_section',
   'settings'  => 'rectified_magazine_options[rectified-magazine-enable-top-header]',
   'type'      => 'checkbox',
   'priority'  => 5,
) );
/*Enable Social Icons In Header*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-top-header-social]', array(
   'capability'        => 'edit_theme_options',
   'transport' => 'refresh',
   'default'           => $default['rectified-magazine-enable-top-header-social'],
   'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-top-header-social]', array(
   'label'     => __( 'Enable Social Icons', 'rectified-magazine' ),
   'description' => __('You can show the social icons here. Manage social icons from Appearance > Menus. Social Menu will display here.', 'rectified-magazine'),
   'section'   => 'rectified_magazine_header_section',
   'settings'  => 'rectified_magazine_options[rectified-magazine-enable-top-header-social]',
   'type'      => 'checkbox',
   'priority'  => 5,
   'active_callback'=>'rectified_magazine_header_active_callback'
) );

/*Enable Menu in top Header*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-top-header-menu]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-top-header-menu'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-top-header-menu]', array(
    'label'     => __( 'Menu in Header', 'rectified-magazine' ),
    'description' => __('Top Header Menu will display here. Go to Appearance < Menu.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_header_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-top-header-menu]',
    'type'      => 'checkbox',
    'priority'  => 5,
    'active_callback'=>'rectified_magazine_header_active_callback'
) );

/*Enable Date in top Header*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-top-header-date]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-top-header-date'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-top-header-date]', array(
    'label'     => __( 'Date in Header', 'rectified-magazine' ),
    'description' => __('Enable Date in Header', 'rectified-magazine'),
    'section'   => 'rectified_magazine_header_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-top-header-date]',
    'type'      => 'checkbox',
    'priority'  => 5,
    'active_callback'=>'rectified_magazine_header_active_callback'
) );

/*Date format*/
$wp_customize->add_setting('rectified_magazine_options[rectified-magazine-top-header-date-format]', array(
    'capability' => 'edit_theme_options',
    'transport' => 'refresh',
    'default' => $default['rectified-magazine-top-header-date-format'],
    'sanitize_callback' => 'rectified_magazine_sanitize_select'
));
$wp_customize->add_control('rectified_magazine_options[rectified-magazine-top-header-date-format]', array(
    'choices' => array(
        'default-date' => __('Theme Default Date Format', 'rectified-magazine'),
        'core-date' => __('Setting Date Fromat', 'rectified-magazine'),
    ),
    'label' => __('Select Date Format in Header', 'rectified-magazine'),
    'description' => __('You can have default format for date or Setting > General date format.', 'rectified-magazine'),
    'section' => 'rectified_magazine_header_section',
    'settings' => 'rectified_magazine_options[rectified-magazine-top-header-date-format]',
    'type' => 'select',
    'priority' => 5,
    'active_callback'=> 'rectified_magazine_header_active_callback',
));