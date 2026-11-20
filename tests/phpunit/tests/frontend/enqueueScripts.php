<?php
/**
 * Tests for frontend script enqueuing and HMAC-SHA256 identity verification.
 *
 * @package Hipcall
 */

/**
 * @group hipcall
 * @group frontend
 */
class Tests_Frontend_EnqueueScripts extends WP_UnitTestCase {

	const WIDGET_ID  = '8e32e0b8-fd33-450b-a5b0-b3359e1384e1';
	const TRACKER_ID = 'e61f8fe3-3eea-44d0-bd1f-416c143c710c';
	const SECRET     = 'whsec_test_secret';

	public function set_up() {
		parent::set_up();

		// Start every test with a fresh script registry.
		$GLOBALS['wp_scripts'] = null;
		update_option( 'hipcall_region', 'global' );
	}

	public function tear_down() {
		$GLOBALS['wp_scripts'] = null;

		parent::tear_down();
	}

	/**
	 * Inline JavaScript printed before the init script (WordPress keeps an empty first entry).
	 *
	 * @return string
	 */
	private function get_inline_before() {
		return implode( "\n", array_filter( (array) wp_scripts()->get_data( 'hipcall-widget-init', 'before' ) ) );
	}

	/**
	 * Decode the window.hipcallWp configuration passed to the init script.
	 *
	 * @return array|null Decoded configuration, or null if the inline script is missing.
	 */
	private function get_widget_config() {
		$before = wp_scripts()->get_data( 'hipcall-widget-init', 'before' );
		if ( empty( $before ) ) {
			return null;
		}

		$inline = $this->get_inline_before();
		$this->assertMatchesRegularExpression( '/^window\.hipcallWp = .*;$/s', $inline, 'Inline config should assign window.hipcallWp.' );

		return json_decode( substr( $inline, strlen( 'window.hipcallWp = ' ), -1 ), true );
	}

	/**
	 * @covers ::hipcall_enqueue_scripts
	 */
	public function test_nothing_should_be_enqueued_when_plugin_is_not_configured() {
		hipcall_enqueue_scripts();

		$this->assertFalse( wp_script_is( 'hipcall-tracker', 'enqueued' ), 'Tracker should not be enqueued.' );
		$this->assertFalse( wp_script_is( 'hipcall-widget-init', 'enqueued' ), 'Widget init should not be enqueued.' );
		$this->assertFalse( wp_script_is( 'hipcall-widget', 'enqueued' ), 'Widget should not be enqueued.' );
	}

	/**
	 * @covers ::hipcall_enqueue_scripts
	 */
	public function test_nothing_should_be_enqueued_for_invalid_ids_stored_in_the_database() {
		update_option( 'hipcall_widget_id', 'not-a-uuid' );
		update_option( 'hipcall_tracker_id', 'not-a-uuid' );

		hipcall_enqueue_scripts();

		$this->assertFalse( wp_script_is( 'hipcall-tracker', 'enqueued' ), 'Tracker should not be enqueued.' );
		$this->assertFalse( wp_script_is( 'hipcall-widget', 'enqueued' ), 'Widget should not be enqueued.' );
	}

	/**
	 * @covers ::hipcall_enqueue_scripts
	 */
	public function test_tracker_should_load_async_in_head_from_the_selected_region() {
		update_option( 'hipcall_region', 'tr' );
		update_option( 'hipcall_tracker_id', self::TRACKER_ID );

		hipcall_enqueue_scripts();

		$this->assertTrue( wp_script_is( 'hipcall-tracker', 'enqueued' ), 'Tracker should be enqueued.' );
		$script = wp_scripts()->registered['hipcall-tracker'];
		$this->assertSame( 'https://use.hipcall.com.tr/track/v1/h.js', $script->src, 'Tracker should load from the Türkiye region.' );
		$this->assertNull( $script->ver, 'External script should not get a ?ver= parameter.' );
		$this->assertFalse( wp_scripts()->get_data( 'hipcall-tracker', 'group' ), 'Tracker should be printed in <head>.' );
		$this->assertSame( 'async', wp_scripts()->get_data( 'hipcall-tracker', 'strategy' ), 'Tracker should load async.' );
		$this->assertFalse( wp_script_is( 'hipcall-widget', 'enqueued' ), 'Widget should not be enqueued without a Widget ID.' );
	}

	/**
	 * @covers ::hipcall_enqueue_scripts
	 */
	public function test_widget_should_load_after_init_script_in_footer() {
		update_option( 'hipcall_widget_id', self::WIDGET_ID );

		hipcall_enqueue_scripts();

		$this->assertTrue( wp_script_is( 'hipcall-widget-init', 'enqueued' ), 'Init script should be enqueued.' );
		$this->assertTrue( wp_script_is( 'hipcall-widget', 'enqueued' ), 'Widget should be enqueued.' );

		$widget = wp_scripts()->registered['hipcall-widget'];
		$this->assertSame( 'https://use.hipcall.com/widget/' . self::WIDGET_ID, $widget->src, 'Widget URL should contain the Widget ID.' );
		$this->assertSame( array( 'hipcall-widget-init' ), $widget->deps, 'Widget should depend on the init script.' );
		$this->assertNull( $widget->ver, 'External script should not get a ?ver= parameter.' );
		$this->assertSame( 1, wp_scripts()->get_data( 'hipcall-widget', 'group' ), 'Widget should be printed in the footer.' );
		$this->assertSame( 1, wp_scripts()->get_data( 'hipcall-widget-init', 'group' ), 'Init script should be printed in the footer.' );
	}

	/**
	 * @covers ::hipcall_enqueue_scripts
	 */
	public function test_visitor_should_not_be_identified_when_logged_out() {
		update_option( 'hipcall_widget_id', self::WIDGET_ID );
		update_option( 'hipcall_secret_key', self::SECRET );

		hipcall_enqueue_scripts();

		$config = $this->get_widget_config();
		$this->assertSame( self::WIDGET_ID, $config['widgetId'], 'Widget ID should be passed to the init script.' );
		$this->assertNull( $config['user'], 'Logged-out visitors must not be identified.' );
	}

	/**
	 * @covers ::hipcall_enqueue_scripts
	 */
	public function test_logged_in_user_should_not_be_identified_without_a_secret() {
		update_option( 'hipcall_widget_id', self::WIDGET_ID );
		wp_set_current_user( self::factory()->user->create() );

		hipcall_enqueue_scripts();

		$this->assertNull( $this->get_widget_config()['user'] );
	}

	/**
	 * @covers ::hipcall_enqueue_scripts
	 */
	public function test_logged_in_user_should_be_identified_with_server_side_hmac() {
		update_option( 'hipcall_widget_id', self::WIDGET_ID );
		update_option( 'hipcall_secret_key', self::SECRET );
		$user_id = self::factory()->user->create(
			array(
				'user_email'   => 'ayse@example.com',
				'display_name' => 'Ayşe Yılmaz',
			)
		);
		wp_set_current_user( $user_id );

		hipcall_enqueue_scripts();

		$user = $this->get_widget_config()['user'];
		$this->assertSame( 'ayse@example.com', $user['email'], 'Email should be passed.' );
		$this->assertSame( 'Ayşe Yılmaz', $user['name'], 'Display name should be passed.' );
		$this->assertSame(
			hash_hmac( 'sha256', 'ayse@example.com', self::SECRET ),
			$user['user_hash'],
			'user_hash should be the lowercase hex HMAC-SHA256 of the email.'
		);
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $user['user_hash'], 'user_hash should be 64 lowercase hex characters.' );
	}

	/**
	 * Known HMAC-SHA256 vector so a change in the hashing input or format is caught.
	 *
	 * @covers ::hipcall_enqueue_scripts
	 */
	public function test_user_hash_should_match_known_hmac_vector() {
		update_option( 'hipcall_widget_id', self::WIDGET_ID );
		update_option( 'hipcall_secret_key', 'key' );
		wp_set_current_user( self::factory()->user->create( array( 'user_email' => 'user@example.com' ) ) );

		hipcall_enqueue_scripts();

		$this->assertSame(
			'd7ef88ef7a97a643eb7b10c4df55c82decbcb8d9c16c1229f370b24b680ea4fd',
			$this->get_widget_config()['user']['user_hash']
		);
	}

	/**
	 * @covers ::hipcall_enqueue_scripts
	 */
	public function test_secret_should_never_be_sent_to_the_browser() {
		update_option( 'hipcall_widget_id', self::WIDGET_ID );
		update_option( 'hipcall_secret_key', self::SECRET );
		wp_set_current_user( self::factory()->user->create() );

		hipcall_enqueue_scripts();

		$inline = $this->get_inline_before();
		$this->assertStringNotContainsString( self::SECRET, $inline );
	}

	/**
	 * A display name controlled by the user must not be able to break out of the inline <script>.
	 *
	 * @covers ::hipcall_enqueue_scripts
	 */
	public function test_display_name_should_not_break_out_of_inline_script() {
		update_option( 'hipcall_widget_id', self::WIDGET_ID );
		update_option( 'hipcall_secret_key', self::SECRET );
		$user_id = self::factory()->user->create();
		// Write directly to the database, bypassing the sanitization wp_update_user() would apply.
		global $wpdb;
		$wpdb->update( $wpdb->users, array( 'display_name' => '</script><script>alert(1)</script>' ), array( 'ID' => $user_id ) );
		clean_user_cache( $user_id );
		wp_set_current_user( $user_id );

		hipcall_enqueue_scripts();

		$inline = $this->get_inline_before();
		$this->assertStringNotContainsString( '</script>', $inline, 'Closing script tag must be escaped.' );
		$this->assertSame( '</script><script>alert(1)</script>', $this->get_widget_config()['user']['name'], 'Name should survive JSON round-trip unchanged.' );
	}

	/**
	 * @covers ::hipcall_enqueue_scripts
	 */
	public function test_enqueue_should_be_hooked_to_wp_enqueue_scripts() {
		$this->assertSame( 10, has_action( 'wp_enqueue_scripts', 'hipcall_enqueue_scripts' ) );
	}
}
