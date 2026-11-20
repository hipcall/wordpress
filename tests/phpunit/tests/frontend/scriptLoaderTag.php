<?php
/**
 * Tests for the data-hipcall-id attribute added to the tracker <script> tag.
 *
 * @package Hipcall
 */

/**
 * @group hipcall
 * @group frontend
 */
class Tests_Frontend_ScriptLoaderTag extends WP_UnitTestCase {

	const TRACKER_ID = 'e61f8fe3-3eea-44d0-bd1f-416c143c710c';
	const TAG        = '<script async src="https://use.hipcall.com/track/v1/h.js" id="hipcall-tracker-js"></script>';

	/**
	 * @covers ::hipcall_script_loader_tag
	 */
	public function test_tracker_tag_should_get_data_hipcall_id() {
		update_option( 'hipcall_tracker_id', self::TRACKER_ID );

		$tag = hipcall_script_loader_tag( self::TAG, 'hipcall-tracker' );

		$this->assertStringContainsString( 'data-hipcall-id="' . self::TRACKER_ID . '" src=', $tag );
	}

	/**
	 * @covers ::hipcall_script_loader_tag
	 */
	public function test_attribute_should_not_be_added_twice() {
		update_option( 'hipcall_tracker_id', self::TRACKER_ID );

		$tag = hipcall_script_loader_tag( hipcall_script_loader_tag( self::TAG, 'hipcall-tracker' ), 'hipcall-tracker' );

		$this->assertSame( 1, substr_count( $tag, 'data-hipcall-id=' ) );
	}

	/**
	 * @covers ::hipcall_script_loader_tag
	 */
	public function test_other_scripts_should_not_be_changed() {
		update_option( 'hipcall_tracker_id', self::TRACKER_ID );
		$other = '<script src="https://example.com/app.js" id="app-js"></script>';

		$this->assertSame( $other, hipcall_script_loader_tag( $other, 'app' ) );
	}

	/**
	 * @covers ::hipcall_script_loader_tag
	 */
	public function test_tracker_id_should_be_escaped_in_the_attribute() {
		// Simulate a malicious value already in the database.
		update_option( 'hipcall_tracker_id', '"><script>alert(1)</script>' );

		$tag = hipcall_script_loader_tag( self::TAG, 'hipcall-tracker' );

		$this->assertStringNotContainsString( '<script>alert(1)</script>', $tag );
	}

	/**
	 * End-to-end: the printed <head> markup contains the tracker with its ID.
	 *
	 * @covers ::hipcall_script_loader_tag
	 * @covers ::hipcall_enqueue_scripts
	 */
	public function test_printed_head_should_contain_tracker_with_id() {
		$GLOBALS['wp_scripts'] = null;
		update_option( 'hipcall_region', 'global' );
		update_option( 'hipcall_tracker_id', self::TRACKER_ID );
		hipcall_enqueue_scripts();

		$head = get_echo( 'wp_print_head_scripts' );
		$GLOBALS['wp_scripts'] = null;

		$this->assertMatchesRegularExpression(
			// Older WordPress versions print attributes with single quotes.
			'#<script[^>]*data-hipcall-id="' . self::TRACKER_ID . '"[^>]*src=[\'"]https://use\.hipcall\.com/track/v1/h\.js[\'"]#',
			$head
		);
	}
}
