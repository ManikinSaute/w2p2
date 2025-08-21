<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function render_w2p2_home_page() {
	$github_url	= 'https://github.com/ManikinSaute/';

	echo '<div class="wrap">';
	echo '<h1> - Welcome.</h1>';

	echo '<h2>Getting Started.</h2>';
	echo '<p>Read the below to get a feel for how this plugin works.</p>';
	echo '<p><a href="' . esc_url($github_url) . '">here</a>.</li>';
	echo '</div>';
}