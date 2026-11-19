<?php
/**
 * Plugin Name:       Hipcall
 * Plugin URI:        https://github.com/hipcall/wordpress
 * Description:       Hipcall iletişim araçlarını WordPress sitenize kolayca entegre eder ve giriş yapmış üyeler için HMAC-SHA256 tabanlı kimlik doğrulaması (Verified User) sağlar.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Hipcall
 * Author URI:        https://www.hipcall.com/tr/
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       hipcall
 */

// Doğrudan dosya erişimini engelle
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 1. Yönetim Paneli Menüsü: "Ayarlar > Hipcall" alt menüsünü ekle
 */
function hipcall_add_admin_menu() {
	add_options_page(
		'Hipcall Ayarları',          // Sayfa başlığı (<title>)
		'Hipcall',                   // Menü başlığı
		'manage_options',            // Gerekli yetki
		'hipcall',                   // Menü kısa adı (slug)
		'hipcall_settings_page_html' // İçeriği basacak fonksiyon
	);
}
add_action( 'admin_menu', 'hipcall_add_admin_menu' );

/**
 * Eklentiler sayfasında hızlı "Ayarlar" bağlantısı ekle
 */
function hipcall_action_links( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=hipcall' ) ) . '">Ayarlar</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'hipcall_action_links' );

/**
 * 2. Admin Ayarlar Sayfası Görünümü ve Kayıt İşlemi
 */
function hipcall_settings_page_html() {
	// Yetki kontrolü
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Form gönderildiyse Nonce ve Yetki doğrulaması yaparak kaydet
	if ( isset( $_POST['hipcall_submit'] ) ) {
		check_admin_referer( 'hipcall_save_settings', 'hipcall_nonce' );

		$raw_widget_id  = isset( $_POST['hipcall_widget_id'] ) ? wp_unslash( $_POST['hipcall_widget_id'] ) : '';
		$raw_secret_key = isset( $_POST['hipcall_secret_key'] ) ? wp_unslash( $_POST['hipcall_secret_key'] ) : '';

		// Kullanıcı yanlışlıkla tam URL yapıştırırsa sadece Widget ID kısmını ayıkla
		$clean_widget_id = sanitize_text_field( $raw_widget_id );
		if ( strpos( $clean_widget_id, '/widget/' ) !== false ) {
			$parts           = explode( '/widget/', $clean_widget_id );
			$clean_widget_id = trim( end( $parts ), " \t\n\r\0\x0B/'" );
		}

		$clean_secret_key = sanitize_text_field( $raw_secret_key );

		update_option( 'hipcall_widget_id', $clean_widget_id );
		update_option( 'hipcall_secret_key', $clean_secret_key );

		add_settings_error(
			'hipcall_messages',
			'hipcall_message',
			'Ayarlar başarıyla kaydedildi.',
			'updated'
		);
	}

	// Mevcut değerleri veritabanından çek
	$widget_id  = get_option( 'hipcall_widget_id', '' );
	$secret_key = get_option( 'hipcall_secret_key', '' );

	settings_errors( 'hipcall_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p>Hipcall bileşenlerini sitenizde aktif etmek için aşağıdaki bilgileri doldurun.</p>

		<form method="post" action="">
			<?php wp_nonce_field( 'hipcall_save_settings', 'hipcall_nonce' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="hipcall_widget_id">Widget ID <span style="color: #d63638;">*</span></label>
						</th>
						<td>
							<input
								name="hipcall_widget_id"
								type="text"
								id="hipcall_widget_id"
								value="<?php echo esc_attr( $widget_id ); ?>"
								class="regular-text"
								placeholder="Örn: 8e32e0b8-fd33-450b-a5b0-b3359e1384e1"
								required
							/>
							<p class="description">
								Hipcall panelinizdeki widget kurulum kodunun sonunda yer alan benzersiz ID'yi girin.<br />
								<code>https://use.hipcall.com.tr/widget/<strong>WIDGET_ID</strong></code>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="hipcall_secret_key">Secret Key (Gizli Anahtar)</label>
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
								<strong>İsteğe bağlı:</strong> Giriş yapmış WordPress üyelerinin Hipcall'da <em>"Verified (Doğrulanmış)"</em> olarak görünmesi için Hipcall panelindeki gizli anahtarınızı girin.
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( 'Değişiklikleri Kaydet', 'primary', 'hipcall_submit' ); ?>
		</form>
	</div>
	<?php
}

/**
 * 3. Frontend JavaScript Enjeksiyonu ve HMAC-SHA256 Kullanıcı Doğrulaması (wp_footer)
 */
function hipcall_render_widget() {
	$widget_id = trim( (string) get_option( 'hipcall_widget_id', '' ) );

	// Widget ID girilmemişse hiçbir şey basma
	if ( empty( $widget_id ) ) {
		return;
	}

	$secret_key  = trim( (string) get_option( 'hipcall_secret_key', '' ) );
	$widget_url  = 'https://use.hipcall.com.tr/widget/' . rawurlencode( $widget_id );
	$is_verified = false;
	$user_email  = '';
	$user_name   = '';
	$user_hash   = '';

	// Secret Key girilmişse VE ziyaretçi WordPress'e giriş yapmışsa Verified (HMAC-SHA256) bilgisini hazırla
	if ( ! empty( $secret_key ) && is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$user_email   = isset( $current_user->user_email ) ? trim( $current_user->user_email ) : '';
		$user_name    = ! empty( $current_user->display_name ) ? $current_user->display_name : $current_user->user_login;

		if ( ! empty( $user_email ) ) {
			// E-posta adresini Secret Key ile HMAC-SHA256 algoritmasıyla şifrele (küçük harfli hex)
			$user_hash   = strtolower( hash_hmac( 'sha256', $user_email, $secret_key, false ) );
			$is_verified = true;
		}
	}

	// Widget init() çalışmadan hemen önce oturum verisini senkronize et
	if ( $is_verified ) :
		?>
	<script type="text/javascript">
		(function() {
			try {
				var inboxId = '<?php echo esc_js( $widget_id ); ?>';
				var storageKey = 'hipcall_session_' + inboxId;
				var existing = JSON.parse(localStorage.getItem(storageKey) || '{}');
				var currentEmail = '<?php echo esc_js( $user_email ); ?>';
				var previousEmail = existing.visitorData && existing.visitorData.email ? existing.visitorData.email : null;
				var isSameUser = !previousEmail || previousEmail === currentEmail;
				var sessionId = (isSameUser && existing.sessionId)
					? existing.sessionId
					: ('hc_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now());
				existing.sessionId = sessionId;
				existing.timestamp = isSameUser && existing.timestamp ? existing.timestamp : Date.now();
				existing.inboxId = inboxId;
				existing.visitorData = {
					name: '<?php echo esc_js( $user_name ); ?>',
					email: currentEmail
				};
				existing.visitorSources = { name: 'website', email: 'website' };
				existing.userHash = '<?php echo esc_js( $user_hash ); ?>';
				localStorage.setItem(storageKey, JSON.stringify(existing));
			} catch (e) {}
		})();
	</script>
		<?php
	else :
		// Kullanıcı çıkış yaptıysa önceki doğrulanmış oturum bilgisini temizle
		?>
	<script type="text/javascript">
		(function() {
			try {
				var storageKey = 'hipcall_session_<?php echo esc_js( $widget_id ); ?>';
				var existing = JSON.parse(localStorage.getItem(storageKey) || '{}');
				if (existing && existing.userHash) {
					localStorage.removeItem(storageKey);
				}
			} catch (e) {}
		})();
	</script>
		<?php
	endif;
	?>
	<script type="text/javascript">
		(function(w,d,s,o,f,js,fjs){
			w['HipcallWidget']=o;w[o]=w[o]||function(){(w[o].q=w[o].q||[]).push(arguments)};
			js=d.createElement(s),fjs=d.getElementsByTagName(s)[0];
			js.id=o;js.src=f;js.async=1;fjs.parentNode.insertBefore(js,fjs);
		})(window,document,'script','hc','<?php echo esc_js( $widget_url ); ?>');
	</script>
	<?php if ( $is_verified ) : ?>
	<script type="text/javascript">
		hc('user', {
			email: '<?php echo esc_js( $user_email ); ?>',
			name: '<?php echo esc_js( $user_name ); ?>',
			user_hash: '<?php echo esc_js( $user_hash ); ?>'
		});
	</script>
	<?php endif; ?>
	<?php
}
add_action( 'wp_footer', 'hipcall_render_widget', 20 );
