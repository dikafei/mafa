<?php
/**
 * GeneratePress child theme functions and definitions.
 *
 * Add your custom PHP in this file.
 * Only edit this file if you have direct access to it on your server (to fix errors if they happen).
 */

require_once get_stylesheet_directory() . '/acf/local-json.php';
require_once get_stylesheet_directory() . '/shortcodes/taxonomy-terms.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once get_stylesheet_directory() . '/acf/export-local-json-cli.php';
}

add_action( 'wp_enqueue_scripts', function() {

	wp_enqueue_style(
		'parent-style',
		get_template_directory_uri() . '/style.css'
	);

	wp_enqueue_style(
		'child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'parent-style' ),
		filemtime( get_stylesheet_directory() . '/style.css' )
	);

});

// Page Title Color - Via Metabox	
add_action( 'add_meta_boxes', 'add_page_metabox' );
add_action( 'save_post', 'page_metabox_save' );	
add_action( 'admin_enqueue_scripts', 'page_metabox_assets' );

function add_page_metabox() 
{		
	add_meta_box(
		'page_metabox', 
		'Page Options',
		'page_metabox_html', 
		'page',
		'side',
		'low'
	);		
}	

function page_metabox_html( $post ) 
{
	$header_color = get_post_meta( $post->ID, '_header_color_meta_key', true );

	wp_nonce_field( 'page_metabox_nonce_action', 'page_metabox_nonce' );
	?>
		<div class="metabox-row">
			<label for="header_color_field">Page Title color</label>
			<input
				   type="text"
				   id="header_color_field"
				   name="header_color_field"
				   value="<?php echo esc_attr( $header_color ); ?>"
				   class="page-color-field"
				   data-default-color="#ffffff"
			/>
		</div>
	<?php
}		

function page_metabox_save( $post_id ) 
{
	// Verify nonce
	if (
		! isset( $_POST['page_metabox_nonce'] ) ||
		! wp_verify_nonce( $_POST['page_metabox_nonce'], 'page_metabox_nonce_action' )
	) {
		return;
	}

	// Autosave check
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['header_color_field'] ) ) {
		update_post_meta(
			$post_id,
			'_header_color_meta_key',
			sanitize_hex_color( $_POST['header_color_field'] )
		);
	}
}

function page_metabox_assets( $hook ) {			
	if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) {
		return;
	}

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
}

add_action( 'admin_footer', 'page_metabox_inline_js' );
function page_metabox_inline_js() {
	$screen = get_current_screen();

	if ( $screen->post_type !== 'page' ) {
		return;
	}
	?>
	<script>
		jQuery(document).ready(function ($) {
			$('.page-color-field').wpColorPicker();
		});
	</script>
	<?php
}

add_action( 'wp_head', 'gp_inject_page_title_color', 99 );
function gp_inject_page_title_color() {

	if ( ! is_singular() ) {
		return;
	}

	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return;
	}

	$color = get_post_meta( $post_id, '_header_color_meta_key', true );
	if ( ! $color ) {
		$color = '#ffffff';
	}

	?>
		<style>
			.page-hero .page-title, .page-hero h1, .page-hero h2, .page-hero h3, .page-hero h4, .page-hero h5, .page-hero h6 {
				color: <?php echo esc_html( $color ); ?>;
			}
		</style>
	<?php
}

//Gravity Forms - change default 30 day expiration to 60 days for Save and Continue
add_filter( 'gform_incomplete_submissions_expiration_days', 'change_incomplete_submissions_expiration_days' );
function change_incomplete_submissions_expiration_days( $expiration_days ) {
 GFCommon::log_debug( 'gform_incomplete_submissions_expiration_days: running.' );
 $expiration_days = 60;
 return $expiration_days;
}

//Alphabetize Sessions, and Vendors
add_action('pre_get_posts', function($query){

    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( is_post_type_archive(['vendors','sessions']) ) {
        $query->set('orderby', 'title');
        $query->set('order', 'ASC');
    }

});

//disable comments
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);
