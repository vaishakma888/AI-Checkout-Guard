<?php
// Abort if not called by WordPress during the plugin uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * 1) Remove plugin options (single + multisite).
 */
$option_keys = array(
	'ai_checkout_guard_settings',
	'ai_checkout_guard_api_key',
	'ai_checkout_guard_risk_threshold',
	'ai_checkout_guard_cod_control',
	'ai_checkout_guard_version',
);

foreach ( $option_keys as $key ) {
	delete_option( $key );           // Single-site option cleanup.
	delete_site_option( $key );      // Network (multisite) option cleanup.
}

/**
 * 2) Remove plugin-created post meta across all posts.
 *    Use core helper to delete by meta key globally.
 */
if ( function_exists( 'delete_post_meta_by_key' ) ) {
	delete_post_meta_by_key( '_ai_checkout_guard_risk_score' );
	delete_post_meta_by_key( '_ai_checkout_guard_prediction' );
}

/**
 * 3) Remove plugin-created user meta across all users.
 *    delete_metadata with $delete_all = true clears for all objects of type 'user'.
 */
if ( function_exists( 'delete_metadata' ) ) {
	delete_metadata( 'user', 0, '_ai_checkout_guard_risk_profile', '', true );
	delete_metadata( 'user', 0, '_ai_checkout_guard_previous_orders', '', true );
}

/**
 * 4) Remove known plugin transients (add/remove keys as your plugin uses them).
 *    These calls are safe even if the transient does not exist.
 */
$transients = array(
	'ai_checkout_guard_cache',
	'ai_checkout_guard_api_status',
);

foreach ( $transients as $t ) {
	delete_transient( $t );
}
