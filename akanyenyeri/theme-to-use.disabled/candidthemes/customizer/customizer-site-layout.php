<?php
/**
 *  Rectified Magazine Site Layout Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */
/*Site Layout Section*/
$wp_customize->add_section( 'rectified_magazine_site_layout_section', array(
   'priority'       => 35,
   'capability'     => 'edit_theme_options',
   'theme_supports' => '',
   'title'          => __( 'Site Layout Options', 'rectified-magazine' ),
   'panel'     => 'rectified_magazine_panel',
) );
/*Site Layout settings*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-site-layout-options]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-site-layout-options'],
    'sanitize_callback' => 'rectified_magazine_sanitize_select'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-site-layout-options]', array(
   'choices' => array(
    'boxed'    => __('Boxed Layout','rectified-magazine'),
    'full-width'    => __('Full Width','rectified-magazine')
),
   'label'     => __( 'Site Layout Option', 'rectified-magazine' ),
   'description' => __('You can make the overall site full width or boxed in nature.', 'rectified-magazine'),
   'section'   => 'rectified_magazine_site_layout_section',
   'settings'  => 'rectified_magazine_options[rectified-magazine-site-layout-options]',
   'type'      => 'select',
   'priority'  => 30,
) );

/*callback functions header section*/
if ( !function_exists('rectified_magazine_boxed_layout_width') ) :
  function rectified_magazine_boxed_layout_width(){
      global $rectified_magazine_theme_options;
      $rectified_magazine_theme_options = rectified_magazine_get_options_value();
      $boxed_width = esc_attr($rectified_magazine_theme_options['rectified-magazine-site-layout-options']);
      if( 'boxed' == $boxed_width ){
          return true;
      }
      else{
          return false;
      }
  }
endif;

/*Site Layout width*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-boxed-width-options]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-boxed-width-options'],
    'sanitize_callback' => 'absint'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-boxed-width-options]', array(
   'label'     => __( 'Set Boxed Width Range', 'rectified-magazine' ),
   'description' => __('Make the required width of your boxed layout. Select a custom width for the boxed layout. Minimim is 1200px and maximum is 1500px.', 'rectified-magazine'),
   'section'   => 'rectified_magazine_site_layout_section',
   'settings'  => 'rectified_magazine_options[rectified-magazine-boxed-width-options]',
   'type'      => 'range',
   'priority'  => 30,
   'input_attrs' => array(
          'min' => 1200,
          'max' => 1500,
        ),
   'active_callback' => 'rectified_magazine_boxed_layout_width',
) );