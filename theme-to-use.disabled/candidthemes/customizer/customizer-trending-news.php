<?php
/**
 *  Rectified Magazine Top Header Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */
/*Top Header Options*/
$wp_customize->add_section( 'rectified_magazine_trending_news_section', array(
   'priority'       => 20,
   'capability'     => 'edit_theme_options',
   'theme_supports' => '',
   'title'          => __( 'Trending News Options', 'rectified-magazine' ),
   'panel'     => 'rectified_magazine_panel',
) );

/*Trending News*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-trending-news]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-trending-news'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-trending-news]', array(
    'label'     => __( 'Trending News in Header', 'rectified-magazine' ),
    'description' => __('Trending News will appear on the top header.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_trending_news_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-trending-news]',
    'type'      => 'checkbox',
    'priority'  => 5,
) );


/*callback functions header section*/
if ( !function_exists('rectified_magazine_header_trending_active_callback') ) :
  function rectified_magazine_header_trending_active_callback(){
      global $rectified_magazine_theme_options;
      $rectified_magazine_theme_options = rectified_magazine_get_options_value();
      $enable_trending = absint($rectified_magazine_theme_options['rectified-magazine-enable-trending-news']);
      if( 1 == $enable_trending   ){
          return true;
      }
      else{
          return false;
      }
  }
endif;

/*Trending News Category Selection*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-trending-news-category]', array(
  'capability'        => 'edit_theme_options',
  'transport' => 'refresh',
  'default'           => $default['rectified-magazine-trending-news-category'],
  'sanitize_callback' => 'absint'
) );
$wp_customize->add_control(
  new Rectified_Magazine_Customize_Category_Dropdown_Control(
    $wp_customize,
    'rectified_magazine_options[rectified-magazine-trending-news-category]',
    array(
      'label'     => __( 'Select Category For Trending News', 'rectified-magazine' ),
      'description' => __('Select the category from dropdown. It will appear on the header.', 'rectified-magazine'),
      'section'   => 'rectified_magazine_trending_news_section',
      'settings'  => 'rectified_magazine_options[rectified-magazine-trending-news-category]',
      'type'      => 'category_dropdown',
      'priority'  => 5,
      'active_callback'=>'rectified_magazine_header_trending_active_callback'
    )
  )
);

/*Trending News*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-trending-news-text]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-trending-news-text'],
    'sanitize_callback' => 'sanitize_text_field'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-trending-news-text]', array(
    'label'     => __( 'Trending News Text', 'rectified-magazine' ),
    'description' => __('Write your own text in place of Trending news.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_trending_news_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-trending-news-text]',
    'type'      => 'text',
    'priority'  => 5,
    'active_callback'=>'rectified_magazine_header_trending_active_callback'
) );