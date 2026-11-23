<?php
/**
 * Helper functions for region selection and UUID validation.
 *
 * @package Hipcall
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the default region based on the WordPress site locale ('tr' for tr_TR, 'global' otherwise).
 *
 * @return string 'tr' or 'global'.
 */
function hipcall_get_default_region() {
	return ( strpos( determine_locale(), 'tr' ) === 0 ) ? 'tr' : 'global';
}

/**
 * Get the active Hipcall base domain according to the selected region.
 *
 * @return string 'https://use.hipcall.com.tr' or 'https://use.hipcall.com'.
 */
function hipcall_get_base_url() {
	$region = get_option( 'hipcall_region', hipcall_get_default_region() );
	return ( 'tr' === $region ) ? 'https://use.hipcall.com.tr' : 'https://use.hipcall.com';
}

/**
 * Extract a UUID from a raw string (e.g., a bare UUID, a widget URL, or a full <script> snippet).
 *
 * @param string $raw Raw input string.
 * @return string Lowercase UUID if found, or empty string.
 */
function hipcall_extract_uuid( $raw ) {
	if ( preg_match( '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', (string) $raw, $matches ) ) {
		return strtolower( $matches[0] );
	}
	return '';
}

/**
 * Check whether a string is a valid UUID.
 *
 * @param string $value String to validate.
 * @return bool True if valid UUID.
 */
function hipcall_is_valid_uuid( $value ) {
	return (bool) preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string) $value );
}

/**
 * Check whether a string is a valid identity verification secret: 1-256 printable ASCII characters without whitespace.
 *
 * @param string $value Secret to validate.
 * @return bool True if valid.
 */
function hipcall_is_valid_secret( $value ) {
	return (bool) preg_match( '/^[\x21-\x7E]{1,256}$/', (string) $value );
}
