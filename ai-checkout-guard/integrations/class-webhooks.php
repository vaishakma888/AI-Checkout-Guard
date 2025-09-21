<?php
/**
 * Webhook receiver for AI Checkout Guard.
 *
 * Listens for incoming webhook calls from the risk service
 * to update order metadata or statuses based on external evaluations.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Checkout_Guard_Webhooks {

	/**
	 * Option key for storing webhook secret.
	 *
	 * @var string
	 */
	private $option_key = 'ai_checkout_guard_settings';

	/**
	 * Initialize webhook listener.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST API route for incoming webhooks.
	 */
	public static function register_routes() {
		register_rest_route(
			'ai-guard/v1',
			'/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_webhook' ),
				'permission_callback' => array( __CLASS__, 'verify_signature' ),
			)
		);
	}

	/**
	 * Verify the webhook request signature.
	 *
	 * @param WP_REST_Request $request
	 * @return bool
	 */
	public static function verify_signature( WP_REST_Request $request ) {
		$settings    = get_option( 'ai_checkout_guard_settings', array() );
		$secret      = isset( $settings['webhook_secret'] ) ? sanitize_text_field( $settings['webhook_secret'] ) : '';
		$signature   = $request->get_header( 'X-Hub-Signature-256' );
		$body        = $request->get_body();

		if ( empty( $secret ) || empty( $signature ) ) {
			return false;
		}

		list( $algo, $hash ) = explode( '=', $signature, 2 ) + array( '', '' );
		$calc_hash = hash_hmac( $algo, $body, $secret );

		return hash_equals( $calc_hash, $hash );
	}

	/**
	 * Handle the webhook payload.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function handle_webhook( WP_REST_Request $request ) {
		$data = $request->get_json_params();

		if ( empty( $data['order_id'] ) || empty( $data['status'] ) ) {
			return new WP_REST_Response( array( 'error' => 'Invalid payload' ), 400 );
		}

		$order_id = absint( $data['order_id'] );
		$status   = sanitize_text_field( $data['status'] );

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_REST_Response( array( 'error' => 'Order not found' ), 404 );
		}

		// Update order meta for record-keeping.
		update_post_meta( $order_id, '_ai_guard_webhook_status', $status );
		update_post_meta( $order_id, '_ai_guard_webhook_payload', wp_json_encode( $data ) );

		// Optionally change order status if configured.
		// e.g., cancel fraudulent orders automatically.
		if ( 'fraud' === $status ) {
			$order->update_status( 'cancelled', __( 'Order cancelled by AI risk service', 'ai-checkout-guard' ) );
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}
}

// Bootstrap webhook routes.
AI_Checkout_Guard_Webhooks::init();
