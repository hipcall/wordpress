<?php
/**
 * Plugin Name:       Hipcall
 * Plugin URI:        https://github.com/hipcall/wordpress
 * Description:       Integrate the Hipcall LiveChat (Webchat) widget and Marketing Tracker (h.js) into WordPress with HMAC-SHA256 user verification.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Tested up to:      7.1
 * Requires PHP:      7.2
 * Author:            Hipcall
 * Author URI:        https://www.hipcall.com/
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       hipcall
 *
 * @package Hipcall
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HIPCALL_VERSION', '1.0.0' );

/**
 * Built-in Turkish translations when WordPress site locale is tr_TR.
 * Default source strings are in English per WordPress.org international standards.
 *
 * @param string $translation Translated text.
 * @param string $text        Original text in English.
 * @return string Localized text.
 */
function hipcall_gettext_tr( $translation, $text ) {
	if ( strpos( determine_locale(), 'tr' ) !== 0 ) {
		return $translation;
	}

	static $tr_map = array(
		'Hipcall Settings'                                 => 'Hipcall Ayarları',
		'Settings'                                         => 'Ayarlar',
		'Configure your Hipcall LiveChat (Webchat) widget and Marketing Tracker (h.js) settings below.' => 'Hipcall Canlı Destek (Webchat) ve Pazarlama İzleyicisi (h.js) bileşenlerini sitenizde aktif etmek için aşağıdaki bilgileri doldurun.',
		'Region / Server'                                  => 'Bölge / Sunucu (Region)',
		'Global (use.hipcall.com)'                         => 'Global (use.hipcall.com)',
		'Türkiye (use.hipcall.com.tr)'                     => 'Türkiye (use.hipcall.com.tr)',
		'Select the Hipcall server region where your account is hosted (use.hipcall.com for Global, use.hipcall.com.tr for Türkiye).' => 'Hipcall hesabınızın bulunduğu sunucu bölgesini seçin (Türkiye için use.hipcall.com.tr, Global için use.hipcall.com).',
		'LiveChat (Webchat Widget)'                        => 'Canlı Destek (Webchat Widget)',
		'Widget ID'                                        => 'Widget ID',
		'e.g. 8e32e0b8-fd33-450b-a5b0-b3359e1384e1'        => 'Örn: 8e32e0b8-fd33-450b-a5b0-b3359e1384e1',
		'Enter the unique Widget ID found at the end of your widget installation URL in the Hipcall dashboard (Settings > Inboxes > Webchat).' => 'Hipcall panelinizdeki (Ayarlar > Gelen Kutuları > Webchat) widget kurulum kodunun sonunda yer alan benzersiz ID\'yi girin.',
		'Secret Key'                                       => 'Secret Key (Gizli Anahtar)',
		'e.g. whsec_...'                                   => 'Örn: whsec_...',
		'Optional: Enter your Hipcall Secret Key to automatically authenticate logged-in WordPress users as "Verified" via HMAC-SHA256.' => 'İsteğe bağlı: Giriş yapmış WordPress üyelerinin Hipcall\'da "Verified (Doğrulanmış)" olarak görünmesi için Hipcall panelindeki gizli anahtarınızı girin.',
		'Marketing Tracker (h.js)'                         => 'Pazarlama İzleyicisi (Marketing Tracker - h.js)',
		'Tracker ID'                                       => 'İzleyici Kimliği (Tracker ID)',
		'e.g. e61f8fe3-3eea-44d0-bd1f-416c143c710c'        => 'Örn: e61f8fe3-3eea-44d0-bd1f-416c143c710c',
		'Optional: Enter the Tracker ID from your Hipcall dashboard (Settings > Marketing > Tracking) to automatically inject h.js into the <head> of every page.' => 'İsteğe bağlı: Hipcall panelinizdeki (Ayarlar > Pazarlama > İzleme) bölümünde yer alan İzleyici Kimliğini girin. Doldurulduğunda her sayfanın <head> bölümüne h.js izleyicisini otomatik ekler.',
		'Save Changes'                                     => 'Değişiklikleri Kaydet',
	);

	return isset( $tr_map[ $text ] ) ? $tr_map[ $text ] : $translation;
}
add_filter( 'gettext_hipcall', 'hipcall_gettext_tr', 10, 2 );

/**
 * Get the default region based on the WordPress site locale ('tr' for tr_TR, 'global' otherwise).
 *
 * @return string 'tr' or 'global'.
 */
function hipcall_get_default_region() {
	return ( strpos( determine_locale(), 'tr' ) === 0 ) ? 'tr' : 'global';
}

/**
 * Get the active Hipcall base domain according to the selected region.
 *
 * @return string 'https://use.hipcall.com.tr' or 'https://use.hipcall.com'.
 */
function hipcall_get_base_url() {
	$region = get_option( 'hipcall_region', hipcall_get_default_region() );
	return ( 'tr' === $region ) ? 'https://use.hipcall.com.tr' : 'https://use.hipcall.com';
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
 * Sanitize Widget ID (extracts UUID if the user pasted the full widget URL).
 *
 * @param mixed $value Raw Widget ID or URL value.
 * @return string Sanitized Widget ID.
 */
function hipcall_sanitize_widget_id( $value ) {
	$clean = sanitize_text_field( (string) $value );
	if ( strpos( $clean, 'use.hipcall.com.tr' ) !== false ) {
		update_option( 'hipcall_region', 'tr' );
	} elseif ( strpos( $clean, 'use.hipcall.com' ) !== false ) {
		update_option( 'hipcall_region', 'global' );
	}
	if ( strpos( $clean, '/widget/' ) !== false ) {
		$parts = explode( '/widget/', $clean );
		$clean = trim( (string) end( $parts ), " \t\n\r\0\x0B/\"'" );
	}
	return $clean;
}

/**
 * Sanitize Tracker ID (extracts data-hipcall-id if the user pasted the full <script> tag).
 *
 * @param mixed $value Raw Tracker ID or script tag.
 * @return string Sanitized Tracker ID.
 */
function hipcall_sanitize_tracker_id( $value ) {
	$raw = (string) $value;
	if ( strpos( $raw, 'use.hipcall.com.tr' ) !== false ) {
		update_option( 'hipcall_region', 'tr' );
	} elseif ( strpos( $raw, 'use.hipcall.com' ) !== false ) {
		update_option( 'hipcall_region', 'global' );
	}
	if ( preg_match( '/data-hipcall-id=["\']([^"\']+)["\']/i', $raw, $matches ) ) {
		return sanitize_text_field( trim( $matches[1] ) );
	}
	return sanitize_text_field( trim( $raw, " \t\n\r\0\x0B/\"'" ) );
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
			'sanitize_callback' => 'sanitize_text_field',
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
 * 1. Register "Settings > Hipcall" submenu page.
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
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'hipcall_action_links' );

/**
 * 2. Render the admin settings page (WordPress Settings API - options.php).
 */
function hipcall_settings_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$region     = (string) get_option( 'hipcall_region', hipcall_get_default_region() );
	$widget_id  = (string) get_option( 'hipcall_widget_id', '' );
	$secret_key = (string) get_option( 'hipcall_secret_key', '' );
	$tracker_id = (string) get_option( 'hipcall_tracker_id', '' );
	$base_url   = hipcall_get_base_url();
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p><?php esc_html_e( 'Configure your Hipcall LiveChat (Webchat) widget and Marketing Tracker (h.js) settings below.', 'hipcall' ); ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'hipcall_settings_group' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="hipcall_region"><?php esc_html_e( 'Region / Server', 'hipcall' ); ?></label>
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
								<?php esc_html_e( 'Select the Hipcall server region where your account is hosted (use.hipcall.com for Global, use.hipcall.com.tr for Türkiye).', 'hipcall' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<hr />

			<h2 class="title"><?php esc_html_e( 'LiveChat (Webchat Widget)', 'hipcall' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="hipcall_widget_id"><?php esc_html_e( 'Widget ID', 'hipcall' ); ?></label>
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
								<?php esc_html_e( 'Enter the unique Widget ID found at the end of your widget installation URL in the Hipcall dashboard (Settings > Inboxes > Webchat).', 'hipcall' ); ?><br />
								<code><?php echo esc_html( $base_url ); ?>/widget/<strong>WIDGET_ID</strong></code>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="hipcall_secret_key"><?php esc_html_e( 'Secret Key', 'hipcall' ); ?></label>
						</th>
						<td>
							<input
								name="hipcall_secret_key"
								type="password"
								id="hipcall_secret_key"
								value="<?php echo esc_attr( $secret_key ); ?>"
								class="regular-text"
								placeholder="<?php echo esc_attr__( 'e.g. whsec_...', 'hipcall' ); ?>"
								autocomplete="off"
							/>
							<p class="description">
								<?php esc_html_e( 'Optional: Enter your Hipcall Secret Key to automatically authenticate logged-in WordPress users as "Verified" via HMAC-SHA256.', 'hipcall' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<hr />

			<h2 class="title"><?php esc_html_e( 'Marketing Tracker (h.js)', 'hipcall' ); ?></h2>
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
								<?php esc_html_e( 'Optional: Enter the Tracker ID from your Hipcall dashboard (Settings > Marketing > Tracking) to automatically inject h.js into the <head> of every page.', 'hipcall' ); ?><br />
								<code>&lt;script async src="<?php echo esc_html( $base_url ); ?>/track/v1/h.js" data-hipcall-id="<strong>TRACKER_ID</strong>"&gt;&lt;/script&gt;</code>
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

/**
 * 3. Enqueue frontend scripts (h.js Tracker in <head> + Webchat Widget in Footer & HMAC-SHA256).
 */
function hipcall_enqueue_scripts() {
	$base_url   = hipcall_get_base_url();
	$tracker_id = trim( (string) get_option( 'hipcall_tracker_id', '' ) );
	$widget_id  = trim( (string) get_option( 'hipcall_widget_id', '' ) );

	// 3A. Marketing Tracker (h.js) - Enqueued asynchronously in <head>.
	if ( ! empty( $tracker_id ) ) {
		wp_enqueue_script(
			'hipcall-tracker',
			esc_url( $base_url . '/track/v1/h.js' ),
			array(),
			HIPCALL_VERSION,
			array(
				'in_footer' => false,
				'strategy'  => 'async',
			)
		);
	}

	// 3B. LiveChat (Webchat Widget) - Enqueued asynchronously in footer.
	if ( empty( $widget_id ) ) {
		return;
	}

	$secret_key  = trim( (string) get_option( 'hipcall_secret_key', '' ) );
	$widget_url  = $base_url . '/widget/' . rawurlencode( $widget_id );
	$is_verified = false;
	$user_email  = '';
	$user_name   = '';
	$user_hash   = '';

	if ( ! empty( $secret_key ) && is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$user_email   = isset( $current_user->user_email ) ? trim( $current_user->user_email ) : '';
		$user_name    = ! empty( $current_user->display_name ) ? $current_user->display_name : $current_user->user_login;

		if ( ! empty( $user_email ) ) {
			$user_hash   = strtolower( hash_hmac( 'sha256', $user_email, $secret_key, false ) );
			$is_verified = true;
		}
	}

	wp_enqueue_script(
		'hipcall-widget',
		esc_url( $widget_url ),
		array(),
		HIPCALL_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'async',
		)
	);

	$inbox_id_json = wp_json_encode( $widget_id );

	if ( $is_verified ) {
		$user_payload_json = wp_json_encode(
			array(
				'email'     => $user_email,
				'name'      => $user_name,
				'user_hash' => $user_hash,
			)
		);
		$user_email_json   = wp_json_encode( $user_email );
		$user_name_json    = wp_json_encode( $user_name );
		$user_hash_json    = wp_json_encode( $user_hash );

		$before_script = "(function(w){w['HipcallWidget']='hc';w['hc']=w['hc']||function(){(w['hc'].q=w['hc'].q||[]).push(arguments)};try{var inboxId={$inbox_id_json};var storageKey='hipcall_session_'+inboxId;var existing=JSON.parse(localStorage.getItem(storageKey)||'{}');var currentEmail={$user_email_json};var previousEmail=existing.visitorData&&existing.visitorData.email?existing.visitorData.email:null;var isSameUser=!previousEmail||previousEmail===currentEmail;var sessionId=(isSameUser&&existing.sessionId)?existing.sessionId:('hc_'+Math.random().toString(36).substr(2,9)+'_'+Date.now());existing.sessionId=sessionId;existing.timestamp=isSameUser&&existing.timestamp?existing.timestamp:Date.now();existing.inboxId=inboxId;existing.visitorData={name:{$user_name_json},email:currentEmail};existing.visitorSources={name:'website',email:'website'};existing.userHash={$user_hash_json};localStorage.setItem(storageKey,JSON.stringify(existing));}catch(e){}})(window);hc('user',{$user_payload_json});";
	} else {
		$before_script = "(function(w){w['HipcallWidget']='hc';w['hc']=w['hc']||function(){(w['hc'].q=w['hc'].q||[]).push(arguments)};try{var storageKey='hipcall_session_'+{$inbox_id_json};var existing=JSON.parse(localStorage.getItem(storageKey)||'{}');if(existing&&existing.userHash){localStorage.removeItem(storageKey);}}catch(e){}})(window);";
	}

	wp_add_inline_script( 'hipcall-widget', $before_script, 'before' );
}
add_action( 'wp_enqueue_scripts', 'hipcall_enqueue_scripts' );

/**
 * Add data-hipcall-id attribute to the Marketing Tracker (h.js) script tag.
 *
 * @param string $tag    Generated <script> HTML tag.
 * @param string $handle Script handle name.
 * @return string Modified <script> tag.
 */
function hipcall_script_loader_tag( $tag, $handle ) {
	if ( 'hipcall-tracker' === $handle ) {
		$tracker_id = trim( (string) get_option( 'hipcall_tracker_id', '' ) );
		if ( ! empty( $tracker_id ) && strpos( $tag, 'data-hipcall-id=' ) === false ) {
			$tag = str_replace( ' src=', ' data-hipcall-id="' . esc_attr( $tracker_id ) . '" src=', $tag );
		}
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'hipcall_script_loader_tag', 10, 2 );
