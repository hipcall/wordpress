<?php
/**
 * Admin settings registration, sanitization, and settings page rendering.
 *
 * @package Hipcall
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize region setting ('global' or 'tr').
 *
 * @param mixed $value Raw region value.
 * @return string Sanitized region code ('global' or 'tr').
 */
function hipcall_sanitize_region( $value ) {
	$clean = sanitize_text_field( (string) $value );
	return in_array( $clean, array( 'global', 'tr' ), true ) ? $clean : hipcall_get_default_region();
}

/**
 * Sanitize Widget ID.
 * Extracts the UUID from raw input first so pasting the full <script> snippet works.
 * Does not modify other options.
 *
 * @param mixed $value Raw Widget ID, URL, or <script> snippet.
 * @return string Validated lowercase UUID or empty string.
 */
function hipcall_sanitize_widget_id( $value ) {
	$raw = trim( (string) $value );
	if ( '' === $raw ) {
		return '';
	}

	$uuid = hipcall_extract_uuid( $raw );
	if ( '' !== $uuid && hipcall_is_valid_uuid( $uuid ) ) {
		return $uuid;
	}

	add_settings_error(
		'general',
		'hipcall_invalid_widget_id',
		esc_html__( 'Invalid Widget ID. Please enter a valid UUID or paste the full widget code from Settings › Inboxes › (inbox) › Widget settings.', 'hipcall' ),
		'error'
	);

	return (string) get_option( 'hipcall_widget_id', '' );
}

/**
 * Sanitize Identity Verification secret.
 * Since the secret is never echoed back into the page HTML, an empty submission preserves
 * the existing secret unless the administrator explicitly checks the clear checkbox.
 *
 * @param mixed $value Raw secret input.
 * @return string Sanitized secret.
 */
function hipcall_sanitize_secret_key( $value ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by WordPress core options.php before sanitize_callback runs.
	if ( ! empty( $_POST['hipcall_clear_secret'] ) ) {
		return '';
	}

	$clean = sanitize_text_field( trim( (string) $value ) );
	if ( '' === $clean ) {
		return (string) get_option( 'hipcall_secret_key', '' );
	}

	return $clean;
}

/**
 * Sanitize Tracker ID.
 * Extracts the UUID from raw input first so pasting the full <script> tag works.
 * Does not modify other options.
 *
 * @param mixed $value Raw Tracker ID or <script> tag.
 * @return string Validated lowercase UUID or empty string.
 */
function hipcall_sanitize_tracker_id( $value ) {
	$raw = trim( (string) $value );
	if ( '' === $raw ) {
		return '';
	}

	$uuid = hipcall_extract_uuid( $raw );
	if ( '' !== $uuid && hipcall_is_valid_uuid( $uuid ) ) {
		return $uuid;
	}

	add_settings_error(
		'general',
		'hipcall_invalid_tracker_id',
		esc_html__( 'Invalid Tracker ID. Please enter a valid UUID or paste the script tag from Settings › Marketing › Tracking.', 'hipcall' ),
		'error'
	);

	return (string) get_option( 'hipcall_tracker_id', '' );
}

/**
 * Register plugin settings using the WordPress Settings API.
 */
function hipcall_register_settings() {
	register_setting(
		'hipcall_settings_group',
		'hipcall_region',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'hipcall_sanitize_region',
			'default'           => hipcall_get_default_region(),
		)
	);

	register_setting(
		'hipcall_settings_group',
		'hipcall_widget_id',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'hipcall_sanitize_widget_id',
			'default'           => '',
		)
	);

	register_setting(
		'hipcall_settings_group',
		'hipcall_secret_key',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'hipcall_sanitize_secret_key',
			'default'           => '',
		)
	);

	register_setting(
		'hipcall_settings_group',
		'hipcall_tracker_id',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'hipcall_sanitize_tracker_id',
			'default'           => '',
		)
	);
}
add_action( 'admin_init', 'hipcall_register_settings' );

/**
 * Register "Settings > Hipcall" submenu page.
 */
function hipcall_add_admin_menu() {
	add_options_page(
		esc_html__( 'Hipcall Settings', 'hipcall' ),
		esc_html__( 'Hipcall', 'hipcall' ),
		'manage_options',
		'hipcall',
		'hipcall_settings_page_html'
	);
}
add_action( 'admin_menu', 'hipcall_add_admin_menu' );

/**
 * Add quick "Settings" link on the Plugins list page.
 *
 * @param array $links Existing plugin action links.
 * @return array Updated action links.
 */
function hipcall_action_links( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=hipcall' ) ) . '">' . esc_html__( 'Settings', 'hipcall' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . HIPCALL_PLUGIN_BASENAME, 'hipcall_action_links' );

/**
 * Render the admin settings page (WordPress Settings API - options.php).
 */
function hipcall_settings_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$region     = (string) get_option( 'hipcall_region', hipcall_get_default_region() );
	$widget_id  = (string) get_option( 'hipcall_widget_id', '' );
	$has_secret = '' !== trim( (string) get_option( 'hipcall_secret_key', '' ) );
	$tracker_id = (string) get_option( 'hipcall_tracker_id', '' );
	$base_url   = hipcall_get_base_url();
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p><?php esc_html_e( 'Configure your Hipcall widget and tracker settings below.', 'hipcall' ); ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'hipcall_settings_group' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="hipcall_region"><?php esc_html_e( 'Region', 'hipcall' ); ?></label>
						</th>
						<td>
							<select name="hipcall_region" id="hipcall_region">
								<option value="global" <?php selected( $region, 'global' ); ?>>
									<?php esc_html_e( 'Global (use.hipcall.com)', 'hipcall' ); ?>
								</option>
								<option value="tr" <?php selected( $region, 'tr' ); ?>>
									<?php esc_html_e( 'Türkiye (use.hipcall.com.tr)', 'hipcall' ); ?>
								</option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Select the Hipcall server region where your account is hosted.', 'hipcall' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<hr />

			<h2 class="title"><?php esc_html_e( 'Widget settings', 'hipcall' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="hipcall_widget_id"><?php esc_html_e( 'Widget code / ID', 'hipcall' ); ?></label>
						</th>
						<td>
							<input
								name="hipcall_widget_id"
								type="text"
								id="hipcall_widget_id"
								value="<?php echo esc_attr( $widget_id ); ?>"
								class="regular-text"
								placeholder="<?php echo esc_attr__( 'e.g. 8e32e0b8-fd33-450b-a5b0-b3359e1384e1', 'hipcall' ); ?>"
							/>
							<p class="description">
								<?php esc_html_e( 'Paste the widget ID or the full widget code from Settings › Inboxes › (inbox) › Widget settings.', 'hipcall' ); ?><br />
								<code><?php echo esc_html( $base_url ); ?>/widget/<strong>WIDGET_ID</strong></code>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="hipcall_secret_key"><?php esc_html_e( 'Identity verification', 'hipcall' ); ?></label>
						</th>
						<td>
							<input
								name="hipcall_secret_key"
								type="password"
								id="hipcall_secret_key"
								value=""
								class="regular-text"
								placeholder="<?php echo esc_attr( $has_secret ? __( 'Saved — enter a new value to change', 'hipcall' ) : __( 'e.g. whsec_...', 'hipcall' ) ); ?>"
								autocomplete="new-password"
							/>
							<?php if ( $has_secret ) : ?>
								<p class="description" style="color: #00a32a; font-weight: 500;">
									<?php esc_html_e( 'An identity verification secret is currently saved. Leave blank to keep the existing secret, or enter a new value to replace it.', 'hipcall' ); ?>
								</p>
								<p>
									<label for="hipcall_clear_secret">
										<input name="hipcall_clear_secret" type="checkbox" id="hipcall_clear_secret" value="1" />
										<?php esc_html_e( 'Remove saved identity verification secret', 'hipcall' ); ?>
									</label>
								</p>
							<?php else : ?>
								<p class="description">
									<?php esc_html_e( 'Optional: Copy the secret from Settings › Inboxes › (inbox) › Widget settings › Identity verification to authenticate logged-in WordPress users via HMAC-SHA256.', 'hipcall' ); ?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>

			<hr />

			<h2 class="title"><?php esc_html_e( 'Install tracker', 'hipcall' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="hipcall_tracker_id"><?php esc_html_e( 'Tracker ID', 'hipcall' ); ?></label>
						</th>
						<td>
							<input
								name="hipcall_tracker_id"
								type="text"
								id="hipcall_tracker_id"
								value="<?php echo esc_attr( $tracker_id ); ?>"
								class="regular-text"
								placeholder="<?php echo esc_attr__( 'e.g. e61f8fe3-3eea-44d0-bd1f-416c143c710c', 'hipcall' ); ?>"
							/>
							<p class="description">
								<?php esc_html_e( 'Optional: Paste the Tracker ID or script tag from Settings › Marketing › Tracking to add h.js to the <head> of every page.', 'hipcall' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( esc_html__( 'Save Changes', 'hipcall' ) ); ?>
		</form>
	</div>
	<?php
}
