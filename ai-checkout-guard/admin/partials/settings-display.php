<?php
/**
 * Partial template: settings-display.php
 *
 * Renders the settings form for AI Checkout Guard.
 *
 * Available variables:
 *   - $option_key : The option key storing the settings.
 *   - $group      : The settings group for register_setting.
 *   - $page_slug  : The menu page slug.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Display screen icon and page title.
?>
<h1><?php esc_html_e( 'AI Checkout Guard Settings', 'ai-checkout-guard' ); ?></h1>

<?php
// Show settings error/update messages.
settings_errors( 'ai_checkout_guard_messages' );
?>

<form method="post" action="options.php">
    <?php
    // Output nonce, action, and option group hidden fields.
    settings_fields( $group );

    // Output all registered sections and fields for this page.
    do_settings_sections( $page_slug );

    // Output the Save Changes button.
    submit_button();
    ?>
</form>
