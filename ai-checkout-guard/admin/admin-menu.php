<?php
/**
 * Admin Menu for AI Checkout Guard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Safe fallback for version if the constant isn't defined yet.
 * This prevents "Undefined constant" fatals during load order changes.
 */
$ai_guard_version = defined( 'AI_CHECKOUT_GUARD_VERSION' ) ? AI_CHECKOUT_GUARD_VERSION : '1.0.0';

/**
 * Register a top-level menu that points to the settings page.
 * Capability uses `manage_woocommerce` to match store admin roles.
 */
if ( ! function_exists( 'ai_checkout_guard_admin_menu' ) ) {
	function ai_checkout_guard_admin_menu() {
		add_menu_page(
			__( 'AI Checkout Guard', 'ai-checkout-guard' ),
			__( 'AI Checkout Guard', 'ai-checkout-guard' ),
			'manage_woocommerce',
			'ai-checkout-guard',
			'ai_checkout_guard_render_settings_page',
			'dashicons-shield',
			56
		);
	}
	add_action( 'admin_menu', 'ai_checkout_guard_admin_menu' );
}

/**
 * Render callback for the settings page.
 * If the settings class exists, let it output; otherwise include the partial.
 */
if ( ! function_exists( 'ai_checkout_guard_render_settings_page' ) ) {
	function ai_checkout_guard_render_settings_page() {
		if ( class_exists( 'AI_Checkout_Guard_Admin_Settings' ) ) {
			// Most setups will let the class handle the output.
			$settings = new AI_Checkout_Guard_Admin_Settings( 'ai_checkout_guard_settings', defined( 'AI_CHECKOUT_GUARD_VERSION' ) ? AI_CHECKOUT_GUARD_VERSION : '1.0.0' );
			if ( method_exists( $settings, 'render_page' ) ) {
				$settings->render_page();
				return;
			}
		}

		// Fallback: simple partial if render_page() isn't available.
		$partial = dirname( __FILE__ ) . '/partials/settings-display.php';
		if ( file_exists( $partial ) ) {
			include $partial;
		} else {
			echo '<div class="wrap"><h1>' . esc_html__( 'AI Checkout Guard', 'ai-checkout-guard' ) . '</h1><p>' . esc_html__( 'Settings page not available.', 'ai-checkout-guard' ) . '</p></div>';
		}
	}
}
