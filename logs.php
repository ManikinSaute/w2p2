<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'w2p2_MAX_LINES', 1000 );
define( 'w2p2_KEEP_LINES', 5000 );


/**
 * Append a message to our plugin log.
 *
 * @param string $message
 * @param string $level   One of 'INFO', 'WARNING', 'ERROR'
 */
function w2p2_log( $message, $level = 'INFO' ) {
    $upload  = wp_upload_dir();
    $log_dir = trailingslashit( $upload['basedir'] ) . 'companions-companion/logs';
    if ( ! file_exists( $log_dir ) ) {
        wp_mkdir_p( $log_dir );
    }
    $file  = $log_dir . '/plugin.log';
// TO DO Move or protect this file from public access 
    $ts    = date_i18n( 'Y-m-d H:i:s' );
// TO DO set to UTC 
    $entry = sprintf( "[%s] [%s] %s\n", $ts, strtoupper( $level ), $message );
// TO DO check its ok to ignore this error 
// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    error_log( $entry, 3, $file );
    w2p2_truncate_log();
}

function w2p2_truncate_log( $max_size = 5242880, $keep_lines = 5000 ) {
    $upload   = wp_upload_dir();
    $log_file = trailingslashit( $upload['basedir'] ) . 'companions-companion/logs/plugin.log';
    if ( ! file_exists( $log_file ) ) {
        return;
    }
    if ( filesize( $log_file ) > $max_size ) {
        $lines = file( $log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        if ( $lines === false ) {
            return;
        }
        $tail = array_slice( $lines, -1 * $keep_lines );
        file_put_contents( $log_file, implode( "\n", $tail ) . "\n" );
        w2p2_log( sprintf( 'Log truncated to last %d lines after exceeding %d bytes.', $keep_lines, $max_size ), 'WARNING' );
    }
}

// TO DO check its ok to ignore this error
// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
set_error_handler( function( $errno, $errstr, $errfile, $errline ) {
    $lvl = in_array( $errno, [E_WARNING, E_USER_WARNING], true ) ? 'WARNING' : 'ERROR';
    w2p2_log( "{$errstr} in {$errfile} on line {$errline}", $lvl );
    return false; 
} );

function w2p2_render_logs_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $upload   = wp_upload_dir();
    $log_file = trailingslashit( $upload['basedir'] ) . 'companions-companion/logs/plugin.log';

    if ( isset( $_POST['w2p2_clear_logs'] ) && check_admin_referer( 'w2p2_clear_logs', 'settings_nonce' ) ) {
        file_put_contents( $log_file, '' );
        w2p2_log( 'logs.php - Logs cleared', 'SUCCESS' );
        echo '<div class="notice notice-success"><p>Log file cleared.</p></div>';
    }

    echo '<div class="wrap"><h1>w2p2 Logs</h1>';
        echo '<h2>About The Logs</h2>';
        echo '<p>Log file location: <code>' . esc_html( $log_file ) . '</code></p>';
        echo '<p>Log file size: ' . esc_html( size_format( filesize( $log_file ) ) ) . '</p>';
        echo '<p>Notice this file is not protected from public access. Please consider protecting this with a server access rule.</p>';
        echo '<p>This log file is truncated at a given size by variables set in the log.php file.</p>';

        echo '<h2>Clear Logs</h2>';
        echo '<p>Click the button below to clear the log file:</p>';

// TO DO consider locking file
    if ( ! file_exists( $log_file ) ) {
        echo '<p>No log file found. Nothing has been logged yet.</p>';
    } else {
        $lines = file( $log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        $tail  = array_slice( $lines, -1000 );

        echo '<form method="post" style="margin-bottom:1em;">';
        wp_nonce_field( 'w2p2_clear_logs', 'settings_nonce' );
        echo '<input type="submit" name="w2p2_clear_logs" class="button button-secondary" value="Clear log">';
        echo '</form>';

        echo '<pre style="max-height:600px; overflow:auto; background:#fff; border:1px solid #ddd; padding:1em;"><code>';
        foreach ( $tail as $line ) {
            if ( strpos( $line, '[ERROR]' ) !== false ) {
// TO DO swap inline style to a CSS class
                echo '<span style="color:#c00;">' . esc_html( $line ) . '</span>' . "\n";
            } elseif ( strpos( $line, '[WARNING]' ) !== false ) {
                echo '<span style="color:#e67e22;">' . esc_html( $line ) . '</span>' . "\n";
            } elseif ( strpos( $line, '[INFO]' ) !== false ) {
                echo '<span style="color:gray">' . esc_html( $line ) . '</span>' . "\n";
            } elseif ( strpos( $line, '[SUCCESS]' ) !== false ) {
                echo '<span style="color:green;">' . esc_html( $line ) . '</span>' . "\n";
            } else {
                echo esc_html( $line ) . "\n";
            }
        }
        echo '</code></pre>';
    }
    echo '</div>';
}



add_action( 'wp_ajax_w2p2_log_message', function () {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => 'Permission denied' ), 403 );
    }

    check_ajax_referer( 'w2p2_log', 'nonce' );

    $msg   = isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : '';
    $level = isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : 'INFO';

    if ( $msg ) {
        w2p2_log( $msg, $level );
        wp_send_json_success( array( 'logged' => true ) );
    } else {
        wp_send_json_error( array( 'message' => 'Empty log message' ), 400 );
    }
});

/* 
add_action( 'admin_init', function() {
    wp_nonce_field( 'w2p2_settings', 'w2p2_settings_nonce' );
    if ( 1 !== (int) get_option( 'w2p2_debug_mode', 0 ) ) {
        return;
    }
    $page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
    if ( ! in_array( $page, [ 'w2p2-settings', 'w2p2-logs' ], true ) ) {
        return;
    }
    if ( isset( $_GET['action'] ) && 'check_revisions' === $_GET['action'] ) {
        if ( ! isset( $_GET['w2p2_nonce'] ) || 
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['w2p2_nonce'] ) ), 'w2p2_check_revisions' ) ) {
            wp_die( esc_html__( 'Security check failed', 'w2p2' ) );
        }
        add_action( 'admin_notices', 'w2p2_check_revisions_notice' );
    }
    add_action( 'admin_notices', 'sp_loaded_admin_notice' );
    add_action( 'admin_notices', 'fft_test_fetch_feed' );
    add_action( 'admin_notices', 'check_api_key' );
} );

function w2p2_check_revisions_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $rev = WP_POST_REVISIONS;
    if ( $rev === false ) {
        $display = 'disabled';
    } elseif ( is_int( $rev ) ) {
        $display = $rev;
    } else {
        $display = 'default';
    }
    echo '<div class="notice notice-success is-dismissible">Post revisions are currently <em>' . esc_html( $display ) . '</div>';
}

function sp_loaded_admin_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
	if ( ! class_exists( 'SimplePie\SimplePie', false ) ) {
        require_once ABSPATH . WPINC . '/class-simplepie.php';
    }
    if ( class_exists( 'SimplePie' ) ) {
        echo '<div class="notice notice-success is-dismissible">SimplePie is loaded.</strong> You can safely use fetch_feed() and other SimplePie APIs.</div>';
        w2p2_log( 'logs.php - SimplePie is loaded.', 'SUCCESS' );
    } else {
        echo '<div class="notice notice-error is-dismissible">SimplePie is <em>not</em> loaded. RSS parsing functions may not work as expected.</div>';
        w2p2_log( 'logs.php - SimplePie is not loaded.', 'ERROR' );

    }
}

function fft_test_fetch_feed() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
	if ( ! class_exists( 'SimplePie\SimplePie', false ) ) {
        require_once ABSPATH . WPINC . '/class-simplepie.php';
    }
    $test_url = 'https://feeds.bbci.co.uk/news/rss.xml';
    $feed = fetch_feed( $test_url );
    if ( is_wp_error( $feed ) ) {
        echo '<div class="notice notice-error is-dismissible"> Fetch Feed Tester: Error fetching feed: ' . esc_html( $feed->get_error_message() ) . '</div>';
        w2p2_log( 'logs.php - Error fetching Feed', 'ERROR' );
        return;
    }

    $max_items = $feed->get_item_quantity( 1 );
    if ( $max_items > 0 ) {
        echo '<div class="notice notice-success is-dismissible">Fetch Feed Tester: Success!' . esc_html( $max_items ) . ' item(s) retrieved from the feed.</div>';
        w2p2_log( 'logs.php - Feed is loaded.', 'SUCCESS' );
    } else {
        echo '<div class="notice notice-warning is-dismissible">Fetch Feed Tester: No items found in the feed.</div>';
        w2p2_log( 'logs.php - No items found', 'WARNING' );
    }
}

function check_api_key() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $api_key = get_option( 'w2p2_api_key', '' );
	if ( ! $api_key ) {
        echo '<div class="notice notice-error is-dismissible">API is empty</div>';
        w2p2_log( 'logs.php - API key is empty', 'ERROR' );
    } else {
        echo '<div class="notice notice-success is-dismissible">API key has been added</div>';
        w2p2_log( 'logs.php - API key is set', 'SUCCESS' );
    }
    $response = wp_remote_get( 'https://api.mistral.ai/v1/models', 
          [
            'timeout'    => 5,
            'headers'    => [
                'Authorization' => "Bearer {$api_key}",
            ],
            ] );
                if ( is_wp_error( $response ) ) {
                echo '<div class="notice notice-error is-dismissible">API request failed: ' . esc_html( $response->get_error_message() ) . '</div>';
                w2p2_log( 'logs.php - API request failed: ' . $response->get_error_message(), 'ERROR' );
                } elseif ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
                echo '<div class="notice notice-error is-dismissible">API key rejected</div>';
                w2p2_log( 'logs.php - API key rejected', 'ERROR' );
                } else {
                echo '<div class="notice notice-success is-dismissible">API key accepted</div>';
                w2p2_log( 'logs.php - API key accepted', 'SUCCESS' );
                }
}


*/ 


