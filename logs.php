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
 * @param string $level   One of 'warning', 'warning', 'error'
 */
function w2p2_log( $message, $level = 'info' ) {
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
        w2p2_log( sprintf( 'logs.php - Log truncated to last %d lines after exceeding %d bytes.', $keep_lines, $max_size ), 'warning' );
    }
}

// TO DO check its ok to ignore this error
// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
set_error_handler( function( $errno, $errstr, $errfile, $errline ) {
    $lvl = in_array( $errno, [E_WARNING, E_USER_WARNING], true ) ? 'warning' : 'error';
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
        w2p2_log( 'logs.php - Logs cleared', 'success' );
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
            if ( strpos( $line, '[error]' ) !== false ) {
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
    $level = isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : 'info';

    if ( $msg ) {
        w2p2_log( $msg, $level );
        wp_send_json_success( array( 'logs.php - logged' => true ) );
    } else {
        wp_send_json_error( array( 'message' => 'logs.php - Empty log message' ), 400 );
    }
});


