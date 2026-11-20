<?php
/**
 * Tests for uninstall cleanup.
 *
 * @package Hipcall
 */

/**
 * @group hipcall
 * @group uninstall
 */
class Tests_Uninstall extends WP_UnitTestCase {

	/**
	 * @coversNothing
	 */
	public function test_uninstall_should_delete_all_plugin_options() {
		$options = array( 'hipcall_region', 'hipcall_widget_id', 'hipcall_secret_key', 'hipcall_tracker_id' );
		foreach ( $options as $option ) {
			update_option( $option, 'value' );
		}

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'hipcall/hipcall.php' );
		}
		require HIPCALL_TESTS_PLUGIN_DIR . '/uninstall.php';

		foreach ( $options as $option ) {
			$this->assertFalse( get_option( $option ), "{$option} should be deleted." );
		}
	}
}
