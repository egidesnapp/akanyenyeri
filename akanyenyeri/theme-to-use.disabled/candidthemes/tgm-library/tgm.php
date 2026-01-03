<?php
/**
 * Recommended plugins
 *
 * @package Prefer 1.0.0
 */

if ( ! function_exists( 'rectified_magazine_recommended_plugins' ) ) :

    /**
     * Recommend plugin list.
     *
     * @since 1.0.0
     */
    function rectified_magazine_recommended_plugins() {

        $plugins = array(
            array(
                'name'     => esc_html__( 'One Click Demo Import', 'rectified-magazine' ),
                'slug'     => 'one-click-demo-import',
                'required' => false,
            ),
            array(
                'name'     => __( 'Candid Advanced Toolset', 'rectified-magazine' ),
                'slug'     => 'candid-advanced-toolset',
                'required' => false,
            ),
            array(
                'name'     => __( 'Location Weather', 'rectified-magazine' ),
                'slug'     => 'location-weather',
                'required' => false,
            ),
        );

        tgmpa( $plugins );

    }

endif;

add_action( 'tgmpa_register', 'rectified_magazine_recommended_plugins' );
