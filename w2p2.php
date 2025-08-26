<?php
/**
 * Plugin Name: w2p2 - Word to Post Plugin
 * Description: Adds a Gutenberg sidebar panel for uploading and converting .docx files with Mammoth.js
 * Version: 0.3.0
 * Author: ManikinSaute
 *
 * @package Mammoth_Gutenberg_Companion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once __DIR__ . '/cpt.php';
require_once __DIR__ . '/menu.php';
require_once __DIR__ . '/home.php';
require_once __DIR__ . '/logs.php';

add_action( 'enqueue_block_editor_assets', function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'post' !== $screen->base || 'w2p2_import' !== $screen->post_type ) {
		return;
	}
	$dir_url  = plugin_dir_url( __FILE__ );
	$dir_path = plugin_dir_path( __FILE__ );

	wp_enqueue_script(
		'mgc-mammoth',
		$dir_url . 'assets/js/mammoth.browser.min.js',
		array(),
		'1.6.0',
		true
	);

	wp_enqueue_script(
		'mgc-sidebar',
		$dir_url . 'sidebar.js',
		array(
			'wp-plugins',
			'wp-edit-post',
			'wp-element',
			'wp-components',
			'wp-data',
			'wp-blocks',
			'wp-block-editor',
			'wp-i18n',
			'mgc-mammoth',
		),
		'0.2.0',
		true
	);

	wp_enqueue_style(
		'mgc-sidebar',
		$dir_url . 'sidebar.css',
		array(),
		'0.2.0'
	);

	wp_localize_script(
    'mgc-sidebar',
    'W2P2_LOGGER',
    array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'w2p2_log' ),
    )
	);

	wp_localize_script(
		'mgc-sidebar',
		'MGC_SETTINGS',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'mgc_sanitize_html' ),
		)
	);

		wp_register_script(
		'mgc-label-overrides',
		false,
		array( 'wp-i18n', 'wp-hooks', 'wp-data', 'wp-dom-ready' ),
		'0.2.0',
		true
	);
} );


add_action( 'wp_ajax_mgc_sanitize_html', function () {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mgc' ) ), 403 );
	}
	check_ajax_referer( 'mgc_sanitize_html', 'nonce' );

// check this
	$html = isset( $_POST['html'] ) ? wp_unslash( $_POST['html'] ) : '';

// checks this is safe
	$safe_html = wp_kses_post( $html );
    $filename = isset( $_POST['filename'] ) ? sanitize_file_name( wp_unslash( $_POST['filename'] ) ) : '';

	    if ( $filename ) {
        w2p2_log( "cc.php - escaped content sent from file: {$filename}", 'success' );
    } else {
        w2p2_log( 'cc.php - escaped content sent (no filename)', 'success' );
    }

	wp_send_json_success(
		array(
			'html' => $safe_html,
		)
	);
} );