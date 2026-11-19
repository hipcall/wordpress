<?php
/**
 * Eklenti WordPress'ten tamamen silindiğinde (Uninstall) veritabanındaki ayarları temizler.
 *
 * @package Hipcall
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'hipcall_widget_id' );
delete_option( 'hipcall_secret_key' );
