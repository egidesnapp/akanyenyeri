<?php
/**
 *  Rectified Magazine Blog Page Option
 *
 * @since Rectified Magazine 1.0.0
 *
 */
/*Blog Page Options*/
$wp_customize->add_section( 'rectified_magazine_blog_page_section', array(
   'priority'       => 45,
   'capability'     => 'edit_theme_options',
   'theme_supports' => '',
   'title'          => __( 'Blog Section Options', 'rectified-magazine' ),
   'panel' 		 => 'rectified_magazine_panel',
) );

/*Blog Page Show content from*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-content-show-from]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-content-show-from'],
    'sanitize_callback' => 'rectified_magazine_sanitize_select'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-content-show-from]', array(
   'choices' => array(
    'excerpt'    => __('Excerpt','rectified-magazine'),
    'content'    => __('Content','rectified-magazine')
),
   'label'     => __( 'Select Content Display Option', 'rectified-magazine' ),
   'description' => __('You can enable excerpt from Screen Options inside post section of dashboard', 'rectified-magazine'),
   'section'   => 'rectified_magazine_blog_page_section',
   'settings'  => 'rectified_magazine_options[rectified-magazine-content-show-from]',
   'type'      => 'select',
   'priority'  => 10,
) );
/*Blog Page excerpt length*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-excerpt-length]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-excerpt-length'],
    'sanitize_callback' => 'absint'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-excerpt-length]', array(
    'label'     => __( 'Size of Excerpt Content', 'rectified-magazine' ),
    'description' => __('Enter the number per Words to show the content in blog page.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_blog_page_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-excerpt-length]',
    'type'      => 'number',
    'priority'  => 10,
) );
/*Blog Page Pagination Options*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-pagination-options]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-pagination-options'],
    'sanitize_callback' => 'rectified_magazine_sanitize_select'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-pagination-options]', array(
   'choices' => rectified_magazine_pagination_types(),
   'label'     => __( 'Pagination Types', 'rectified-magazine' ),
   'description' => __('Select the Required Pagination Type', 'rectified-magazine'),
   'section'   => 'rectified_magazine_blog_page_section',
   'settings'  => 'rectified_magazine_options[rectified-magazine-pagination-options]',
   'type'      => 'select',
   'priority'  => 10,
) );
/*Blog Page read more text*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-read-more-text]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-read-more-text'],
    'sanitize_callback' => 'sanitize_text_field'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-read-more-text]', array(
    'label'     => __( 'Enter Read More Text', 'rectified-magazine' ),
    'description' => __('Read more text for blog and archive page.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_blog_page_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-read-more-text]',
    'type'      => 'text',
    'priority'  => 10,
) );

/*Blog Page author*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-blog-author]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-blog-author'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-blog-author]', array(
    'label'     => __( 'Show Author', 'rectified-magazine' ),
    'description' => __('Checked to show the author.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_blog_page_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-blog-author]',
    'type'      => 'checkbox',
    'priority'  => 10,
) );
/*Blog Page category*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-blog-category]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-blog-category'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-blog-category]', array(
    'label'     => __( 'Show Category', 'rectified-magazine' ),
    'description' => __('Checked to show the category.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_blog_page_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-blog-category]',
    'type'      => 'checkbox',
    'priority'  => 10,
) );
/*Blog Page date*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-blog-date]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-blog-date'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-blog-date]', array(
    'label'     => __( 'Show Date', 'rectified-magazine' ),
    'description' => __('Checked to show the Date.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_blog_page_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-blog-date]',
    'type'      => 'checkbox',
    'priority'  => 10,
) );
/*Blog Page comment*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-blog-comment]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-blog-comment'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-blog-comment]', array(
    'label'     => __( 'Show Comment', 'rectified-magazine' ),
    'description' => __('Checked to show the Comment.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_blog_page_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-blog-comment]',
    'type'      => 'checkbox',
    'priority'  => 10,
) );

/*Blog Page comment*/
$wp_customize->add_setting( 'rectified_magazine_options[rectified-magazine-enable-blog-tags]', array(
    'capability'        => 'edit_theme_options',
    'transport' => 'refresh',
    'default'           => $default['rectified-magazine-enable-blog-tags'],
    'sanitize_callback' => 'rectified_magazine_sanitize_checkbox'
) );
$wp_customize->add_control( 'rectified_magazine_options[rectified-magazine-enable-blog-tags]', array(
    'label'     => __( 'Show Tags', 'rectified-magazine' ),
    'description' => __('Checked to show the Tags.', 'rectified-magazine'),
    'section'   => 'rectified_magazine_blog_page_section',
    'settings'  => 'rectified_magazine_options[rectified-magazine-enable-blog-tags]',
    'type'      => 'checkbox',
    'priority'  => 10,
) );