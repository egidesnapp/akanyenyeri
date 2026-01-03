<?php
/**
 *  Rectified Magazine Breadcrumb Settings Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */
/*Breadcrumb Options*/
$wp_customize->add_section( 'rectified_magazine_breadcrumb_options', array(
    'priority'       => 73,
    'capability'     => 'edit_theme_options',
    'theme_supports' => '',
    'title'          => __( 'Breadcrumb Settings', 'rectified-magazine' ),
    'panel'          => 'rectified_magazine_panel',
) );

/*Breadcrumb Enable*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-extra-breadcrumb]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-extra-breadcrumb'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-extra-breadcrumb]', array(
    'label'     => __( 'Enable Breadcrumb', 'rectified-magazine' ),
    'description' => __( 'Breadcrumb will appear on all pages except home page', 'rectified-magazine' ),
    'section'   => 'rectified_magazine_breadcrumb_options',
    'settings'  => 'rectified_magazine_options[rectified-magazine-extra-breadcrumb]',
    'type'      => 'checkbox',
    'priority'  => 15,
) );

/*callback functions breadcrumb enable*/
if ( !function_exists('rectified_magazine_breadcrumb_selection_enable') ) :
  function rectified_magazine_breadcrumb_selection_enable(){
      global $rectified_magazine_theme_options;
      $rectified_magazine_theme_options = rectified_magazine_get_options_value();
      $enable_bc = absint($rectified_magazine_theme_options['rectified-magazine-extra-breadcrumb']);
      if( $enable_bc == 1){
          return true;
      }
      else{
          return false;
      }
  }
endif;

/*Show Breadcrumb Types Selection*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-breadcrumb-display-from-option]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-breadcrumb-display-from-option'],
    'sanitize_callback' => 'rectified_magazine_sanitize_select'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-breadcrumb-display-from-option]', array(
    'choices' => array(
        'theme-default'    => __('Theme Default Breadcrumb','rectified-magazine'),
        'plugin-breadcrumb'    => __('Plugins Breadcrumb','rectified-magazine')
    ),
    'label'     => __( 'Select the Breadcrumb Show Option', 'rectified-magazine' ),
    'description' => __('Theme has its own breadcrumb. If you want to use the plugin breadcrumb, you need to enable the breadcrumb on the respected plugins first. Check plugin settings and enable it.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_breadcrumb_options',
    'settings'  => 'rectified_magazine_options[rectified-magazine-breadcrumb-display-from-option]',
    'type'      => 'select',
    'priority'  => 15,
    'active_callback'=> 'rectified_magazine_breadcrumb_selection_enable',
) );

/*callback functions breadcrumb*/
if ( !function_exists('rectified_magazine_breadcrumb_selection_option') ) :
  function rectified_magazine_breadcrumb_selection_option(){
    global $rectified_magazine_theme_options;
      $rectified_magazine_theme_options = rectified_magazine_get_options_value();
      $enable_breadcrumb = absint($rectified_magazine_theme_options['rectified-magazine-extra-breadcrumb']);
      $breadcrumb_selection = esc_attr($rectified_magazine_theme_options['rectified-magazine-breadcrumb-display-from-option']);
      if( 'theme-default' == $breadcrumb_selection && $enable_breadcrumb == 1){
          return true;
      }
      else{
          return false;
      }
  }
endif;

/*callback functions breadcrumb*/
if ( !function_exists('rectified_magazine_breadcrumb_selection_plugin') ) :
  function rectified_magazine_breadcrumb_selection_plugin(){
      global $rectified_magazine_theme_options;
      $rectified_magazine_theme_options = rectified_magazine_get_options_value();
      $enable_breadcrumb_plugin = absint($rectified_magazine_theme_options['rectified-magazine-extra-breadcrumb']);
      $breadcrumb_selection_plugin = esc_attr($rectified_magazine_theme_options['rectified-magazine-breadcrumb-display-from-option']);
      if( 'plugin-breadcrumb' == $breadcrumb_selection_plugin && $enable_breadcrumb_plugin == 1){
          return true;
      }
      else{
          return false;
      }
  }
endif;

/*Breadcrumb Text*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-breadcrumb-text]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-breadcrumb-text'],
    'sanitize_callback' => 'sanitize_text_field'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-breadcrumb-text]', array(
    'label'     => __( 'Breadcrumb Text', 'rectified-magazine' ),
    'description' => __( 'Write your own text in place of You are Here', 'rectified-magazine' ),
    'section'   => 'rectified_magazine_breadcrumb_options',
    'settings'  => 'rectified_magazine_options[rectified-magazine-breadcrumb-text]',
    'type'      => 'text',
    'priority'  => 15,
    'active_callback' => 'rectified_magazine_breadcrumb_selection_option',
) );


/*Show Breadcrumb From Plugins*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-breadcrumb-display-from-plugins]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-breadcrumb-display-from-plugins'],
    'sanitize_callback' => 'rectified_magazine_sanitize_select'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-breadcrumb-display-from-plugins]', array(
    'choices' => array(
        'yoast'    => __('Yoast SEO Breadcrumb','rectified-magazine'),
        'rank-math'    => __('Rank Math Breadcrumb','rectified-magazine'),
        'navxt'    => __('NavXT Breadcrumb','rectified-magazine')
    ),
    'label'     => __( 'Select the Breadcrumb From Plugins', 'rectified-magazine' ),
    'description' => __('Theme has its own breadcrumb. If you want to use the plugin breadcrumb, you need to enable the breadcrumb on the respected plugins first. Check plugin settings and enable it.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_breadcrumb_options',
    'settings'  => 'rectified_magazine_options[rectified-magazine-breadcrumb-display-from-plugins]',
    'type'      => 'select',
    'priority'  => 15,
    'active_callback'=> 'rectified_magazine_breadcrumb_selection_plugin',
) );