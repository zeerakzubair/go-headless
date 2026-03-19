<?php
/**
 * Admin settings and UI.
 *
 * @package Headless_Mode
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Headless_Mode_Admin
 *
 * Handles the admin settings page, registration, and sanitization.
 */
class Headless_Mode_Admin {

	/**
	 * Settings page hook suffix.
	 *
	 * @var string
	 */
	private $hook_suffix;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . HEADLESS_MODE_BASENAME, array( $this, 'add_action_links' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_indicator' ), 100 );
		add_action( 'admin_notices', array( $this, 'headless_active_notice' ) );
	}

	/**
	 * Add the settings page to the admin menu.
	 *
	 * @return void
	 */
	public function add_menu_page() {
		$this->hook_suffix = add_options_page(
			__( 'GoHeadless', 'headless-mode' ),
			__( 'GoHeadless', 'headless-mode' ),
			'manage_options',
			'headless-mode',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin styles and scripts on our settings page only.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		// Admin bar CSS on all pages when headless is active.
		$settings = headless_mode_get_settings();
		if ( $settings['enabled'] ) {
			wp_enqueue_style(
				'headless-mode-adminbar',
				HEADLESS_MODE_URL . 'admin/css/admin-bar.css',
				array(),
				HEADLESS_MODE_VERSION
			);
		}

		if ( $this->hook_suffix !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'headless-mode-admin',
			HEADLESS_MODE_URL . 'admin/css/admin-style.css',
			array(),
			HEADLESS_MODE_VERSION
		);

		wp_enqueue_script(
			'headless-mode-admin',
			HEADLESS_MODE_URL . 'admin/js/admin-script.js',
			array(),
			HEADLESS_MODE_VERSION,
			true
		);

		wp_localize_script(
			'headless-mode-admin',
			'headlessModeAdmin',
			array(
				'confirmEnable' => __( 'Enabling GoHeadless will block all frontend access to your site. Visitors will see the blocked message or be redirected. Are you sure?', 'headless-mode' ),
				'confirmReset'  => __( 'This will reset all settings to their defaults. Are you sure?', 'headless-mode' ),
				'resetNonce'    => wp_create_nonce( 'headless_mode_reset' ),
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Show admin bar indicator when headless mode is active.
	 *
	 * @param \WP_Admin_Bar $admin_bar WordPress admin bar instance.
	 * @return void
	 */
	public function admin_bar_indicator( $admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = headless_mode_get_settings();

		if ( ! $settings['enabled'] ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'    => 'headless-mode-indicator',
				'title' => '<span class="ab-icon"></span>' . esc_html__( 'GoHeadless', 'headless-mode' ),
				'href'  => esc_url( admin_url( 'options-general.php?page=headless-mode' ) ),
				'meta'  => array(
					'class' => 'headless-mode-admin-bar',
					'title' => __( 'GoHeadless is active — frontend is blocked', 'headless-mode' ),
				),
			)
		);
	}

	/**
	 * Show persistent admin notice when headless mode is active.
	 *
	 * @return void
	 */
	public function headless_active_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = headless_mode_get_settings();

		if ( ! $settings['enabled'] ) {
			return;
		}

		// Only show on the plugin settings page and the dashboard.
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'settings_page_headless-mode' ), true ) ) {
			return;
		}

		printf(
			'<div class="notice notice-info"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
			esc_html__( 'GoHeadless is active.', 'headless-mode' ),
			esc_html__( 'Your site\'s frontend is currently blocked.', 'headless-mode' ),
			esc_url( admin_url( 'options-general.php?page=headless-mode' ) ),
			esc_html__( 'Manage Settings', 'headless-mode' )
		);
	}

	/**
	 * Register plugin settings with sanitization.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'headless_mode_group',
			'headless_mode_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => headless_mode_get_defaults(),
			)
		);

		// Handle reset to defaults.
		$this->handle_reset();
	}

	/**
	 * Handle reset to defaults AJAX and form action.
	 *
	 * @return void
	 */
	private function handle_reset() {
		if ( ! isset( $_POST['headless_mode_reset'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['headless_mode_reset_nonce'] ?? '' ) ), 'headless_mode_reset' ) ) {
			return;
		}

		update_option( 'headless_mode_settings', headless_mode_get_defaults(), true );

		add_settings_error(
			'headless_mode_settings',
			'settings_reset',
			__( 'Settings have been reset to defaults.', 'headless-mode' ),
			'updated'
		);
	}

	/**
	 * Sanitize all settings before saving.
	 *
	 * @param array $input Raw input from the settings form.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$defaults  = headless_mode_get_defaults();
		$sanitized = array();

		// Toggle fields: default to 0 if unchecked (not present in input).
		$toggles = array(
			'enabled',
			'enable_cors',
			'disable_rss',
			'disable_xmlrpc',
			'disable_oembed',
			'remove_wp_version',
			'disable_wp_emoji',
			'remove_shortlink',
			'remove_rsd_link',
			'remove_wlwmanifest',
			'disable_rest_for_visitors',
		);

		foreach ( $toggles as $key ) {
			$sanitized[ $key ] = ! empty( $input[ $key ] ) ? 1 : 0;
		}

		// Text fields.
		$sanitized['message'] = isset( $input['message'] )
			? sanitize_textarea_field( $input['message'] )
			: $defaults['message'];

		$sanitized['whitelist'] = isset( $input['whitelist'] )
			? sanitize_textarea_field( $input['whitelist'] )
			: $defaults['whitelist'];

		// URL fields.
		$sanitized['redirect_url'] = isset( $input['redirect_url'] )
			? esc_url_raw( $input['redirect_url'] )
			: '';

		$sanitized['cors_origin'] = isset( $input['cors_origin'] )
			? esc_url_raw( trim( $input['cors_origin'] ) )
			: '';

		// Response code.
		$sanitized['response_code'] = isset( $input['response_code'] )
			? absint( $input['response_code'] )
			: $defaults['response_code'];

		$valid_codes = array( 200, 403, 404, 503 );
		if ( ! in_array( $sanitized['response_code'], $valid_codes, true ) ) {
			$sanitized['response_code'] = 403;
		}

		return $sanitized;
	}

	/**
	 * Add Settings link on the Plugins page.
	 *
	 * @param array $links Existing plugin action links.
	 * @return array Modified links.
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=headless-mode' ) ),
			esc_html__( 'Settings', 'headless-mode' )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Render the main settings page with tabs.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include HEADLESS_MODE_DIR . 'admin/views/page-settings.php';
	}

	/**
	 * Get the current active tab.
	 *
	 * @return string Active tab slug.
	 */
	public function get_active_tab() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		$valid_tabs = array( 'general', 'api', 'security', 'status', 'about' );

		return in_array( $tab, $valid_tabs, true ) ? $tab : 'general';
	}

	/**
	 * Render hidden fields for settings not on the current tab.
	 *
	 * Prevents data loss when saving from a single tab.
	 *
	 * @param array  $settings Current settings.
	 * @param string $active_tab Currently active tab.
	 * @return void
	 */
	public function render_hidden_fields( $settings, $active_tab ) {
		$tab_fields = array(
			'general'  => array( 'enabled', 'message', 'redirect_url', 'response_code' ),
			'api'      => array( 'whitelist', 'enable_cors', 'cors_origin', 'disable_rest_for_visitors' ),
			'security' => array(
				'disable_rss',
				'disable_xmlrpc',
				'disable_oembed',
				'remove_wp_version',
				'disable_wp_emoji',
				'remove_shortlink',
				'remove_rsd_link',
				'remove_wlwmanifest',
			),
		);

		foreach ( $tab_fields as $tab => $fields ) {
			if ( $tab === $active_tab ) {
				continue;
			}
			foreach ( $fields as $field ) {
				$value = isset( $settings[ $field ] ) ? $settings[ $field ] : '';
				printf(
					'<input type="hidden" name="headless_mode_settings[%s]" value="%s" />',
					esc_attr( $field ),
					esc_attr( $value )
				);
			}
		}
	}

	/**
	 * Render the General tab.
	 *
	 * @param array $settings Current settings.
	 * @return void
	 */
	public function render_tab_general( $settings ) {
		?>
		<div class="headless-mode-section">
			<h2><?php esc_html_e( 'GoHeadless', 'headless-mode' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable GoHeadless', 'headless-mode' ); ?></th>
					<td>
						<label class="headless-mode-switch" for="headless_mode_enabled">
							<input type="checkbox"
								id="headless_mode_enabled"
								name="headless_mode_settings[enabled]"
								value="1"
								<?php checked( $settings['enabled'], 1 ); ?> />
							<span class="headless-mode-slider"></span>
						</label>
						<span class="headless-mode-switch-label">
							<?php esc_html_e( 'Block frontend access to this WordPress site', 'headless-mode' ); ?>
						</span>
					</td>
				</tr>
			</table>
		</div>

		<div class="headless-mode-section">
			<h2><?php esc_html_e( 'Blocked Page Settings', 'headless-mode' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="headless_mode_message"><?php esc_html_e( 'Custom Message', 'headless-mode' ); ?></label>
					</th>
					<td>
						<textarea id="headless_mode_message"
							name="headless_mode_settings[message]"
							rows="4"
							class="large-text"
							placeholder="<?php esc_attr_e( 'Enter the message visitors will see...', 'headless-mode' ); ?>"
						><?php echo esc_textarea( $settings['message'] ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'This message is shown when someone visits the frontend of your site.', 'headless-mode' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="headless_mode_response_code"><?php esc_html_e( 'HTTP Response Code', 'headless-mode' ); ?></label>
					</th>
					<td>
						<select id="headless_mode_response_code" name="headless_mode_settings[response_code]">
							<option value="403" <?php selected( $settings['response_code'], 403 ); ?>>
								403 &mdash; <?php esc_html_e( 'Forbidden (Recommended)', 'headless-mode' ); ?>
							</option>
							<option value="200" <?php selected( $settings['response_code'], 200 ); ?>>
								200 &mdash; <?php esc_html_e( 'OK', 'headless-mode' ); ?>
							</option>
							<option value="404" <?php selected( $settings['response_code'], 404 ); ?>>
								404 &mdash; <?php esc_html_e( 'Not Found', 'headless-mode' ); ?>
							</option>
							<option value="503" <?php selected( $settings['response_code'], 503 ); ?>>
								503 &mdash; <?php esc_html_e( 'Service Unavailable', 'headless-mode' ); ?>
							</option>
						</select>
						<p class="description">
							<?php esc_html_e( 'HTTP status code returned when blocking frontend access.', 'headless-mode' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<div class="headless-mode-section">
			<h2><?php esc_html_e( 'Redirect', 'headless-mode' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="headless_mode_redirect_url"><?php esc_html_e( 'Redirect URL', 'headless-mode' ); ?></label>
					</th>
					<td>
						<input type="url"
							id="headless_mode_redirect_url"
							name="headless_mode_settings[redirect_url]"
							value="<?php echo esc_url( $settings['redirect_url'] ); ?>"
							class="regular-text"
							placeholder="https://your-frontend-app.com" />
						<p class="description">
							<?php esc_html_e( 'Optional. If set, visitors are redirected here instead of seeing the blocked message.', 'headless-mode' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render the API & Routes tab.
	 *
	 * @param array $settings Current settings.
	 * @return void
	 */
	public function render_tab_api( $settings ) {
		?>
		<div class="headless-mode-section">
			<h2><?php esc_html_e( 'Route Whitelist', 'headless-mode' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="headless_mode_whitelist"><?php esc_html_e( 'Whitelisted Routes', 'headless-mode' ); ?></label>
					</th>
					<td>
						<textarea id="headless_mode_whitelist"
							name="headless_mode_settings[whitelist]"
							rows="6"
							class="large-text"
						><?php echo esc_textarea( $settings['whitelist'] ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'One route per line. These URL prefixes will bypass headless mode. The REST API (/wp-json) and admin (/wp-admin) are always accessible.', 'headless-mode' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<div class="headless-mode-section">
			<h2><?php esc_html_e( 'CORS Headers', 'headless-mode' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable CORS', 'headless-mode' ); ?></th>
					<td>
						<label class="headless-mode-switch" for="headless_mode_enable_cors">
							<input type="checkbox"
								id="headless_mode_enable_cors"
								name="headless_mode_settings[enable_cors]"
								value="1"
								data-toggles="cors-origin-row"
								<?php checked( $settings['enable_cors'], 1 ); ?> />
							<span class="headless-mode-slider"></span>
						</label>
						<span class="headless-mode-switch-label">
							<?php esc_html_e( 'Send Access-Control-Allow-Origin headers', 'headless-mode' ); ?>
						</span>
					</td>
				</tr>
				<tr id="cors-origin-row" class="<?php echo esc_attr( empty( $settings['enable_cors'] ) ? 'headless-mode-hidden' : '' ); ?>">
					<th scope="row">
						<label for="headless_mode_cors_origin"><?php esc_html_e( 'Allowed Origin', 'headless-mode' ); ?></label>
					</th>
					<td>
						<input type="url"
							id="headless_mode_cors_origin"
							name="headless_mode_settings[cors_origin]"
							value="<?php echo esc_url( $settings['cors_origin'] ); ?>"
							class="regular-text"
							placeholder="https://your-frontend-app.com" />
						<p class="description">
							<?php esc_html_e( 'Enter the specific origin URL of your frontend application. Do not use wildcard (*) for security.', 'headless-mode' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<div class="headless-mode-section">
			<h2><?php esc_html_e( 'REST API', 'headless-mode' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Restrict REST API', 'headless-mode' ); ?></th>
					<td>
						<label class="headless-mode-switch" for="headless_mode_disable_rest_visitors">
							<input type="checkbox"
								id="headless_mode_disable_rest_visitors"
								name="headless_mode_settings[disable_rest_for_visitors]"
								value="1"
								<?php checked( $settings['disable_rest_for_visitors'], 1 ); ?> />
							<span class="headless-mode-slider"></span>
						</label>
						<span class="headless-mode-switch-label">
							<?php esc_html_e( 'Require authentication for REST API access', 'headless-mode' ); ?>
						</span>
						<p class="description">
							<?php esc_html_e( 'When enabled, unauthenticated REST API requests will be rejected. Use with caution if your frontend relies on public API access.', 'headless-mode' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render the Security tab.
	 *
	 * @param array $settings Current settings.
	 * @return void
	 */
	public function render_tab_security( $settings ) {
		$security_options = array(
			array(
				'key'         => 'disable_rss',
				'label'       => __( 'Disable RSS Feeds', 'headless-mode' ),
				'description' => __( 'Prevents access to RSS, Atom, and RDF feeds.', 'headless-mode' ),
			),
			array(
				'key'         => 'disable_xmlrpc',
				'label'       => __( 'Disable XML-RPC', 'headless-mode' ),
				'description' => __( 'Disables the XML-RPC interface. Recommended unless you use the WordPress mobile app or Jetpack.', 'headless-mode' ),
			),
			array(
				'key'         => 'disable_oembed',
				'label'       => __( 'Disable oEmbed Discovery', 'headless-mode' ),
				'description' => __( 'Removes oEmbed discovery links and scripts from the page head.', 'headless-mode' ),
			),
			array(
				'key'         => 'remove_wp_version',
				'label'       => __( 'Remove WordPress Version', 'headless-mode' ),
				'description' => __( 'Removes the WordPress version number from the HTML head and asset URLs.', 'headless-mode' ),
			),
			array(
				'key'         => 'disable_wp_emoji',
				'label'       => __( 'Disable WordPress Emoji Scripts', 'headless-mode' ),
				'description' => __( 'Removes the emoji detection script and styles loaded by WordPress.', 'headless-mode' ),
			),
			array(
				'key'         => 'remove_shortlink',
				'label'       => __( 'Remove Shortlink Tag', 'headless-mode' ),
				'description' => __( 'Removes the shortlink tag from the HTML head and HTTP headers.', 'headless-mode' ),
			),
			array(
				'key'         => 'remove_rsd_link',
				'label'       => __( 'Remove RSD Link', 'headless-mode' ),
				'description' => __( 'Removes the Really Simple Discovery (RSD) link from the HTML head.', 'headless-mode' ),
			),
			array(
				'key'         => 'remove_wlwmanifest',
				'label'       => __( 'Remove WLW Manifest Link', 'headless-mode' ),
				'description' => __( 'Removes the Windows Live Writer manifest link from the HTML head.', 'headless-mode' ),
			),
		);
		?>
		<div class="headless-mode-section">
			<h2><?php esc_html_e( 'Security & Cleanup', 'headless-mode' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Harden your WordPress installation by disabling unnecessary features and removing information leaks. These settings work independently of the headless mode toggle.', 'headless-mode' ); ?>
			</p>
			<table class="form-table" role="presentation">
				<?php foreach ( $security_options as $option ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $option['label'] ); ?></th>
						<td>
							<label class="headless-mode-switch" for="headless_mode_<?php echo esc_attr( $option['key'] ); ?>">
								<input type="checkbox"
									id="headless_mode_<?php echo esc_attr( $option['key'] ); ?>"
									name="headless_mode_settings[<?php echo esc_attr( $option['key'] ); ?>]"
									value="1"
									<?php checked( $settings[ $option['key'] ], 1 ); ?> />
								<span class="headless-mode-slider"></span>
							</label>
							<span class="headless-mode-switch-label">
								<?php echo esc_html( $option['description'] ); ?>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
		<?php
	}

	/**
	 * Render the Status tab.
	 *
	 * @param array $settings Current settings.
	 * @return void
	 */
	public function render_tab_status( $settings ) {
		$is_woo_active = class_exists( 'WooCommerce' );
		?>
		<div class="headless-mode-section">
			<h2><?php esc_html_e( 'Current Status', 'headless-mode' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Overview of your headless mode configuration and environment.', 'headless-mode' ); ?>
			</p>
		</div>

		<div class="headless-mode-status-grid">
			<div class="headless-mode-status-card">
				<h3><?php esc_html_e( 'GoHeadless', 'headless-mode' ); ?></h3>
				<div class="status-value">
					<?php if ( $settings['enabled'] ) : ?>
						<span class="status-badge status-badge--on"><?php esc_html_e( 'Active', 'headless-mode' ); ?></span>
					<?php else : ?>
						<span class="status-badge status-badge--off"><?php esc_html_e( 'Inactive', 'headless-mode' ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<div class="headless-mode-status-card">
				<h3><?php esc_html_e( 'Frontend Behavior', 'headless-mode' ); ?></h3>
				<div class="status-value">
					<?php if ( ! empty( $settings['redirect_url'] ) ) : ?>
						<?php esc_html_e( 'Redirecting to:', 'headless-mode' ); ?>
						<br><code><?php echo esc_html( $settings['redirect_url'] ); ?></code>
					<?php else : ?>
						<?php esc_html_e( 'Showing blocked message', 'headless-mode' ); ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="headless-mode-status-card">
				<h3><?php esc_html_e( 'Response Code', 'headless-mode' ); ?></h3>
				<div class="status-value">
					<span class="status-badge status-badge--info"><?php echo esc_html( $settings['response_code'] ); ?></span>
				</div>
			</div>

			<div class="headless-mode-status-card">
				<h3><?php esc_html_e( 'WooCommerce', 'headless-mode' ); ?></h3>
				<div class="status-value">
					<?php if ( $is_woo_active ) : ?>
						<span class="status-badge status-badge--on"><?php esc_html_e( 'Detected', 'headless-mode' ); ?></span>
					<?php else : ?>
						<span class="status-badge status-badge--off"><?php esc_html_e( 'Not Active', 'headless-mode' ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<div class="headless-mode-status-card">
				<h3><?php esc_html_e( 'REST API', 'headless-mode' ); ?></h3>
				<div class="status-value">
					<span class="status-badge status-badge--on"><?php esc_html_e( 'Available', 'headless-mode' ); ?></span>
					<br>
					<code><?php echo esc_html( rest_url() ); ?></code>
				</div>
			</div>

			<div class="headless-mode-status-card">
				<h3><?php esc_html_e( 'CORS Headers', 'headless-mode' ); ?></h3>
				<div class="status-value">
					<?php if ( $settings['enable_cors'] && ! empty( $settings['cors_origin'] ) ) : ?>
						<span class="status-badge status-badge--on"><?php esc_html_e( 'Enabled', 'headless-mode' ); ?></span>
						<br>
						<code><?php echo esc_html( $settings['cors_origin'] ); ?></code>
					<?php else : ?>
						<span class="status-badge status-badge--off"><?php esc_html_e( 'Disabled', 'headless-mode' ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<div class="headless-mode-status-card">
				<h3><?php esc_html_e( 'RSS Feeds', 'headless-mode' ); ?></h3>
				<div class="status-value">
					<?php if ( $settings['disable_rss'] ) : ?>
						<span class="status-badge status-badge--on"><?php esc_html_e( 'Disabled', 'headless-mode' ); ?></span>
					<?php else : ?>
						<span class="status-badge status-badge--info"><?php esc_html_e( 'Active', 'headless-mode' ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<div class="headless-mode-status-card">
				<h3><?php esc_html_e( 'XML-RPC', 'headless-mode' ); ?></h3>
				<div class="status-value">
					<?php if ( $settings['disable_xmlrpc'] ) : ?>
						<span class="status-badge status-badge--on"><?php esc_html_e( 'Disabled', 'headless-mode' ); ?></span>
					<?php else : ?>
						<span class="status-badge status-badge--info"><?php esc_html_e( 'Active', 'headless-mode' ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<div class="headless-mode-status-card">
				<h3><?php esc_html_e( 'WordPress', 'headless-mode' ); ?></h3>
				<div class="status-value">
					<span class="status-badge status-badge--info"><?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
				</div>
			</div>

			<div class="headless-mode-status-card">
				<h3><?php esc_html_e( 'PHP', 'headless-mode' ); ?></h3>
				<div class="status-value">
					<span class="status-badge status-badge--info"><?php echo esc_html( PHP_VERSION ); ?></span>
				</div>
			</div>

			<div class="headless-mode-status-card">
				<h3><?php esc_html_e( 'Whitelisted Routes', 'headless-mode' ); ?></h3>
				<div class="status-value">
					<?php
					$routes = array_filter( array_map( 'trim', explode( "\n", $settings['whitelist'] ) ) );
					if ( ! empty( $routes ) ) {
						foreach ( $routes as $route ) {
							echo '<code>' . esc_html( $route ) . '</code><br>';
						}
					} else {
						esc_html_e( 'None configured', 'headless-mode' );
					}
					?>
				</div>
			</div>

			<div class="headless-mode-status-card">
				<h3><?php esc_html_e( 'Active Theme', 'headless-mode' ); ?></h3>
				<div class="status-value">
					<?php echo esc_html( wp_get_theme()->get( 'Name' ) ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the About tab.
	 *
	 * @return void
	 */
	public function render_tab_about() {
		?>
		<div class="headless-mode-about">
			<div class="headless-mode-about-card">
				<h3><?php esc_html_e( 'About GoHeadless', 'headless-mode' ); ?></h3>
				<p>
					<?php esc_html_e( 'GoHeadless converts your WordPress or WooCommerce site into a headless CMS by blocking frontend access while keeping the REST API, Store API, and wp-admin fully functional.', 'headless-mode' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Version:', 'headless-mode' ); ?>
					<strong><?php echo esc_html( HEADLESS_MODE_VERSION ); ?></strong>
				</p>
				<p>
					<?php esc_html_e( 'Author:', 'headless-mode' ); ?>
					<strong>Zeerak Zubair</strong>
				</p>
			</div>

			<div class="headless-mode-about-card">
				<h3><?php esc_html_e( 'Perfect For', 'headless-mode' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Next.js, Nuxt, or Gatsby frontends', 'headless-mode' ); ?></li>
					<li><?php esc_html_e( 'Headless WooCommerce stores', 'headless-mode' ); ?></li>
					<li><?php esc_html_e( 'React or Vue.js single-page applications', 'headless-mode' ); ?></li>
					<li><?php esc_html_e( 'Mobile app backends', 'headless-mode' ); ?></li>
					<li><?php esc_html_e( 'API-only WordPress installations', 'headless-mode' ); ?></li>
					<li><?php esc_html_e( 'Static site generators using WordPress as a data source', 'headless-mode' ); ?></li>
				</ul>
			</div>

			<div class="headless-mode-about-card">
				<h3><?php esc_html_e( 'Free Features', 'headless-mode' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Toggle headless mode on/off', 'headless-mode' ); ?></li>
					<li><?php esc_html_e( 'Custom blocked page message', 'headless-mode' ); ?></li>
					<li><?php esc_html_e( 'Frontend redirect to your app', 'headless-mode' ); ?></li>
					<li><?php esc_html_e( 'Configurable HTTP response codes', 'headless-mode' ); ?></li>
					<li><?php esc_html_e( 'Route whitelisting', 'headless-mode' ); ?></li>
					<li><?php esc_html_e( 'CORS header management', 'headless-mode' ); ?></li>
					<li><?php esc_html_e( 'Security hardening (RSS, XML-RPC, oEmbed)', 'headless-mode' ); ?></li>
					<li><?php esc_html_e( 'WP version removal & cleanup', 'headless-mode' ); ?></li>
					<li><?php esc_html_e( 'Status dashboard', 'headless-mode' ); ?></li>
				</ul>
			</div>

			<div class="headless-mode-about-card">
				<h3><?php esc_html_e( 'Links & Support', 'headless-mode' ); ?></h3>
				<ul>
					<li>
						<a href="https://zetheriallabs.com/goheadless" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Plugin Website', 'headless-mode' ); ?>
						</a>
					</li>
					<li>
						<a href="https://github.com/zeerakzubair/headless-mode/issues" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Report a Bug', 'headless-mode' ); ?>
						</a>
					</li>
					<li>
						<a href="https://wordpress.org/support/plugin/headless-mode/" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Support Forum', 'headless-mode' ); ?>
						</a>
					</li>
				</ul>
			</div>
		</div>

		<div class="headless-mode-pro-teaser">
			<h3>
				<?php esc_html_e( 'GoHeadless Pro', 'headless-mode' ); ?>
				<span class="headless-mode-pro-badge"><?php esc_html_e( 'Coming Soon', 'headless-mode' ); ?></span>
			</h3>
			<p><?php esc_html_e( 'Unlock advanced features with a one-time payment. No subscriptions.', 'headless-mode' ); ?></p>
			<ul class="pro-features-list">
				<li><?php esc_html_e( 'Custom HTML/CSS blocked page template', 'headless-mode' ); ?></li>
				<li><?php esc_html_e( 'IP-based access whitelisting', 'headless-mode' ); ?></li>
				<li><?php esc_html_e( 'Multiple redirect rules with conditions', 'headless-mode' ); ?></li>
				<li><?php esc_html_e( 'Import & export settings', 'headless-mode' ); ?></li>
				<li><?php esc_html_e( 'Custom response headers', 'headless-mode' ); ?></li>
				<li><?php esc_html_e( 'Role-based frontend access', 'headless-mode' ); ?></li>
				<li><?php esc_html_e( 'Maintenance mode with countdown', 'headless-mode' ); ?></li>
				<li><?php esc_html_e( 'Priority email support', 'headless-mode' ); ?></li>
			</ul>
		</div>
		<?php
	}
}
