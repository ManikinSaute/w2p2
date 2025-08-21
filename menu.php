<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    add_menu_page(
        'w2p2²',               
        'w2p2² Home',               
        'manage_options',       
        'w2p2-home',          
        'render_w2p2_home_page',
        'dashicons-media-document'
    );

    $subs = [
        ['slug' => 'w2p2-logs',         'title' => 'Logs',                 'callback' => 'w2p2_render_logs_page'],
    ];

    foreach ( $subs as $sub ) {
        add_submenu_page(
            'w2p2-home',
            $sub['title'],      
            $sub['title'],      
            'manage_options',
            $sub['slug'],
            $sub['callback']
        );
    }
});