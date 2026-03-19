<?php
/**
 * Uninstall handler for GoHeadless.
 *
 * Fires when the plugin is deleted from WordPress (not just deactivated).
 * Removes ALL plugin data from the database.
 *
 * @package Headless_Mode
 */

// Exit if not called by WordPress uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove all plugin options for a single site.
 *
 * @return void
 */
function headless_mode_uninstall_cleanup() {
	// Main plugin options.
	delete_option( 'headless_mode_settings' );
	delete_option( 'headless_mode_version' );

	// Legacy option from v1.x.
	delete_option( 'hm_settings' );

	// Clean up any transients the plugin may have created.
	delete_transient( 'headless_mode_settings_errors' );

	// Remove the settings API registration (stored in alloptions cache).
	// WordPress handles this automatically but we clear for safety.
	wp_cache_delete( 'alloptions', 'options' );
	wp_cache_delete( 'notoptions', 'options' );
}

// Handle multisite: clean each site individually.
if ( is_multisite() ) {
	$goheadless_site_ids = get_sites(
		array(
			'fields'   => 'ids',
			'number'   => 0, // All sites.
		)
	);
	foreach ( $goheadless_site_ids as $goheadless_site_id ) {
		switch_to_blog( $goheadless_site_id );
		headless_mode_uninstall_cleanup();
		restore_current_blog();
	}
} else {
	headless_mode_uninstall_cleanup();
}
