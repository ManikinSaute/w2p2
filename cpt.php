<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function w2p2_register_cpts() {
    $cpts = [
        'w2p2_import'    => [ 'singular' => 'w2p2 Import',  'plural' => 'w2p2 Imports' ]
        ];

    foreach ( $cpts as $slug => $labels ) {
        $args = [
            'labels' => [
                'name'          => $labels['plural'],
                'singular_name' => $labels['singular'],
            ],
            'public'              => false,
            'exclude_from_search' => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'publicly_queryable'  => false,
            'menu_position'       => 20,
            'supports'            => [ 'title', 'editor', 'custom-fields' ],
            'show_in_rest'        => true,
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'menu_icon'           => 'dashicons-media-document',
            'capabilities'        => [
                'publish_posts' => 'do_not_allow', // ⬅️ disables "Publish"
            ],
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'has_archive'         => true,
            'rewrite'             => [
                'slug'       => $slug,
                'with_front' => false,
            ],
        ];

        register_post_type( $slug, $args );
    }
}
add_action( 'init', 'w2p2_register_cpts' );
