<?php
/**
 * Clean up plugin options when uninstalled from WordPress.
 *
 * @package Hipcall
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'hipcall_region' );
delete_option( 'hipcall_widget_id' );
delete_option( 'hipcall_secret_key' );
delete_option( 'hipcall_tracker_id' );
