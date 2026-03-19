<?php
/**
 * Uninstall handler for GoHeadless.
 *
 * Removes all plugin data from the database on uninstall.
 *
 * @package Headless_Mode
 */

// Exit if not called by WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove plugin options for a single site.
 *
 * @return void
 */
function headless_mode_delete_options() {
	delete_option( 'headless_mode_settings' );
	delete_option( 'headless_mode_version' );
	delete_option( 'hm_settings' ); // Legacy option.
}

// Handle multisite.
if ( is_multisite() ) {
	$sites = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $sites as $site_id ) {
		switch_to_blog( $site_id );
		headless_mode_delete_options();
		restore_current_blog();
	}
} else {
	headless_mode_delete_options();
}
