<?php
/**
 * Frontend checkout filtering for AI Checkout Guard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Checkout_Guard_Checkout_Filter {

	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Plugin directory.
	 *
	 * @var string
	 */
	private $plugin_dir;

	/**
	 * Option key for settings.
	 *
	 * @var string
	 */
	private $option_key;

	/**
	 * Cached decision for current session.
	 *
	 * @var array|null
	 */
	private $decision = null;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_url   URL to plugin root.
	 * @param string $plugin_dir   File system path to plugin root.
	 * @param string $option_key   Option key storing settings.
	 */
	public function __construct( $plugin_url, $plugin_dir, $option_key ) {
		$this->plugin_url  = untrailingslashit( $plugin_url );
		$this->plugin_dir  = untrailingslashit( $plugin_dir );
		$this->option_key  = $option_key;
	}

	/**
	 * Hook into Checkout Block and classic checkout.
	 */
	public function init_checkout_hooks() {
		// For classic WooCommerce checkout and REST block compatibility.
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'filter_gateways' ) );
		// Expose JS variables for block integration.
		add_action( 'wp_enqueue_scripts', array( $this, 'localize_script' ) );
	}

	/**
	 * Register block integration (for custom JS).
	 */
	public function register_block_integration() {
		wp_enqueue_script(
			'ai-checkout-guard-block',
			$this->plugin_url . '/public/js/checkout-handler.js',
			array( 'wp-hooks', 'wp-element', 'wp-data', 'wc-checkout' ),
			filemtime( $this->plugin_dir . '/public/js/checkout-handler.js' ),
			true
		);
	}

	/**
	 * Localize JS with AJAX endpoint and nonce.
	 */
	public function localize_script() {
		wp_localize_script(
			'ai-checkout-guard-block',
			'AI_Checkout_Guard_Settings',
			array(
				'ajax_url'     => rest_url( 'ai-guard/v1/risk' ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'option_key'   => $this->option_key,
			)
		);
	}

	/**
	 * Filter available payment gateways based on risk decision.
	 *
	 * @param array $gateways All available gateways.
	 * @return array Filtered gateways.
	 */
	public function filter_gateways( $gateways ) {
		if ( isset( $gateways['cod'] ) ) {

			$decision = $this->get_decision();

			if ( empty( $decision ) || empty( $decision['tier'] ) ) {
				return $gateways;
			}

			$tier   = $decision['tier'];
			$action = $this->get_setting( 'cod_action', 'verify' );

			// Low risk: allow COD.
			if ( 'low' === $tier ) {
				return $gateways;
			}

			// Medium risk: allow but add a notice.
			if ( 'medium' === $tier ) {
				// Add a checkout notice only once.
				wc_add_notice(
					__( 'Tip: Prepay now to save ₹20 and ensure a smooth delivery.', 'ai-checkout-guard' ),
					'notice'
				);
				return $gateways;
			}

			// High risk: act based on policy.
			if ( 'high' === $tier ) {
				if ( 'hide' === $action ) {
					unset( $gateways['cod'] );
				} elseif ( 'verify' === $action ) {
					// Keep COD but require client-side verification in JS.
					// JS will hook into payment selection to prompt verification.
				}
			}
		}

		return $gateways;
	}

	/**
	 * Retrieve the risk decision, caching for current page load.
	 *
	 * @return array Decision array with keys tier, score, reason.
	 */
	private function get_decision() {
		if ( null !== $this->decision ) {
			return $this->decision;
		}

		// Build minimal payload from current cart and customer.
		$params = array(
			'name'        => WC()->customer->get_billing_first_name() . ' ' . WC()->customer->get_billing_last_name(),
			'email'       => WC()->customer->get_billing_email(),
			'phone'       => WC()->customer->get_billing_phone(),
			'address'     => WC()->customer->get_billing_address_1(),
			'pincode'     => WC()->customer->get_billing_postcode(),
			'order_total' => WC()->cart->total ?? 0,
			'items'       => array_map(
				function( $item ) {
					return array(
						'sku'   => $item->get_product()->get_sku(),
						'qty'   => $item->get_quantity(),
						'price' => $item->get_total() / $item->get_quantity(),
					);
				},
				WC()->cart->get_cart()
			),
		);

		// Call risk API synchronously.
		$response = wp_remote_post(
			rest_url( 'ai-guard/v1/risk' ),
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'X-WP-Nonce'    => wp_create_nonce( 'wp_rest' ),
				),
				'body'    => wp_json_encode( $params ),
				'timeout' => 3,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->decision = array( 'tier' => 'medium', 'score' => 50, 'reason' => $response->get_error_message() );
		} else {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$this->decision = is_array( $body ) ? $body : array( 'tier' => 'medium', 'score' => 50, 'reason' => 'Invalid response' );
		}

		return $this->decision;
	}

	/**
	 * Get a specific setting from options.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	private function get_setting( $key, $default = null ) {
		$opts = get_option( $this->option_key, array() );
		return isset( $opts[ $key ] ) ? $opts[ $key ] : $default;
	}
}
