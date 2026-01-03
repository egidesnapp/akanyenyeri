<?php
/**
 *  Rectified Magazine Slider Featured Section Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */
/*Slider Options*/
$wp_customize->add_section( 'rectified_magazine_slider_section', array(
 'priority'       => 25,
 'capability'     => 'edit_theme_options',
 'theme_supports' => '',
 'title'          => __( 'Featured Section', 'rectified-magazine' ),
 'panel' 		 => 'rectified_magazine_panel',
) );
/*callback functions slider*/
if ( !function_exists('rectified_magazine_slider_active_callback') ) :
  function rectified_magazine_slider_active_callback(){
    global $rectified_magazine_theme_options;
    $rectified_magazine_theme_options = rectified_magazine_get_options_value();
    $enable_slider = absint($rectified_magazine_theme_options['rectified-magazine-enable-slider']);
    if( 1 == $enable_slider ){
      return true;
    }
    else{
      return false;
    }
  }
endif;
/*Slider Enable Option*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-slider]', array(
 'capability'        => 'edit_theme_options',
 'transport' => 'refresh',
 'default'           => $default['rectified-magazine-enable-slider'],
 'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-slider]', array(
 'label'     => __( 'Enable Featured Section', 'rectified-magazine' ),
 'description' => __('Checked to Featured Section In Home Page.', 'rectified-magazine'),
 'section'   => 'rectified_magazine_slider_section',
 'settings'  => 'rectified_magazine_options[rectified-magazine-enable-slider]',
 'type'      => 'checkbox',
 'priority'  => 10,
) );
/*Slider Category Left Selection*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-select-category]', array(
  'capability'        => 'edit_theme_options',
  'transport' => 'refresh',
  'default'           => $default['rectified-magazine-select-category'],
  'sanitize_callback' => 'absint'
) );
$wp_customize->add_control(
  new Rectified_Magazine_Customize_Category_Dropdown_Control(
    $wp_customize,
    'rectified_magazine_options[rectified-magazine-select-category]',
    array(
      'label'     => __( 'Select Category For Featured Left Section', 'rectified-magazine' ),
      'description' => __('From the dropdown select the category for the featured left section. Category having post will display in below dropdown.', 'rectified-magazine'),
      'section'   => 'rectified_magazine_slider_section',
      'settings'  => 'rectified_magazine_options[rectified-magazine-select-category]',
      'type'      => 'category_dropdown',
      'priority'  => 10,
      'active_callback'=>'rectified_magazine_slider_active_callback'
    )
  )
);

/*Slider Category Right Selection*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-select-category-featured-right]', array(
  'capability'        => 'edit_theme_options',
  'transport' => 'refresh',
  'default'           => $default['rectified-magazine-select-category-featured-right'],
  'sanitize_callback' => 'absint'
) );
$wp_customize->add_control(
  new Rectified_Magazine_Customize_Category_Dropdown_Control(
    $wp_customize,
    'rectified_magazine_options[rectified-magazine-select-category-featured-right]',
    array(
      'label'     => __( 'Select Category For Featured Right Section', 'rectified-magazine' ),
      'description' => __('From the dropdown select the category for the featured right section. Category having post will display in below dropdown.', 'rectified-magazine'),
      'section'   => 'rectified_magazine_slider_section',
      'settings'  => 'rectified_magazine_options[rectified-magazine-select-category-featured-right]',
      'type'      => 'category_dropdown',
      'priority'  => 10,
      'active_callback'=>'rectified_magazine_slider_active_callback'
    )
  )
);


/*Enable Category*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-slider-post-category]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-slider-post-category'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-slider-post-category]', array(
    'label'     => __( 'Enable the Post Category', 'rectified-magazine' ),
    'description' => __('You can change the category color from Color Options.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_slider_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-slider-post-category]',
    'type'      => 'checkbox',
    'active_callback'=>'rectified_magazine_slider_active_callback',
    'priority'  => 10,
) );

/*Enable Read Time*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-slider-post-read-time]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-slider-post-read-time'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-slider-post-read-time]', array(
    'label'     => __( 'Enable the Post Read Time', 'rectified-magazine' ),
    'description' => __('Read time can managed from Extra Options. Default word is 200 per minute.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_slider_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-slider-post-read-time]',
    'type'      => 'checkbox',
    'active_callback'=>'rectified_magazine_slider_active_callback',
    'priority'  => 10,
) );

/*Enable Date*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-slider-post-date]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-slider-post-date'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-slider-post-date]', array(
    'label'     => __( 'Enable the Post Date', 'rectified-magazine' ),
    'description' => __('Show or Hide the Post Date from the featured posts.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_slider_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-slider-post-date]',
    'type'      => 'checkbox',
    'active_callback'=>'rectified_magazine_slider_active_callback',
    'priority'  => 10,
) );
/*Enable Author*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-slider-post-author]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-slider-post-author'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-slider-post-author]', array(
    'label'     => __( 'Enable the Post Author', 'rectified-magazine' ),
    'description' => __('Show or Hide the Post Author from the featured posts.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_slider_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-slider-post-author]',
    'type'      => 'checkbox',
    'active_callback'=>'rectified_magazine_slider_active_callback',
    'priority'  => 10,
) );