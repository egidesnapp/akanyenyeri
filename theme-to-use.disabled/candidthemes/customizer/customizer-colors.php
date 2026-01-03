<?php
/**
 *  Rectified Magazine Color Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */

$wp_customize->add_panel(
    'colors',
    array(
        'title'    => __( 'Color Options', 'rectified-magazine' ),
        'priority' => 30, // Before Additional CSS.
    )
);
$wp_customize->add_section(
    'colors',
    array(
        'title' => __( 'General Colors', 'rectified-magazine' ),
        'panel' => 'colors',
    )
);

/* Site Title hover color */
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-site-title-hover]',
    array(
        'sanitize_callback' => 'sanitize_hex_color',
        'capability'        => 'edit_theme_options',
        'transport' => 'refresh',
        'default'           => $default['rectified-magazine-site-title-hover'],
    )
);
$wp_customize->add_control(
    new WP_Customize_Color_Control(
        $wp_customize,
        'rectified_magazine_options[rectified-magazine-site-title-hover]',
        array(
            'label'       => esc_html__( 'Site Title Hover Color', 'rectified-magazine' ),
            'description' => esc_html__( 'It will change the color of site title in hover.', 'rectified-magazine' ),
            'section'     => 'colors',
             'settings'  => 'rectified_magazine_options[rectified-magazine-site-title-hover]',
        )
    )
);

/* Site tagline color */
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-site-tagline]',
    array(
        'sanitize_callback' => 'sanitize_hex_color',
        'capability'        => 'edit_theme_options',
        'transport' => 'refresh',
        'default'           => $default['rectified-magazine-site-tagline'],
    )
);
$wp_customize->add_control(
    new WP_Customize_Color_Control(
        $wp_customize,
        'rectified_magazine_options[rectified-magazine-site-tagline]',
        array(
            'label'       => esc_html__( 'Site Tagline Color', 'rectified-magazine' ),
            'description' => esc_html__( 'It will change the color of site tagline color.', 'rectified-magazine' ),
            'section'     => 'colors',
        )
    )
);

/* Primary Color Section Inside Core Color Option */
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-primary-color]',
    array(
        'sanitize_callback' => 'sanitize_hex_color',
        'capability'        => 'edit_theme_options',
        'transport' => 'refresh',
        'default'           => $default['rectified-magazine-primary-color'],
    )
);
$wp_customize->add_control(
    new WP_Customize_Color_Control(
        $wp_customize,
        'rectified_magazine_options[rectified-magazine-primary-color]',
        array(
            'label'       => esc_html__( 'Primary Color', 'rectified-magazine' ),
            'description' => esc_html__( 'Applied to main color of site.', 'rectified-magazine' ),
            'section'     => 'colors',
        )
    )
);
/* Logo Section Colors */

$wp_customize->add_section(
    'logo_colors',
    array(
        'title' => __( 'Logo Section Colors', 'rectified-magazine' ),
        'panel' => 'colors',
    )
);

/* Colors background the logo */
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-logo-section-background]',
    array(
        'default'           => $default['rectified-magazine-logo-section-background'],
        'sanitize_callback' => 'sanitize_hex_color',
    )
);
$wp_customize->add_control(
    new WP_Customize_Color_Control(
        $wp_customize,
        'rectified_magazine_options[rectified-magazine-logo-section-background]',
        array(
            'label'       => esc_html__( 'Background Color', 'rectified-magazine' ),
            'description' => esc_html__( 'Will change the color of background logo.', 'rectified-magazine' ),
            'section'     => 'logo_colors',
        )
    )
);