<?php
/**
 * Settings page template.
 *
 * @package Headless_Mode
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings   = headless_mode_get_settings();
$active_tab = $this->get_active_tab();
$page_url   = admin_url( 'options-general.php?page=goheadless' );
?>
<div class="wrap headless-mode-wrap">

	<div class="headless-mode-header">
		<div class="headless-mode-header-left">
			<span class="headless-mode-header-icon dashicons dashicons-rest-api"></span>
			<div>
				<h1><?php esc_html_e( 'GoHeadless', 'goheadless' ); ?></h1>
				<span class="headless-mode-header-version">
					<?php
					/* translators: %s: plugin version number */
					printf( 'v%s', esc_html( HEADLESS_MODE_VERSION ) );
					?>
				</span>
			</div>
		</div>
		<div class="headless-mode-header-right">
			<?php if ( $settings['enabled'] ) : ?>
				<span class="headless-mode-header-status headless-mode-header-status--active">
					<span class="headless-mode-pulse"></span>
					<?php esc_html_e( 'Frontend Blocked', 'goheadless' ); ?>
				</span>
			<?php else : ?>
				<span class="headless-mode-header-status headless-mode-header-status--inactive">
					<?php esc_html_e( 'Inactive', 'goheadless' ); ?>
				</span>
			<?php endif; ?>
		</div>
	</div>

	<nav class="nav-tab-wrapper">
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'general', $page_url ) ); ?>"
			class="nav-tab <?php echo esc_attr( 'general' === $active_tab ? 'nav-tab-active' : '' ); ?>">
			<span class="dashicons dashicons-admin-settings"></span>
			<?php esc_html_e( 'General', 'goheadless' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'api', $page_url ) ); ?>"
			class="nav-tab <?php echo esc_attr( 'api' === $active_tab ? 'nav-tab-active' : '' ); ?>">
			<span class="dashicons dashicons-rest-api"></span>
			<?php esc_html_e( 'API & Routes', 'goheadless' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'security', $page_url ) ); ?>"
			class="nav-tab <?php echo esc_attr( 'security' === $active_tab ? 'nav-tab-active' : '' ); ?>">
			<span class="dashicons dashicons-shield"></span>
			<?php esc_html_e( 'Security', 'goheadless' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'status', $page_url ) ); ?>"
			class="nav-tab <?php echo esc_attr( 'status' === $active_tab ? 'nav-tab-active' : '' ); ?>">
			<span class="dashicons dashicons-dashboard"></span>
			<?php esc_html_e( 'Status', 'goheadless' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'about', $page_url ) ); ?>"
			class="nav-tab <?php echo esc_attr( 'about' === $active_tab ? 'nav-tab-active' : '' ); ?>">
			<span class="dashicons dashicons-info-outline"></span>
			<?php esc_html_e( 'About', 'goheadless' ); ?>
		</a>
	</nav>

	<?php settings_errors( 'headless_mode_settings' ); ?>

	<?php if ( in_array( $active_tab, array( 'general', 'api', 'security' ), true ) ) : ?>
		<form method="post" action="options.php" id="headless-mode-settings-form">
			<?php settings_fields( 'headless_mode_group' ); ?>

			<?php if ( 'general' === $active_tab ) : ?>
				<?php $this->render_tab_general( $settings ); ?>
			<?php elseif ( 'api' === $active_tab ) : ?>
				<?php $this->render_tab_api( $settings ); ?>
			<?php elseif ( 'security' === $active_tab ) : ?>
				<?php $this->render_tab_security( $settings ); ?>
			<?php endif; ?>

			<?php
			$this->render_hidden_fields( $settings, $active_tab );
			?>

			<div class="headless-mode-form-actions">
				<?php submit_button( __( 'Save Changes', 'goheadless' ), 'primary', 'submit', false ); ?>
			</div>
		</form>

		<form method="post" class="headless-mode-reset-form">
			<input type="hidden" name="headless_mode_reset" value="1" />
			<?php wp_nonce_field( 'headless_mode_reset', 'headless_mode_reset_nonce' ); ?>
			<?php submit_button( __( 'Reset to Defaults', 'goheadless' ), 'secondary headless-mode-reset-btn', 'reset-defaults', false ); ?>
		</form>
	<?php elseif ( 'status' === $active_tab ) : ?>
		<?php $this->render_tab_status( $settings ); ?>
	<?php elseif ( 'about' === $active_tab ) : ?>
		<?php $this->render_tab_about(); ?>
	<?php endif; ?>

</div>
