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

// Remove plugin options.
delete_option( 'headless_mode_settings' );
delete_option( 'headless_mode_version' );

// Remove legacy option if it still exists.
delete_option( 'hm_settings' );
