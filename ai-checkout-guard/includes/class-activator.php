<?php
/**
 * Plugin activation and deactivation logic for AI Checkout Guard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Checkout_Guard_Activator {

	/**
	 * Runs on plugin activation.
	 *
	 * - Creates default settings if not present.
	 * - Registers custom database tables or taxonomies if any.
	 * - Flushes rewrite rules to ensure REST routes work immediately.
	 */
	public static function activate() {
		$option_key = 'ai_checkout_guard_settings';

		// If settings do not exist, initialize with defaults.
		if ( false === get_option( $option_key ) ) {
			$defaults = array(
				'api_url'        => '',
				'api_key'        => '',
				'timeout'        => 3,
				'cache_ttl'      => 60,
				'low_threshold'  => 30,
				'high_threshold' => 70,
				'cod_action'     => 'verify',
				'webhook_url'    => '',
				'webhook_key'    => '',
			);
			add_option( $option_key, $defaults );
		}

		// Flush rewrite rules so our REST API routes are recognized immediately.
		flush_rewrite_rules();
	}

	/**
	 * Runs on plugin deactivation.
	 *
	 * - Clears scheduled hooks or cron jobs if any.
	 * - Flushes rewrite rules to remove plugin-specific routes.
	 */
	public static function deactivate() {
		// Example: if the plugin had scheduled events, remove them here.
		// wp_clear_scheduled_hook( 'ai_checkout_guard_cron_event' );

		// Flush rewrite rules to clean up REST routes.
		flush_rewrite_rules();
	}
}
