<?php

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

\WP_CLI::add_command( 'mafa acf-export-json', function() {
	if ( ! function_exists( 'acf_get_field_groups' ) ) {
		\WP_CLI::error( 'ACF is not active.' );
	}

	$json_dir = get_stylesheet_directory() . '/acf/json';

	if ( ! is_dir( $json_dir ) && ! wp_mkdir_p( $json_dir ) ) {
		\WP_CLI::error( 'Unable to create JSON directory: ' . $json_dir );
	}

	$field_groups = acf_get_field_groups();
	foreach ( $field_groups as $field_group ) {
		acf_update_field_group( $field_group );
		\WP_CLI::log( 'Exported field group: ' . $field_group['title'] );
	}

	if ( function_exists( 'acf_get_internal_post_type_posts' ) ) {
		$post_types = acf_get_internal_post_type_posts( array(), 'acf-post-type' );
		foreach ( $post_types as $post_type ) {
			acf_update_post_type( $post_type );
			\WP_CLI::log( 'Exported post type: ' . $post_type['title'] );
		}

		$taxonomies = acf_get_internal_post_type_posts( array(), 'acf-taxonomy' );
		foreach ( $taxonomies as $taxonomy ) {
			acf_update_taxonomy( $taxonomy );
			\WP_CLI::log( 'Exported taxonomy: ' . $taxonomy['title'] );
		}
	}

	\WP_CLI::success( 'ACF local JSON export complete.' );
} );
