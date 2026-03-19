<?php
/**
 * Frontend blocking logic.
 *
 * @package Headless_Mode
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Headless_Mode_Frontend
 *
 * Handles blocking frontend access and applying headless optimizations.
 */
class Headless_Mode_Frontend {

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings = headless_mode_get_settings();

		// Frontend blocking only when headless mode is enabled.
		if ( $this->settings['enabled'] ) {
			add_action( 'template_redirect', array( $this, 'block_frontend' ) );

			// CORS headers only on non-admin pages.
			if ( ! is_admin() ) {
				add_action( 'send_headers', array( $this, 'add_cors_headers' ) );
			}

			// Also send CORS on REST API responses.
			add_action( 'rest_api_init', array( $this, 'add_rest_cors_headers' ) );
		}

		// Restrict REST API to authenticated users (independent of headless mode).
		if ( ! empty( $this->settings['disable_rest_for_visitors'] ) ) {
			add_filter( 'rest_authentication_errors', array( $this, 'restrict_rest_api' ) );
		}

		// Security and cleanup settings work independently of headless mode toggle.
		$this->apply_security_settings();
		$this->apply_cleanup_settings();
	}

	/**
	 * Block frontend access for non-whitelisted routes.
	 *
	 * @return void
	 */
	public function block_frontend() {
		// Never block admin or REST requests.
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		// Never block AJAX requests.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// Never block WP-CLI.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		// Never block cron requests.
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		$current_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$whitelist   = array_filter( array_map( 'trim', explode( "\n", $this->settings['whitelist'] ) ) );

		foreach ( $whitelist as $route ) {
			if ( '' !== $route && 0 === strpos( $current_uri, $route ) ) {
				return;
			}
		}

		// Redirect if URL is set.
		if ( ! empty( $this->settings['redirect_url'] ) ) {
			// Using wp_redirect to allow external domains (frontend app on a different host).
			wp_redirect( esc_url_raw( $this->settings['redirect_url'] ), 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;
		}

		// Show blocked message.
		$response_code = absint( $this->settings['response_code'] );
		if ( ! in_array( $response_code, array( 200, 403, 404, 503 ), true ) ) {
			$response_code = 403;
		}

		wp_die(
			'<h1>' . esc_html__( 'GoHeadless', 'goheadless' ) . '</h1><p>' . esc_html( $this->settings['message'] ) . '</p>',
			esc_html__( 'GoHeadless', 'goheadless' ),
			array( 'response' => $response_code )
		);
	}

	/**
	 * Add CORS headers on frontend/API responses.
	 *
	 * @return void
	 */
	public function add_cors_headers() {
		if ( empty( $this->settings['enable_cors'] ) ) {
			return;
		}

		$origin = trim( $this->settings['cors_origin'] );

		if ( empty( $origin ) ) {
			return;
		}

		if ( headers_sent() ) {
			return;
		}

		header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
		header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
		header( 'Access-Control-Allow-Headers: Content-Type, Authorization' );
		header( 'Access-Control-Allow-Credentials: true' );
	}

	/**
	 * Add CORS headers to REST API responses.
	 *
	 * @return void
	 */
	public function add_rest_cors_headers() {
		if ( empty( $this->settings['enable_cors'] ) ) {
			return;
		}

		$origin = trim( $this->settings['cors_origin'] );

		if ( empty( $origin ) ) {
			return;
		}

		add_filter(
			'rest_pre_serve_request',
			function ( $served ) use ( $origin ) {
				if ( ! headers_sent() ) {
					header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
					header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
					header( 'Access-Control-Allow-Headers: Content-Type, Authorization' );
					header( 'Access-Control-Allow-Credentials: true' );
				}
				return $served;
			}
		);
	}

	/**
	 * Apply security-related settings.
	 *
	 * @return void
	 */
	private function apply_security_settings() {
		// Disable RSS feeds.
		if ( ! empty( $this->settings['disable_rss'] ) ) {
			add_action( 'do_feed', array( $this, 'disable_feed' ), 1 );
			add_action( 'do_feed_rdf', array( $this, 'disable_feed' ), 1 );
			add_action( 'do_feed_rss', array( $this, 'disable_feed' ), 1 );
			add_action( 'do_feed_rss2', array( $this, 'disable_feed' ), 1 );
			add_action( 'do_feed_atom', array( $this, 'disable_feed' ), 1 );
			remove_action( 'wp_head', 'feed_links', 2 );
			remove_action( 'wp_head', 'feed_links_extra', 3 );
		}

		// Disable XML-RPC.
		if ( ! empty( $this->settings['disable_xmlrpc'] ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
			add_filter( 'xmlrpc_methods', '__return_empty_array' );
		}

		// Disable oEmbed.
		if ( ! empty( $this->settings['disable_oembed'] ) ) {
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			remove_action( 'wp_head', 'wp_oembed_add_host_js' );
			add_filter( 'embed_oembed_discover', '__return_false' );
		}

		// Remove WP version.
		if ( ! empty( $this->settings['remove_wp_version'] ) ) {
			remove_action( 'wp_head', 'wp_generator' );
			add_filter( 'the_generator', '__return_empty_string' );
			add_filter( 'style_loader_src', array( $this, 'remove_version_query' ), 10, 1 );
			add_filter( 'script_loader_src', array( $this, 'remove_version_query' ), 10, 1 );
		}
	}

	/**
	 * Apply cleanup settings.
	 *
	 * @return void
	 */
	private function apply_cleanup_settings() {
		// Disable WP emoji scripts.
		if ( ! empty( $this->settings['disable_wp_emoji'] ) ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			add_filter( 'emoji_svg_url', '__return_false' );
		}

		// Remove shortlink.
		if ( ! empty( $this->settings['remove_shortlink'] ) ) {
			remove_action( 'wp_head', 'wp_shortlink_wp_head' );
			remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
		}

		// Remove RSD link.
		if ( ! empty( $this->settings['remove_rsd_link'] ) ) {
			remove_action( 'wp_head', 'rsd_link' );
		}

		// Remove WLW manifest link.
		if ( ! empty( $this->settings['remove_wlwmanifest'] ) ) {
			remove_action( 'wp_head', 'wlwmanifest_link' );
		}
	}

	/**
	 * Restrict REST API access to authenticated users only.
	 *
	 * @param \WP_Error|null|true $result Authentication result.
	 * @return \WP_Error|null|true Modified result.
	 */
	public function restrict_rest_api( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}

		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be authenticated to access the REST API.', 'goheadless' ),
				array( 'status' => 401 )
			);
		}

		return $result;
	}

	/**
	 * Disable RSS feed with a message.
	 *
	 * @return void
	 */
	public function disable_feed() {
		wp_die(
			esc_html__( 'RSS feeds are disabled on this site.', 'goheadless' ),
			esc_html__( 'Feed Disabled', 'goheadless' ),
			array( 'response' => 403 )
		);
	}

	/**
	 * Remove version query string from asset URLs.
	 *
	 * @param string $src Asset URL.
	 * @return string Modified URL.
	 */
	public function remove_version_query( $src ) {
		if ( strpos( $src, 'ver=' ) ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	}
}
