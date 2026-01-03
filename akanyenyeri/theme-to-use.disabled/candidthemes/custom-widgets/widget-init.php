<?php

if ( ! function_exists( 'rectified_magazine_load_widgets' ) ) :

    /**
     * Load widgets.
     *
     * @since 1.0.0
     */
    function rectified_magazine_load_widgets() {

        // Highlight Post.
        register_widget( 'Rectified_Magazine_Featured_Post' );

        // Author Widget.
        register_widget( 'Rectified_Magazine_Author_Widget' );
		
		// Social Widget.
        register_widget( 'Rectified_Magazine_Social_Widget' );

        // Post Slider Widget.
        register_widget( 'Rectified_Magazine_Post_Slider' );

        // Tabbed Widget.
        register_widget( 'Rectified_Magazine_Tabbed_Post' );

        // Two Category Column Widget.
        register_widget( 'Rectified_Magazine_Category_Column' );

        // Grid Layout Widget.
        register_widget( 'Rectified_Magazine_Grid_Post' );

        // Advertisement Widget.
        register_widget( 'Rectified_Magazine_Advertisement_Widget' );

        // Thumbnail Widget.
        register_widget( 'Rectified_Magazine_Thumb_Posts' );

    }

endif;
add_action( 'widgets_init', 'rectified_magazine_load_widgets' );