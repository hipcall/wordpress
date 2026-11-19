<?php
/**
 * Plugin Name:       Hipcall
 * Plugin URI:        https://github.com/hipcall/wordpress
 * Description:       Hipcall canlı destek (Webchat) widget'ını ve pazarlama izleyicisini (h.js) WordPress sitenize kolayca entegre eder; giriş yapmış üyeler için HMAC-SHA256 tabanlı kimlik doğrulaması (Verified User) sağlar.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Tested up to:      7.1
 * Requires PHP:      7.2
 * Author:            Hipcall
 * Author URI:        https://www.hipcall.com.tr/
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       hipcall
 *
 * @package Hipcall
 */

// Doğrudan dosya erişimini engelle.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HIPCALL_VERSION', '1.0.0' );

/**
 * Widget ID değerini temizle (tam URL yapıştırılmışsa yalnızca ID kısmını ayıkla).
 *
 * @param mixed $value Ham Widget ID veya URL değeri.
 * @return string Temizlenmiş Widget ID.
 */
function hipcall_sanitize_widget_id( $value ) {
	$clean = sanitize_text_field( (string) $value );
	if ( strpos( $clean, '/widget/' ) !== false ) {
		$parts = explode( '/widget/', $clean );
		$clean = trim( (string) end( $parts ), " \t\n\r\0\x0B/\"'" );
	}
	return $clean;
}

/**
 * Tracker ID (İzleyici Kimliği) değerini temizle.
 * Kullanıcı yanlışlıkla tüm <script ... data-hipcall-id="..."> etiketini yapıştırırsa sadece ID'yi ayıklar.
 *
 * @param mixed $value Ham Tracker ID veya script etiketi.
 * @return string Temizlenmiş Tracker ID.
 */
function hipcall_sanitize_tracker_id( $value ) {
	$raw = (string) $value;
	if ( preg_match( '/data-hipcall-id=["\']([^"\']+)["\']/i', $raw, $matches ) ) {
		return sanitize_text_field( trim( $matches[1] ) );
	}
	return sanitize_text_field( trim( $raw, " \t\n\r\0\x0B/\"'" ) );
}

/**
 * Ayarları WordPress Settings API ile kaydet.
 */
function hipcall_register_settings() {
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
 * 1. Yönetim Paneli Menüsü: "Ayarlar > Hipcall" alt menüsünü ekle.
 */
function hipcall_add_admin_menu() {
	add_options_page(
		esc_html__( 'Hipcall Ayarları', 'hipcall' ),
		esc_html__( 'Hipcall', 'hipcall' ),
		'manage_options',
		'hipcall',
		'hipcall_settings_page_html'
	);
}
add_action( 'admin_menu', 'hipcall_add_admin_menu' );

/**
 * Eklentiler sayfasında hızlı "Ayarlar" bağlantısı ekle.
 *
 * @param array $links Mevcut eklenti aksiyon bağlantıları.
 * @return array Güncellenmiş bağlantılar.
 */
function hipcall_action_links( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=hipcall' ) ) . '">' . esc_html__( 'Ayarlar', 'hipcall' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'hipcall_action_links' );

/**
 * 2. Admin Ayarlar Sayfası Görünümü (WordPress Settings API - options.php).
 */
function hipcall_settings_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$widget_id  = (string) get_option( 'hipcall_widget_id', '' );
	$secret_key = (string) get_option( 'hipcall_secret_key', '' );
	$tracker_id = (string) get_option( 'hipcall_tracker_id', '' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p><?php esc_html_e( 'Hipcall Canlı Destek (Webchat) ve Pazarlama İzleyicisi (h.js) bileşenlerini sitenizde aktif etmek için aşağıdaki bilgileri doldurun.', 'hipcall' ); ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'hipcall_settings_group' ); ?>

			<h2 class="title"><?php esc_html_e( 'Canlı Destek (Webchat Widget)', 'hipcall' ); ?></h2>
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
								placeholder="Örn: 8e32e0b8-fd33-450b-a5b0-b3359e1384e1"
							/>
							<p class="description">
								<?php esc_html_e( 'Hipcall panelinizdeki (Ayarlar > Gelen Kutuları > Webchat) widget kurulum kodunun sonunda yer alan benzersiz ID\'yi girin.', 'hipcall' ); ?><br />
								<code>https://use.hipcall.com.tr/widget/<strong>WIDGET_ID</strong></code>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="hipcall_secret_key"><?php esc_html_e( 'Secret Key (Gizli Anahtar)', 'hipcall' ); ?></label>
						</th>
						<td>
							<input
								name="hipcall_secret_key"
								type="password"
								id="hipcall_secret_key"
								value="<?php echo esc_attr( $secret_key ); ?>"
								class="regular-text"
								placeholder="Örn: whsec_..."
								autocomplete="off"
							/>
							<p class="description">
								<?php esc_html_e( 'İsteğe bağlı: Giriş yapmış WordPress üyelerinin Hipcall\'da "Verified (Doğrulanmış)" olarak görünmesi için Hipcall panelindeki gizli anahtarınızı girin.', 'hipcall' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<hr />

			<h2 class="title"><?php esc_html_e( 'Pazarlama İzleyicisi (Marketing Tracker - h.js)', 'hipcall' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="hipcall_tracker_id"><?php esc_html_e( 'İzleyici Kimliği (Tracker ID)', 'hipcall' ); ?></label>
						</th>
						<td>
							<input
								name="hipcall_tracker_id"
								type="text"
								id="hipcall_tracker_id"
								value="<?php echo esc_attr( $tracker_id ); ?>"
								class="regular-text"
								placeholder="Örn: e61f8fe3-3eea-44d0-bd1f-416c143c710c"
							/>
							<p class="description">
								<?php esc_html_e( 'Hipcall panelinizdeki (Ayarlar > Pazarlama > İzleme) bölümünde yer alan İzleyici Kimliğini girin. Doldurulduğunda her sayfanın <head> bölümüne h.js izleyicisini otomatik ekler.', 'hipcall' ); ?><br />
								<code>&lt;script async src="https://use.hipcall.com.tr/track/v1/h.js" data-hipcall-id="<strong>TRACKER_ID</strong>"&gt;&lt;/script&gt;</code>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( esc_html__( 'Değişiklikleri Kaydet', 'hipcall' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * 3. Frontend JavaScript Enjeksiyonu (h.js Tracker <head> + Webchat Widget Footer & HMAC-SHA256).
 */
function hipcall_enqueue_scripts() {
	$tracker_id = trim( (string) get_option( 'hipcall_tracker_id', '' ) );
	$widget_id  = trim( (string) get_option( 'hipcall_widget_id', '' ) );

	// 3A. Pazarlama İzleyicisi (h.js) - <head> içine async olarak eklenir.
	if ( ! empty( $tracker_id ) ) {
		wp_enqueue_script(
			'hipcall-tracker',
			'https://use.hipcall.com.tr/track/v1/h.js',
			array(),
			HIPCALL_VERSION,
			array(
				'in_footer' => false,
				'strategy'  => 'async',
			)
		);
	}

	// 3B. Canlı Destek (Webchat Widget) - Footer içine async olarak eklenir.
	if ( empty( $widget_id ) ) {
		return;
	}

	$secret_key  = trim( (string) get_option( 'hipcall_secret_key', '' ) );
	$widget_url  = 'https://use.hipcall.com.tr/widget/' . rawurlencode( $widget_id );
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
 * Pazarlama izleyicisi (h.js) script etiketine data-hipcall-id özniteliğini ekle.
 *
 * @param string $tag    Oluşturulan <script> HTML etiketi.
 * @param string $handle Script handle adı.
 * @return string Güncellenmiş <script> etiketi.
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
