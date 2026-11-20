<?php
/**
 * PHPUnit bootstrap file for the Hipcall plugin tests.
 *
 * Expects the WordPress test library installed by bin/install-wp-tests.sh.
 *
 * @package Hipcall
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php. Run bin/install-wp-tests.sh first." . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// The WordPress test library needs the Yoast PHPUnit Polyfills (installed with Composer).
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	$_polyfills = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_polyfills ? $_polyfills : dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills' );
}

require_once "{$_tests_dir}/includes/functions.php";

define( 'HIPCALL_TESTS_PLUGIN_DIR', dirname( __DIR__, 2 ) );

/**
 * Load the plugin the same way WordPress does for an active plugin.
 */
tests_add_filter(
	'muplugins_loaded',
	static function () {
		require HIPCALL_TESTS_PLUGIN_DIR . '/hipcall.php';
	}
);

require "{$_tests_dir}/includes/bootstrap.php";

// The settings screen code is only loaded in wp-admin; load it so its functions can be tested directly.
require_once HIPCALL_TESTS_PLUGIN_DIR . '/includes/class-hipcall-settings.php';
