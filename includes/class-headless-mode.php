<?php
/**
 * Main plugin class.
 *
 * @package Headless_Mode
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Headless_Mode
 *
 * Main plugin orchestrator.
 */
class Headless_Mode {

	/**
	 * Singleton instance.
	 *
	 * @var Headless_Mode|null
	 */
	private static $instance = null;

	/**
	 * Admin class instance.
	 *
	 * @var Headless_Mode_Admin
	 */
	public $admin;

	/**
	 * Frontend class instance.
	 *
	 * @var Headless_Mode_Frontend
	 */
	public $frontend;

	/**
	 * Get singleton instance.
	 *
	 * @return Headless_Mode
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_textdomain();
		$this->init_components();
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @return void
	 */
	private function load_textdomain() {
		add_action(
			'init',
			function () {
				load_plugin_textdomain( 'goheadless', false, dirname( HEADLESS_MODE_BASENAME ) . '/languages' );
			}
		);
	}

	/**
	 * Initialize admin and frontend components.
	 *
	 * @return void
	 */
	private function init_components() {
		if ( is_admin() ) {
			$this->admin = new Headless_Mode_Admin();
		}

		$this->frontend = new Headless_Mode_Frontend();
	}
}
