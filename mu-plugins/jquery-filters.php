<?php
/* Plugin Name: jQuery Filters
 * Description: Default filters, option values, and other tweaks.
 */

if ( defined( 'WP_INSTALLING' ) )
	return;

$options = jquery_default_site_options();
$sites = jquery_sites();
$options = array_merge( $options, $sites[ JQUERY_LIVE_SITE ]['options'] );
foreach ( $options as $option => $value ) {
	if ( 'stylesheet' === $option || 'template' === $option )
		continue; // Don't mess with themes for now.
	add_filter( 'pre_option_' . $option, function( $null ) use ( $value, $blog_id ) {
		if ( $blog_id == get_current_blog_id() )
			return $value;
		return $null;
	} );
}
unset( $sites, $options, $option );

// Disable WordPress auto-paragraphing for posts.
remove_filter( 'the_content', 'wpautop' );

// Disable WordPress text transformations (smart quotes, etc.) for posts.
remove_filter( 'the_content', 'wptexturize' );

// Disable more restrictive multisite upload settings.
remove_filter( 'upload_mimes', 'check_upload_mimes' );
// Give unfiltered upload ability to super admins.
define( 'ALLOW_UNFILTERED_UPLOADS', true );
// Until unfiltered uploads make it into XML-RPC:
add_filter( 'upload_mimes', function( $mimes ) {
	$mimes['eot'] = 'application/vnd.ms-fontobject';
	$mimes['svg'] = 'image/svg+xml';
	$mimes['ttf'] = 'application/x-font-ttf';
	$mimes['woff'] = 'application/font-woff';
	$mimes['xml'] = 'text/xml';
	$mimes['php'] = 'application/x-php';
	$mimes['json'] = 'application/json';
	return $mimes;
} );

// Increase file size limit to 1GB
add_filter( 'pre_site_option_fileupload_maxk', function() {
	return 1024 * 1024;
} );

// Allow full HTML in term descriptions.
add_action( 'init', 'jquery_unfiltered_html_for_term_descriptions' );
add_action( 'set_current_user', 'jquery_unfiltered_html_for_term_descriptions' );
function jquery_unfiltered_html_for_term_descriptions() {
	remove_filter( 'pre_term_description', 'wp_filter_kses' );
	remove_filter( 'pre_term_description', 'wp_filter_post_kses' );
	if ( ! current_user_can( 'unfiltered_html' ) )
		add_filter( 'pre_term_description', 'wp_filter_post_kses' );
}

// Bypass multisite checks.
add_filter( 'ms_site_check', '__return_true' );

// Add body classes found in postmeta.
add_filter( 'body_class', function( $classes ) {
	if ( ! is_singular() )
		return $classes;
	if ( is_page() )
		$classes[] = 'page-slug-' . sanitize_html_class( strtolower( get_queried_object()->post_name ) );
	if ( $post_classes = get_post_meta( get_queried_object_id(), 'body_class', true ) )
		$classes = array_merge( $classes, explode( ' ', $post_classes ) );
	return $classes;
});
