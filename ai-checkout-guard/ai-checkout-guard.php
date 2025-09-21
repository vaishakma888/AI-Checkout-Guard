<?php
/**
 * Plugin Name:     AI Checkout Guard
 * Plugin URI:      https://example.com/ai-checkout-guard
 * Description:     Reduce RTO by risk-scoring orders and controlling COD.
 * Version:         1.0.1
 * Author:          Your Name
 * Text Domain:     ai-checkout-guard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
if ( ! defined( 'AI_CHECKOUT_GUARD_VERSION' ) ) {
    define( 'AI_CHECKOUT_GUARD_VERSION', '1.0.1' );
}
if ( ! defined( 'AI_CHECKOUT_GUARD_FILE' ) ) {
    define( 'AI_CHECKOUT_GUARD_FILE', __FILE__ );
}
if ( ! defined( 'AI_CHECKOUT_GUARD_DIR' ) ) {
    define( 'AI_CHECKOUT_GUARD_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'AI_CHECKOUT_GUARD_URL' ) ) {
    define( 'AI_CHECKOUT_GUARD_URL', plugin_dir_url( __FILE__ ) );
}

// Load main class
require_once AI_CHECKOUT_GUARD_DIR . 'includes/class-main.php';

/**
 * Bootstrap the plugin.
 */
function ai_checkout_guard_bootstrap() {
    $main = new AI_Checkout_Guard_Main(
        AI_CHECKOUT_GUARD_VERSION,
        AI_CHECKOUT_GUARD_FILE
    );
    $main->run();
}
add_action( 'plugins_loaded', 'ai_checkout_guard_bootstrap' );
