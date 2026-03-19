<?php
/**
 * Plugin Name:       GoHeadless — WordPress & WooCommerce Headless CMS
 * Plugin URI:        https://github.com/zeerakzubair/go-headless
 * Description:       Convert your WordPress or WooCommerce site into a headless CMS. Block frontend access while keeping REST API, Store API, and wp-admin fully functional.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Zeerak Zubair
 * Author URI:        https://zetheriallabs.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       headless-mode
 * Domain Path:       /languages
 *
 * @package Headless_Mode
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @var string
 */
define( 'HEADLESS_MODE_VERSION', '1.0.0' );

/**
 * Plugin directory path.
 *
 * @var string
 */
define( 'HEADLESS_MODE_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 *
 * @var string
 */
define( 'HEADLESS_MODE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 *
 * @var string
 */
define( 'HEADLESS_MODE_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Minimum PHP version required.
 *
 * @var string
 */
define( 'HEADLESS_MODE_MIN_PHP', '7.4' );

/**
 * Check PHP version and deactivate if too old.
 *
 * @return bool True if PHP version is sufficient.
 */
function headless_mode_check_php_version() {
	if ( version_compare( PHP_VERSION, HEADLESS_MODE_MIN_PHP, '<' ) ) {
		add_action( 'admin_notices', 'headless_mode_php_version_notice' );
		add_action(
			'admin_init',
			function () {
				deactivate_plugins( HEADLESS_MODE_BASENAME );
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['activate'] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					unset( $_GET['activate'] );
				}
			}
		);
		return false;
	}
	return true;
}

/**
 * Admin notice for PHP version requirement.
 *
 * @return void
 */
function headless_mode_php_version_notice() {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		sprintf(
			/* translators: 1: Required PHP version, 2: Current PHP version. */
			esc_html__( 'GoHeadless requires PHP %1$s or higher. You are running PHP %2$s. The plugin has been deactivated.', 'headless-mode' ),
			esc_html( HEADLESS_MODE_MIN_PHP ),
			esc_html( PHP_VERSION )
		)
	);
}

// Check PHP version before loading.
if ( ! headless_mode_check_php_version() ) {
	return;
}

// Load plugin classes.
require_once HEADLESS_MODE_DIR . 'includes/class-headless-mode.php';
require_once HEADLESS_MODE_DIR . 'includes/class-headless-mode-admin.php';
require_once HEADLESS_MODE_DIR . 'includes/class-headless-mode-frontend.php';

/**
 * Returns the default plugin settings.
 *
 * @return array Default settings.
 */
function headless_mode_get_defaults() {
	return array(
		'enabled'              => 1,
		'message'              => __( 'This site is running in headless mode. The frontend is powered by a separate application.', 'headless-mode' ),
		'redirect_url'         => '',
		'response_code'        => 403,
		'whitelist'            => "/wp-json\n/wp-admin\n/wc-auth",
		'enable_cors'          => 0,
		'cors_origin'          => '',
		'disable_rss'          => 1,
		'disable_xmlrpc'       => 1,
		'disable_oembed'       => 0,
		'remove_wp_version'    => 1,
		'disable_wp_emoji'     => 0,
		'remove_shortlink'     => 0,
		'remove_rsd_link'      => 1,
		'remove_wlwmanifest'   => 1,
		'disable_rest_for_visitors' => 0,
	);
}

/**
 * Retrieve plugin settings with defaults.
 *
 * @return array Plugin settings.
 */
function headless_mode_get_settings() {
	$defaults = headless_mode_get_defaults();
	$settings = get_option( 'headless_mode_settings', array() );

	return wp_parse_args( $settings, $defaults );
}

/**
 * Plugin activation hook.
 *
 * @return void
 */
function headless_mode_activate() {
	// Migrate from old option name if it exists.
	$old_settings = get_option( 'hm_settings' );
	if ( false !== $old_settings && is_array( $old_settings ) ) {
		$defaults   = headless_mode_get_defaults();
		$migrated   = wp_parse_args( $old_settings, $defaults );
		update_option( 'headless_mode_settings', $migrated, true );
		delete_option( 'hm_settings' );
	}

	// Set defaults if no settings exist.
	if ( false === get_option( 'headless_mode_settings' ) ) {
		update_option( 'headless_mode_settings', headless_mode_get_defaults(), true );
	}

	// Store version for future upgrades.
	update_option( 'headless_mode_version', HEADLESS_MODE_VERSION, true );
}
register_activation_hook( __FILE__, 'headless_mode_activate' );

/**
 * Plugin deactivation hook.
 *
 * Cleans up transients and scheduled events.
 *
 * @return void
 */
function headless_mode_deactivate() {
	// Nothing to clean on deactivation for now.
	// Options are preserved so settings survive deactivate/reactivate.
	// Options are only removed on uninstall (delete).
}
register_deactivation_hook( __FILE__, 'headless_mode_deactivate' );

/**
 * Run version upgrade routines.
 *
 * @return void
 */
function headless_mode_maybe_upgrade() {
	$stored_version = get_option( 'headless_mode_version', '0' );

	if ( version_compare( $stored_version, HEADLESS_MODE_VERSION, '<' ) ) {
		// Migrate from old prefix if upgrading from v1.x.
		$old_settings = get_option( 'hm_settings' );
		if ( false !== $old_settings && is_array( $old_settings ) ) {
			$defaults = headless_mode_get_defaults();
			$migrated = wp_parse_args( $old_settings, $defaults );
			update_option( 'headless_mode_settings', $migrated, true );
			delete_option( 'hm_settings' );
		}

		update_option( 'headless_mode_version', HEADLESS_MODE_VERSION, true );
	}
}
add_action( 'admin_init', 'headless_mode_maybe_upgrade' );

/**
 * Initialize the plugin after all plugins are loaded.
 *
 * @return void
 */
function headless_mode_init() {
	Headless_Mode::get_instance();
}
add_action( 'plugins_loaded', 'headless_mode_init' );
