<?php
/**
 * Frontend script enqueuing and HMAC-SHA256 user identity verification.
 *
 * @package Hipcall
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue frontend scripts (h.js Tracker in <head> + Webchat Widget in Footer & HMAC-SHA256).
 */
function hipcall_enqueue_scripts() {
	$base_url   = hipcall_get_base_url();
	$tracker_id = trim( (string) get_option( 'hipcall_tracker_id', '' ) );
	$widget_id  = trim( (string) get_option( 'hipcall_widget_id', '' ) );

	// 1. Marketing Tracker (h.js) - Enqueued asynchronously in <head> ($ver = null so no ?ver= is appended).
	if ( '' !== $tracker_id && hipcall_is_valid_uuid( $tracker_id ) ) {
		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion,WordPress.WP.EnqueuedResourceParameters.NotInFooter -- External Hipcall endpoint manages its own caching; h.js must load in <head>.
		wp_enqueue_script(
			'hipcall-tracker',
			esc_url( $base_url . '/track/v1/h.js' ),
			array(),
			null,
			array(
				'in_footer' => false,
				'strategy'  => 'async',
			)
		);
		// phpcs:enable
	}

	// 2. LiveChat (Webchat Widget) - Enqueued asynchronously in footer.
	if ( '' === $widget_id || ! hipcall_is_valid_uuid( $widget_id ) ) {
		return;
	}

	$secret_key = trim( (string) get_option( 'hipcall_secret_key', '' ) );
	$widget_url = $base_url . '/widget/' . rawurlencode( $widget_id );
	$user_data  = null;

	if ( '' !== $secret_key && is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$user_email   = isset( $current_user->user_email ) ? trim( (string) $current_user->user_email ) : '';
		$user_name    = ! empty( $current_user->display_name ) ? (string) $current_user->display_name : (string) $current_user->user_login;

		if ( '' !== $user_email ) {
			$user_data = array(
				'email'     => $user_email,
				'name'      => $user_name,
				'user_hash' => strtolower( hash_hmac( 'sha256', $user_email, $secret_key, false ) ),
			);
		}
	}

	// Enqueue local initialization & session-isolation script in footer before the widget script.
	wp_enqueue_script(
		'hipcall-widget-init',
		esc_url( HIPCALL_PLUGIN_URL . 'assets/js/hipcall-widget.js' ),
		array(),
		HIPCALL_VERSION,
		array(
			'in_footer' => true,
		)
	);

	$config_json = wp_json_encode(
		array(
			'widgetId' => $widget_id,
			'user'     => $user_data,
		)
	);

	wp_add_inline_script(
		'hipcall-widget-init',
		'window.hipcallWp = ' . $config_json . ';',
		'before'
	);

	// Enqueue external Hipcall widget script ($ver = null so no ?ver= is appended).
	// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion -- External Hipcall endpoint manages its own caching.
	wp_enqueue_script(
		'hipcall-widget',
		esc_url( $widget_url ),
		array( 'hipcall-widget-init' ),
		null,
		array(
			'in_footer' => true,
			'strategy'  => 'async',
		)
	);
	// phpcs:enable
}
add_action( 'wp_enqueue_scripts', 'hipcall_enqueue_scripts' );

/**
 * Add data-hipcall-id attribute to the Marketing Tracker (h.js) script tag.
 *
 * @param string $tag    Generated <script> HTML tag.
 * @param string $handle Script handle name.
 * @return string Modified <script> tag.
 */
function hipcall_script_loader_tag( $tag, $handle ) {
	if ( 'hipcall-tracker' === $handle ) {
		$tracker_id = trim( (string) get_option( 'hipcall_tracker_id', '' ) );
		if ( '' !== $tracker_id && strpos( $tag, 'data-hipcall-id=' ) === false ) {
			$tag = str_replace( ' src=', ' data-hipcall-id="' . esc_attr( $tracker_id ) . '" src=', $tag );
		}
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'hipcall_script_loader_tag', 10, 2 );
