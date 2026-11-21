<?php
/**
 * Tests for the Settings > Hipcall admin page.
 *
 * @package Hipcall
 */

/**
 * @group hipcall
 * @group settings
 */
class Tests_Settings_SettingsPage extends WP_UnitTestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();

		require_once ABSPATH . 'wp-admin/includes/template.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	public function set_up() {
		parent::set_up();

		$GLOBALS['title'] = 'Hipcall Settings';
	}

	public function tear_down() {
		unset( $GLOBALS['title'] );

		parent::tear_down();
	}

	/**
	 * @covers ::hipcall_settings_page_html
	 */
	public function test_settings_page_should_never_print_the_saved_secret() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		update_option( 'hipcall_secret_key', 'whsec_do_not_leak_me' );

		$html = get_echo( 'hipcall_settings_page_html' );

		$this->assertStringContainsString( 'name="hipcall_secret_key"', $html, 'The secret field should be rendered.' );
		$this->assertStringNotContainsString( 'whsec_do_not_leak_me', $html, 'The saved secret must not appear in the page source.' );
		$this->assertStringContainsString( 'hipcall_clear_secret', $html, 'The remove-secret checkbox should be shown when a secret is saved.' );
	}

	/**
	 * The tracker <script> example did not follow the region selected in the form, so it was removed.
	 *
	 * @covers ::hipcall_settings_page_html
	 */
	public function test_settings_page_should_not_show_a_region_specific_tracker_snippet() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$html = get_echo( 'hipcall_settings_page_html' );

		$this->assertStringContainsString( 'name="hipcall_tracker_id"', $html, 'The Tracker ID field should be rendered.' );
		$this->assertStringNotContainsString( '/track/v1/h.js', $html, 'No hard-coded tracker URL should be shown.' );
	}

	/**
	 * @covers ::hipcall_settings_page_html
	 */
	public function test_settings_page_should_render_nothing_for_users_without_manage_options() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertSame( '', get_echo( 'hipcall_settings_page_html' ) );
	}

	/**
	 * @covers ::hipcall_settings_page_html
	 */
	public function test_settings_page_should_escape_saved_ids() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		// Bypass sanitization to simulate a malicious value already in the database.
		update_option( 'hipcall_widget_id', '"><script>alert(1)</script>' );

		$html = get_echo( 'hipcall_settings_page_html' );

		$this->assertStringNotContainsString( '<script>alert(1)</script>', $html );
	}
}
