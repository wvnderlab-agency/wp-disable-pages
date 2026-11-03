<?php

/*
 * Plugin Name:     Wvnderlab - Disable pages
 * Plugin URI:      https://github.com/wvnderlab-agency/disable-pages/
 * Description:     Deactivate the default pages, categories and tags.
 * Author:          Wvnderlab Agency
 * Author URI:      https://wvnderlab.com
 * Text Domain:     wvnderlab-disable-pages
 * Version:         0.1.0
 */

/*
 *  ################
 *  ##            ##    Copyright (c) 2026 Wvnderlab Agency
 *  ##
 *  ##   ##  ###  ##    ✉️ moin@wvnderlab.com
 *  ##    #### ####     🔗 https://wvnderlab.com
 *  #####  ##  ###
 */

declare(strict_types=1);

namespace WvnderlabAgency\DisablePages;

use WP_Admin_Bar;
use WP_Post_Type;
use WP_Query;

defined( 'ABSPATH' ) || die;

// Return early if running in WP-CLI context.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	return;
}

/**
 * Filter: Disable pages Enabled
 *
 * @param bool $enabled Whether to enable the disable pages functionality. Default true.
 * @return bool
 */
if ( ! apply_filters( 'wvnderlab/disable-pages/enabled', true ) ) {
	return;
}

/**
 * Disable or redirects any page.
 *
 * @link   https://developer.wordpress.org/reference/hooks/template_redirect/
 * @hooked action template_redirect
 *
 * @return void
 */
function disable_or_redirect_page(): void {
	// return early if not an individual page.
	if ( ! is_singular( 'page' ) ) {
		return;
	}

	// return early if in admin, ajax, cron, rest api or wp-cli context.
	if (
		is_admin()
		|| wp_doing_ajax()
		|| wp_doing_cron()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
	) {
		return;
	}

	/**
	 * Filter: Disable pages Status Code
	 *
	 * Supported:
	 * - 301 / 302 / 307 / 308  → redirect
	 * - 404 / 410              → no redirect, proper error response
	 *
	 * @param int $status_code The HTTP status code for the redirect. Default is 404 (Not Found).
	 * @return int
	 */
	$status_code = (int) apply_filters(
		'wvnderlab/disable-pages/status-code',
		404
	);

	// Handle 404 and 410 status codes separately.
	if ( in_array( $status_code, array( 404, 410 ), true ) ) {
		global $wp_query;

		$wp_query->set_404();
		status_header( $status_code );
		nocache_headers();

		$template = get_query_template( '404' );

		if ( $template ) {
			include $template;
		} else {
			wp_die(
				esc_html__( '404 Not Found', 'wvnderlab-disable-pages' ),
				esc_html__( 'Not Found', 'wvnderlab-disable-pages' ),
				array( 'response' => esc_html( $status_code ) )
			);
		}

		exit;
	}

	// Ensure the status code is a valid redirect code.
	if ( $status_code < 300 || $status_code > 399 ) {
		$status_code = 301;
	}

	/**
	 * Filter: Disable pages Redirect URL
	 *
	 * Allows modification of the redirect URL for disabled pages.
	 *
	 * @param string $redirect_url The URL to redirect to. Default is the homepage.
	 * @return string
	 */
	$redirect_url = (string) apply_filters(
		'wvnderlab/disable-pages/redirect-url',
		home_url()
	);

	// Ensure the redirect URL is not empty.
	if ( empty( $redirect_url ) ) {
		$redirect_url = home_url();
	}

	wp_safe_redirect( $redirect_url, $status_code );

	exit;
}

add_action( 'template_redirect', __NAMESPACE__ . '\\disable_or_redirect_page', PHP_INT_MIN );

/**
 * Exclude pages from Frontend Queries
 *
 * @link   https://developer.wordpress.org/reference/hooks/pre_get_posts/
 * @hooked pre_get_posts
 *
 * @param WP_Query $query The WP_Query instance (passed by reference).
 * @return void
 */
function exclude_pages_from_queries( WP_Query $query ): void {
	// return early if in admin or not main query.
	if ( is_admin() || ! $query->is_main_query() ) {

		return;
	}

	// only modify relevant frontend queries.
	if ( ! $query->is_search() && ! $query->is_archive() && ! $query->is_home() && ! $query->is_feed() ) {

		return;
	}

	$post_types = $query->get( 'post_type' );

	// wordPress searches may use "any" as their default post type.
	if ( empty( $post_types ) && $query->is_search() ) {
		$post_types = 'any';
	}

	// expand "any" into publicly queryable post types.
	if ( 'any' === $post_types || ( is_array( $post_types ) && in_array( 'any', $post_types, true ) ) ) {
		$post_types = get_post_types(
			array(
				'exclude_from_search' => false,
			),
			'names'
		);
	}

	// normalize post types to an array.
	if ( is_string( $post_types ) ) {
		$post_types = array( $post_types );
	}

	// return early if post types is not an array or is empty.
	if ( ! is_array( $post_types ) || empty( $post_types ) ) {

		return;
	}

	// remove the page post type.
	$post_types = array_values(
		array_diff(
			$post_types,
			array( 'page', 'any' )
		)
	);

	// ensure explicitly page-only queries return no results.
	if ( empty( $post_types ) ) {
		$query->set( 'post__in', array( 0 ) );

		return;
	}

	$query->set( 'post_type', $post_types );
}

add_action( 'pre_get_posts', __NAMESPACE__ . '\\exclude_pages_from_queries', PHP_INT_MAX );

/**
 * Redirect page Edit Templates
 *
 * @link   https://developer.wordpress.org/reference/hooks/admin_init/
 * @hooked action admin_init
 *
 * @return void
 * @global string $pagenow
 */
function redirect_admin_pages_templates(): void {
	global $pagenow;
	global $typenow;

	// return early if doing ajax.
	if ( wp_doing_ajax() ) {

		return;
	}

	$is_page_edit = ( 'post.php' === $pagenow && 'page' === get_post_type() );
	$is_page_list = ( 'edit.php' === $pagenow && in_array( $typenow, array( '', 'page' ), true ) );
	$is_page_new  = ( 'post-new.php' === $pagenow && in_array( $typenow, array( '', 'page' ), true ) );

	if (
		$is_page_edit
		|| $is_page_list
		|| $is_page_new
	) {
		wp_safe_redirect( admin_url(), 301 );
		exit;
	}
}

add_action( 'admin_init', __NAMESPACE__ . '\\redirect_admin_pages_templates', PHP_INT_MIN );

/**
 * Remove pages Admin Bar Node
 *
 * @link   https://developer.wordpress.org/reference/hooks/admin_bar_menu/
 * @hooked action admin_bar_menu
 *
 * @param WP_Admin_Bar $admin_bar The WP_Admin_Bar instance.
 * @return  void
 */
function remove_pages_admin_bar_node( WP_Admin_Bar $admin_bar ): void {
	if ( is_admin_bar_showing() ) {
		$admin_bar->remove_node( 'new-page' );
	}
}

add_action( 'admin_bar_menu', __NAMESPACE__ . '\\remove_pages_admin_bar_node', PHP_INT_MAX );

/**
 * Remove pages Menu Pages
 *
 * @link   https://developer.wordpress.org/reference/hooks/admin_menu/
 * @hooked action admin_menu
 *
 * @return void
 */
function remove_pages_admin_menu_pages(): void {
	remove_menu_page( 'edit.php?post_type=page' );
}

add_action( 'admin_menu', __NAMESPACE__ . '\\remove_pages_admin_menu_pages', PHP_INT_MAX );

/**
 * Remove REST pages Endpoints
 *
 * @link   https://developer.wordpress.org/reference/hooks/rest_endpoints/
 * @hooked filter rest_endpoints
 *
 * @param array<string,mixed> $endpoints The REST API endpoints.
 * @return array<string,mixed>
 */
function remove_pages_endpoints( array $endpoints ): array {
	foreach ( array_keys( $endpoints ) as $route ) {
		if ( preg_match( '#^/wp/v2/pages(?:/|$)#', $route ) ) {
			unset( $endpoints[ $route ] );
		}
	}

	return $endpoints;
}

add_filter( 'rest_endpoints', __NAMESPACE__ . '\\remove_pages_endpoints' );

/**
 * Remove pages from XML sitemap.
 *
 * @hooked filter wp_sitemaps_post_types
 * @param  array<string, WP_Post_Type> $post_types Registered post types.
 * @return array<string, WP_Post_Type>
 */
function remove_pages_from_sitemap( array $post_types ): array {
	unset( $post_types['page'] );

	return $post_types;
}

add_filter( 'wp_sitemaps_post_types', __NAMESPACE__ . '\\remove_pages_from_sitemap' );