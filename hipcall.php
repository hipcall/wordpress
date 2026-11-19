<?php
/**
 * Plugin Name:       Hipcall
 * Plugin URI:        https://github.com/hipcall/wordpress
 * Description:       Integrate the Hipcall LiveChat (Webchat) widget and Marketing Tracker (h.js) into WordPress with HMAC-SHA256 user verification.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Hipcall
 * Author URI:        https://www.hipcall.com/
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       hipcall
 * Domain Path:       /languages
 *
 * @package Hipcall
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HIPCALL_VERSION', '1.0.0' );
define( 'HIPCALL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HIPCALL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HIPCALL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once HIPCALL_PLUGIN_DIR . 'includes/helpers.php';
require_once HIPCALL_PLUGIN_DIR . 'includes/class-hipcall-frontend.php';

if ( is_admin() ) {
	require_once HIPCALL_PLUGIN_DIR . 'includes/class-hipcall-settings.php';
}
