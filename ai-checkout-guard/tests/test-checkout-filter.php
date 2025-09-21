<?php
/**
 * test-checkout-filter.php
 *
 * Standalone test script for AI_Checkout_Guard_Checkout_Filter.
 *
 * Usage (CLI):
 *   php test-checkout-filter.php
 *
 * Usage (browser):
 *   Place this file in your plugin root and visit https://your-site.com/wp-content/plugins/ai-checkout-guard/test-checkout-filter.php
 */

if ( PHP_SAPI !== 'cli' ) {
    // Browser mode: load WordPress.
    require dirname( __FILE__ ) . '/../../../../wp-load.php';
} else {
    // CLI mode: find wp-load relative to this script.
    $root = dirname( __FILE__ );
    while ( ! file_exists( "$root/wp-load.php" ) && strlen( $root ) > 3 ) {
        $root = dirname( $root );
    }
    if ( file_exists( "$root/wp-load.php" ) ) {
        require "$root/wp-load.php";
    } else {
        fwrite( STDERR, "Error: Could not locate wp-load.php\n" );
        exit(1);
    }
}

if ( ! class_exists( 'AI_Checkout_Guard_Checkout_Filter' ) ) {
    require_once dirname( __FILE__ ) . '/public/class-checkout-filter.php';
}

// Ensure WooCommerce cart and customer are available.
if ( ! function_exists( 'WC' ) ) {
    fwrite( STDERR, "Error: WooCommerce not active.\n" );
    exit(1);
}

// Create dummy gateways array.
$gateways = array(
    'paypal' => (object) array( 'id' => 'paypal', 'title' => 'PayPal', 'description' => '' ),
    'cod'    => (object) array( 'id' => 'cod',    'title' => 'Cash on Delivery', 'description' => '' ),
);

// Instantiate the filter class.
$filter = new AI_Checkout_Guard_Checkout_Filter(
    plugin_dir_url( __FILE__ ),
    plugin_dir_path( __FILE__ ),
    'ai_checkout_guard_settings'
);

// Mock cart and customer data to simulate medium/high risk.
// For a real test, set WC()->customer and WC()->cart appropriately.

WC()->customer->set_billing_email( 'test@example.com' );
WC()->customer->set_billing_phone( '+1234567890' );
WC()->customer->set_billing_first_name( 'Test' );
WC()->customer->set_billing_last_name( 'User' );
WC()->customer->set_billing_address_1( '123 Test St' );
WC()->customer->set_billing_postcode( '12345' );

WC()->cart->empty_cart();
WC()->cart->add_to_cart( 1, 1 ); // Requires a product with ID 1 in the store for a real test.

// Call the filter method.
$filtered = $filter->filter_gateways( $gateways );

// Output result.
header( 'Content-Type: application/json; charset=utf-8' );
echo wp_json_encode( array_keys( $filtered ), JSON_PRETTY_PRINT );
