<?php
/**
 * test-risk-api.php
 *
 * Standalone test script for AI_Checkout_Guard_Risk_API.
 *
 * Usage (CLI):
 *   php test-risk-api.php
 *
 * Usage (browser):
 *   Place this file in your plugin root and visit https://your-site.com/wp-content/plugins/ai-checkout-guard/test-risk-api.php
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

if ( ! class_exists( 'AI_Checkout_Guard_Risk_API' ) ) {
    require_once dirname( __FILE__ ) . '/includes/class-risk-api.php';
}

$options_key = 'ai_checkout_guard_settings';

// Example test payload. Adjust values to match your store data.
$test_payload = array(
    'name'        => 'Jane Doe',
    'email'       => 'jane@example.com',
    'phone'       => '+11234567890',
    'address'     => '123 Main St',
    'pincode'     => '12345',
    'city'        => 'Metropolis',
    'state'       => 'NY',
    'country'     => 'US',
    'order_total' => 199.99,
    'items'       => array(
        array( 'sku' => 'SKU123', 'qty' => 2, 'price' => 49.99 ),
        array( 'sku' => 'SKU456', 'qty' => 1, 'price' => 99.99 ),
    ),
    'context'     => array( 'ip' => '203.0.113.42' ),
);

$risk_api = new AI_Checkout_Guard_Risk_API( $options_key );
$result   = $risk_api->calculate_risk( $test_payload );

if ( is_wp_error( $result ) ) {
    $output = array(
        'success' => false,
        'error'   => $result->get_error_message(),
    );
} else {
    $output = array(
        'success' => true,
        'result'  => $result,
    );
}

// Output JSON.
header( 'Content-Type: application/json; charset=utf-8' );
echo wp_json_encode( $output, JSON_PRETTY_PRINT );
