<?php
/**
 * Tests for the settings sanitize callbacks and their registration.
 *
 * @package Hipcall
 */

/**
 * @group hipcall
 * @group settings
 */
class Tests_Settings_Sanitize extends WP_UnitTestCase {

	const WIDGET_ID  = '8e32e0b8-fd33-450b-a5b0-b3359e1384e1';
	const TRACKER_ID = 'e61f8fe3-3eea-44d0-bd1f-416c143c710c';

	/**
	 * Original $_POST, restored after each test.
	 *
	 * @var array
	 */
	private $original_post;

	public function set_up() {
		parent::set_up();

		$this->original_post = $_POST;
		$GLOBALS['wp_settings_errors'] = array();
	}

	public function tear_down() {
		$_POST = $this->original_post;
		$GLOBALS['wp_settings_errors'] = array();

		parent::tear_down();
	}

	/**
	 * Codes of the settings errors added during the test.
	 *
	 * @return string[]
	 */
	private function get_settings_error_codes() {
		return wp_list_pluck( $GLOBALS['wp_settings_errors'], 'code' );
	}

	/*
	 * Region.
	 */

	/**
	 * @covers ::hipcall_sanitize_region
	 */
	public function test_sanitize_region_should_keep_supported_regions() {
		$this->assertSame( 'tr', hipcall_sanitize_region( 'tr' ), 'Türkiye region should be kept.' );
		$this->assertSame( 'global', hipcall_sanitize_region( 'global' ), 'Global region should be kept.' );
	}

	/**
	 * @covers ::hipcall_sanitize_region
	 */
	public function test_sanitize_region_should_fall_back_to_default_for_unknown_values() {
		add_filter(
			'pre_determine_locale',
			static function () {
				return 'en_US';
			}
		);

		$this->assertSame( 'global', hipcall_sanitize_region( 'eu' ) );
	}

	/*
	 * Widget ID.
	 */

	/**
	 * Regression test for review item 1.2: sanitize_text_field() used to strip the pasted <script> tag
	 * and silently save an empty Widget ID.
	 *
	 * @covers ::hipcall_sanitize_widget_id
	 */
	public function test_sanitize_widget_id_should_extract_uuid_from_full_portal_snippet() {
		$snippet = '<script async src="https://use.hipcall.com.tr/widget/' . strtoupper( self::WIDGET_ID ) . '"></script>';

		$this->assertSame( self::WIDGET_ID, hipcall_sanitize_widget_id( $snippet ) );
		$this->assertEmpty( $this->get_settings_error_codes(), 'A valid snippet should not add a settings error.' );
	}

	/**
	 * @covers ::hipcall_sanitize_widget_id
	 */
	public function test_sanitize_widget_id_should_allow_clearing_the_field() {
		update_option( 'hipcall_widget_id', self::WIDGET_ID );

		$this->assertSame( '', hipcall_sanitize_widget_id( '   ' ) );
	}

	/**
	 * @covers ::hipcall_sanitize_widget_id
	 */
	public function test_sanitize_widget_id_should_keep_previous_value_and_show_error_for_invalid_input() {
		update_option( 'hipcall_widget_id', self::WIDGET_ID );

		$this->assertSame( self::WIDGET_ID, hipcall_sanitize_widget_id( 'not a widget id' ), 'Previous value should be kept.' );
		$this->assertContains( 'hipcall_invalid_widget_id', $this->get_settings_error_codes(), 'An error should be shown.' );
	}

	/*
	 * Tracker ID.
	 */

	/**
	 * @covers ::hipcall_sanitize_tracker_id
	 */
	public function test_sanitize_tracker_id_should_extract_uuid_from_tracker_script_tag() {
		$tag = '<script async src="https://use.hipcall.com/track/v1/h.js" data-hipcall-id="' . self::TRACKER_ID . '"></script>';

		$this->assertSame( self::TRACKER_ID, hipcall_sanitize_tracker_id( $tag ) );
	}

	/**
	 * @covers ::hipcall_sanitize_tracker_id
	 */
	public function test_sanitize_tracker_id_should_keep_previous_value_and_show_error_for_invalid_input() {
		update_option( 'hipcall_tracker_id', self::TRACKER_ID );

		$this->assertSame( self::TRACKER_ID, hipcall_sanitize_tracker_id( 'abc' ), 'Previous value should be kept.' );
		$this->assertContains( 'hipcall_invalid_tracker_id', $this->get_settings_error_codes(), 'An error should be shown.' );
	}

	/*
	 * Identity verification secret.
	 */

	/**
	 * @covers ::hipcall_sanitize_secret_key
	 */
	public function test_sanitize_secret_should_save_a_new_trimmed_value() {
		$this->assertSame( 'whsec_new', hipcall_sanitize_secret_key( "  whsec_new \n" ) );
	}

	/**
	 * The secret is never printed back into the form, so an empty submission must keep it.
	 *
	 * @covers ::hipcall_sanitize_secret_key
	 */
	public function test_sanitize_secret_should_keep_existing_secret_when_submitted_empty() {
		update_option( 'hipcall_secret_key', 'whsec_existing' );

		$this->assertSame( 'whsec_existing', hipcall_sanitize_secret_key( '' ) );
	}

	/**
	 * @covers ::hipcall_sanitize_secret_key
	 */
	public function test_sanitize_secret_should_remove_secret_when_clear_checkbox_is_checked() {
		update_option( 'hipcall_secret_key', 'whsec_existing' );
		$_POST['hipcall_clear_secret'] = '1';

		$this->assertSame( '', hipcall_sanitize_secret_key( 'whsec_ignored' ) );
	}

	/*
	 * Registration (end-to-end through update_option()).
	 */

	/**
	 * @covers ::hipcall_register_settings
	 */
	public function test_saving_full_widget_snippet_through_settings_api_should_store_the_uuid() {
		hipcall_register_settings();

		update_option( 'hipcall_widget_id', '<script async src="https://use.hipcall.com/widget/' . self::WIDGET_ID . '"></script>' );

		$this->assertSame( self::WIDGET_ID, get_option( 'hipcall_widget_id' ) );
	}

	/**
	 * Regression test for review item 1.4: saving the Widget/Tracker ID used to overwrite the region
	 * the administrator had just selected.
	 *
	 * @covers ::hipcall_register_settings
	 */
	public function test_saving_ids_should_not_change_the_selected_region() {
		hipcall_register_settings();
		update_option( 'hipcall_region', 'global' );

		update_option( 'hipcall_widget_id', 'https://use.hipcall.com.tr/widget/' . self::WIDGET_ID );
		update_option( 'hipcall_tracker_id', self::TRACKER_ID );

		$this->assertSame( 'global', get_option( 'hipcall_region' ) );
	}
}
