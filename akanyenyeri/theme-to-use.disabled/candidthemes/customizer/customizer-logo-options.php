<?php
/**
 *  Rectified Magazine Logo Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */
/*Logo Options*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-custom-logo-position]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-custom-logo-position'],
    'sanitize_callback' => 'rectified_magazine_sanitize_select'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-custom-logo-position]', array(
   'choices' => array(
    'default'    => __('Left Align','rectified-magazine'),
    'center'    => __('Center Logo','rectified-magazine')
),
   'label'     => __( 'Logo Position Option', 'rectified-magazine' ),
   'description' => __('Your logo will be in the center position.', 'rectified-magazine'),
   'section'   => 'title_tagline',
   'settings'  => 'rectified_magazine_options[rectified-magazine-custom-logo-position]',
   'type'      => 'select',
   'priority'  => 30,
) );