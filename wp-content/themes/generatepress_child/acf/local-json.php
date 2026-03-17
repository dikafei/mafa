<?php

if ( function_exists( 'acf' ) ) {
	add_filter( 'acf/settings/save_json', 'generatepress_child_acf_json_save_path' );
	add_filter( 'acf/settings/load_json', 'generatepress_child_acf_json_load_paths' );
	add_filter('acf/settings/enable_shortcode', '__return_true');
}

function generatepress_child_acf_json_save_path( $path ) {
	return get_stylesheet_directory() . '/acf/json';
}

function generatepress_child_acf_json_load_paths( $paths ) {
	$paths[] = get_stylesheet_directory() . '/acf/json';

	return $paths;
}
