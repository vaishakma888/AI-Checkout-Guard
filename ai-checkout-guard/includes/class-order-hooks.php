<?php
/**
 * Order event handling for AI Checkout Guard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Checkout_Guard_Order_Hooks {

	/**
	 * Option key for settings (used to load webhook URL/key).
	 *
	 * @var string
	 */
	private $option_key = 'ai_checkout_guard_settings';

	/**
	 * Construct and hook order events.
	 *
	 * @param string $option_key Settings option key.
	 */
	public function __construct( $option_key = 'ai_checkout_guard_settings' ) {
		$this->option_key = $option_key;

		// Hook order created (new order placed).
		add_action( 'woocommerce_new_order', array( $this, 'on_new_order' ), 10, 1 );

		// Hook order status transitions.
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_completed' ), 10, 1 );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'on_order_cancelled' ), 10, 1 );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'on_order_refunded' ), 10, 1 );
	}

	/**
	 * Handle new orders: send initial risk event to backend.
	 *
	 * @param int $order_id Order ID.
	 */
	public function on_new_order( $order_id ) {
		if ( ! $order = wc_get_order( $order_id ) ) {
			return;
		}

		$this->send_webhook( $order, 'order.created' );
	}

	/**
	 * Handle completed orders: update risk backend.
	 *
	 * @param int $order_id Order ID.
	 */
	public function on_order_completed( $order_id ) {
		if ( ! $order = wc_get_order( $order_id ) ) {
			return;
		}

		$this->send_webhook( $order, 'order.completed' );
	}

	/**
	 * Handle cancelled orders: update risk backend.
	 *
	 * @param int $order_id Order ID.
	 */
	public function on_order_cancelled( $order_id ) {
		if ( ! $order = wc_get_order( $order_id ) ) {
			return;
		}

		$this->send_webhook( $order, 'order.cancelled' );
	}

	/**
	 * Handle refunded orders: update risk backend.
	 *
	 * @param int $order_id Order ID.
	 */
	public function on_order_refunded( $order_id ) {
		if ( ! $order = wc_get_order( $order_id ) ) {
			return;
		}

		$this->send_webhook( $order, 'order.refunded' );
	}

	/**
	 * Send order event to external risk service via REST API or webhook.
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $event Event name.
	 */
	private function send_webhook( $order, $event ) {
		// Load API settings.
		$settings = get_option( $this->option_key, array() );
		$webhook_url = isset( $settings['webhook_url'] ) ? sanitize_text_field( $settings['webhook_url'] ) : '';
		$webhook_key = isset( $settings['webhook_key'] ) ? sanitize_text_field( $settings['webhook_key'] ) : '';

		if ( empty( $webhook_url ) ) {
			return; // No webhook configured.
		}

		// Prepare payload data.
		$payload = array(
			'event'     => $event,
			'order_id'  => $order->get_id(),
			'status'    => $order->get_status(),
			'total'     => (float) $order->get_total(),
			'currency'  => $order->get_currency(),
			'created'   => $order->get_date_created() ? $order->get_date_created()->date_i18n( 'c' ) : '',
			'customer'  => array(
				'id'    => $order->get_user_id(),
				'email' => $order->get_billing_email(),
				'phone' => $order->get_billing_phone(),
			),
		);

		$args = array(
			'timeout' => 5,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $webhook_key,
			),
			'body'    => wp_json_encode( $payload ),
		);

		// Fire and forget: do not halt order processing on failures.
		wp_remote_post( $webhook_url, $args );
	}
}
