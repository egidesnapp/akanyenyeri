<?php
/**
 *  Rectified Magazine Category Color Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */
/*Category Color Options*/
$wp_customize->add_section('rectified_magazine_category_color_setting', array(
    'priority'      => 72,
    'title'         => __('Category Color', 'rectified-magazine'),
    'description'   => __('You can select the different color for each category.', 'rectified-magazine'),
    'panel'          => 'rectified_magazine_panel'
));

/*Customizer color*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-category-color]', array(
   'capability'        => 'edit_theme_options',
   'transport' => 'refresh',
   'default'           => $default['rectified-magazine-enable-category-color'],
   'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-category-color]', array(
   'label'     => __( 'Enable Category Color', 'rectified-magazine' ),
   'description' => __('Checked to enable the category color and select the required color for each category.', 'rectified-magazine'),
   'section'   => 'rectified_magazine_category_color_setting',
   'settings'  => 'rectified_magazine_options[rectified-magazine-enable-category-color]',
   'type'      => 'checkbox',
   'priority'  => 1,
) );

/*callback functions header section*/
if ( !function_exists('rectified_magazine_colors_active_callback') ) :
  function rectified_magazine_colors_active_callback(){
      global $rectified_magazine_theme_options;
      $rectified_magazine_theme_options = rectified_magazine_get_options_value();
      $enable_color = absint($rectified_magazine_theme_options['rectified-magazine-enable-category-color']);
      if( 1 == $enable_color ){
          return true;
      }
      else{
          return false;
      }
  }
endif;

$i = 1;
$args = array(
    'orderby' => 'id',
    'hide_empty' => 0
);
$categories = get_categories( $args );
$wp_category_list = array();
foreach ($categories as $category_list ) {
    $wp_category_list[$category_list->cat_ID] = $category_list->cat_name;

    $wp_customize->add_setting('rectified_magazine_options[cat-'.get_cat_id($wp_category_list[$category_list->cat_ID]).']', array(
        'default'           => $default['rectified-magazine-primary-color'],
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    $wp_customize->add_control(
    	new WP_Customize_Color_Control(
    		$wp_customize,
		    'rectified_magazine_options[cat-'.get_cat_id($wp_category_list[$category_list->cat_ID]).']',
		    array(
		    	'label'     => sprintf(__('"%s" Color', 'rectified-magazine'), $wp_category_list[$category_list->cat_ID] ),
			    'section'   => 'rectified_magazine_category_color_setting',
			    'settings'  => 'rectified_magazine_options[cat-'.get_cat_id($wp_category_list[$category_list->cat_ID]).']',
			    'priority'  => $i,
                'active_callback'   => 'rectified_magazine_colors_active_callback'
		    )
	    )
    );
    $i++;
}