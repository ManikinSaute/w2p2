<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function render_w2p2_home_page() {
	$github_url	= 'https://github.com/ManikinSaute/w2p2';
    $logs_url   = admin_url('admin.php?page=w2p2-logs');
	$imported_url   = admin_url('edit.php?post_type=w2p2_import');

	echo '<div class="wrap">';
	echo '<h1>w2p2 - Welcome to the Word to Post Plugin.</h1>';
	echo '<p>This is a proof of concept plugin and is not ready to be used in a production enviroment.</p>';
    echo '<p>The log file is currently unprotected.</p>';
    echo '<p>Images still need to be sideloaded and processed.</p>';
    echo '<p>The plugin needs testing with templates and a lot of work will be required to create custom rules for various templates.</p>';
	echo '<h2>Getting Started.</h2>';
	echo '<p>Read the below to get a feel for how this plugin works.</p>';
    echo '<ul>';
	echo '<li>Check the <a href="' . esc_url($imported_url) . '">Import Word Custom Post Type here</a></li>';
    echo '<li>You can not publish from this post type.</li>';
    echo '<li>Documents are processed in the browser and are not sent to the server.</li>';
    echo '<li>The processed content is escaped and then saved to a draft post in this non public custom post type.</li>';
	echo '<li>The content is converted to Gutenberg format.</li>';
    echo '<li>As these Word documents are potentialy sensitive, it requires manuly moveing the post out, but this could be automated.</li>';
	echo '<li>Check the <a href="' . esc_url($logs_url) . '">logs page</a> for any errors.';
    echo '<li>The GitHub repo for this work is <a href="' . esc_url($github_url) . '">here</a>.</li>';
    echo '</ul> </ br>';
    echo '<h2>Screen Shots.</h2>';
    echo '<p>The Custom Post Type.</p>';
    echo '<img src="' . esc_url(plugins_url( 'assets/img/land.png', __FILE__ )) . '" alt=The Custom Post Type" style="max-width:600px; border:1px solid #ddd; box-shadow:2px 2px 6px rgba(0,0,0,0.1);"/> <br>';
    echo '<p>The Custom Gutenberg meta box on the post.</p>';
    echo '<img src="' . esc_url(plugins_url( 'assets/img/tool.png', __FILE__ )) . '" alt="The Menu" style="max-width:600px; border:1px solid #ddd; box-shadow:2px 2px 6px rgba(0,0,0,0.1);"/> <br>';
    echo '<p>After processing a Word document.</p>';
    echo '<img src="' . esc_url(plugins_url( 'assets/img/imported.png', __FILE__ )) . '" alt="After processing" style="max-width:600px; border:1px solid #ddd; box-shadow:2px 2px 6px rgba(0,0,0,0.1);"/> <br>';
	echo '</div>';
    echo '<p>The log file can be viewed on the logs page.</p>';
    echo '<img src="' . esc_url(plugins_url( 'assets/img/logs.png', __FILE__ )) . '" alt="The Logs" style="max-width:600px; border:1px solid #ddd; box-shadow:2px 2px 6px rgba(0,0,0,0.1);"/> <br>';
}