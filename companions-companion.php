<?php
/**
 * Plugin Name: Mammoth Gutenberg Companion
 * Description: Adds a Gutenberg sidebar panel for uploading and converting .docx files with Mammoth.js, sanitised via wp_kses_post().
 * Version: 0.2.0
 * Author: You
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

/**
 * Enqueue assets in the post editor only.
 */
add_action( 'enqueue_block_editor_assets', function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'post' !== $screen->base || 'w2p2_import' !== $screen->post_type ) {
		return;
	}
	$dir_url  = plugin_dir_url( __FILE__ );
	$dir_path = plugin_dir_path( __FILE__ );

	// Mammoth library (bundle your own copy).
	wp_enqueue_script(
		'mgc-mammoth',
		$dir_url . 'assets/js/mammoth.browser.min.js',
		array(),
		'1.6.0',
		true
	);

	// Sidebar UI.
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

	// Localize secure AJAX details.
	wp_localize_script(
		'mgc-sidebar',
		'MGC_SETTINGS',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'mgc_sanitize_html' ),
		)
	);
} );

/**
 * AJAX: sanitize HTML through wp_kses_post().
 * Only for users who can edit posts.
 */
add_action( 'wp_ajax_mgc_sanitize_html', function () {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mgc' ) ), 403 );
	}

	check_ajax_referer( 'mgc_sanitize_html', 'nonce' );

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_unslash is correct for superglobal, sanitisation happens via wp_kses_post().
	$html = isset( $_POST['html'] ) ? wp_unslash( $_POST['html'] ) : '';

	// Sanitize to allowed post HTML.
	$safe_html = wp_kses_post( $html );

	w2p2_log( 'main - escaped content sent', 'SUCCESS' );

	// Return JSON.
	wp_send_json_success(
		array(
			// Escape for JSON transport only; consumer inserts as HTML (already sanitised).
			'html' => $safe_html,
		)
	);
} );




// the button does not submit for review so we need to change it
add_action( 'enqueue_block_editor_assets', function() {
	global $post;
	if ( ! $post || $post->post_type !== 'w2p2_import' ) {
		return;
	}
	wp_add_inline_script(
		'wp-edit-post',
		"wp.domReady( function() {
			// MutationObserver to watch for buttons being re-rendered
			const observer = new MutationObserver(() => {
				document.querySelectorAll('.editor-post-publish-button, .editor-post-save-draft').forEach(btn => {
					if (btn.innerText.match(/Submit for Review/i) || btn.innerText.match(/Publish/i) || btn.innerText.match(/Save Draft/i)) {
						btn.innerText = 'Save';
					}
				});
			});
			observer.observe(document.body, { childList: true, subtree: true });
		});"
	);
} );