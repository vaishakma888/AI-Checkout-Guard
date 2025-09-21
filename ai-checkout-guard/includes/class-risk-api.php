<?php
/**
 * Risk API communication for AI Checkout Guard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Checkout_Guard_Risk_API {

	/**
	 * Option key where settings are stored.
	 *
	 * @var string
	 */
	private $option_key = 'ai_checkout_guard_settings';

	/**
	 * External API URL.
	 *
	 * @var string
	 */
	private $api_url = '';

	/**
	 * API key or bearer token.
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * HTTP timeout (seconds).
	 *
	 * @var int
	 */
	private $timeout = 3;

	/**
	 * Transient cache TTL (seconds).
	 *
	 * @var int
	 */
	private $cache_ttl = 60;

	/**
	 * Construct.
	 *
	 * @param string $option_key Settings option key.
	 */
	public function __construct( $option_key = 'ai_checkout_guard_settings' ) {
		$this->option_key = $option_key;

		$settings = get_option( $this->option_key, array() );

		// Settings may also be stored in discrete options for convenience/migration.
		$this->api_url   = isset( $settings['api_url'] ) ? sanitize_text_field( $settings['api_url'] ) : sanitize_text_field( (string) get_option( 'ai_checkout_guard_api_endpoint', '' ) );
		$this->api_key   = isset( $settings['api_key'] ) ? sanitize_text_field( $settings['api_key'] ) : sanitize_text_field( (string) get_option( 'ai_checkout_guard_api_key', '' ) );
		$this->timeout   = isset( $settings['timeout'] ) ? max( 1, absint( $settings['timeout'] ) ) : 3;
		$this->cache_ttl = isset( $settings['cache_ttl'] ) ? max( 0, absint( $settings['cache_ttl'] ) ) : 60;
	}

	/**
	 * Public entry: compute risk for given checkout/cart params.
	 *
	 * @param array $raw_params Raw params from REST/controller.
	 * @return array|WP_Error   Normalized result array or WP_Error on hard failure.
	 */
	public function calculate_risk( $raw_params ) {
		$payload = $this->build_payload( (array) $raw_params );

		// If API URL is missing, return a neutral decision rather than failing hard.
		if ( empty( $this->api_url ) ) {
			return $this->neutral_result( 'Risk API URL not configured' );
		}

		// Optional tiny cache to avoid hammering the API during a single checkout session.
		$cache_key = 'ai_guard_risk_' . md5( wp_json_encode( $payload ) );
		if ( $this->cache_ttl > 0 ) {
			$cached = get_transient( $cache_key );
			if ( ! empty( $cached ) && is_array( $cached ) ) {
				return $cached;
			}
		}

		$args = array(
			'timeout' => $this->timeout,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
				'Authorization' => ! empty( $this->api_key ) ? 'Bearer ' . $this->api_key : '',
			),
			'body'    => wp_json_encode( $payload ),
		);

		$response = wp_remote_post( $this->api_url, $args );

		if ( is_wp_error( $response ) ) {
			return $this->neutral_result( 'HTTP error: ' . $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code > 299 ) {
			return $this->neutral_result( 'Bad status: ' . $code );
		}

		$data = json_decode( $body, true );

		// If API returns invalid JSON or unexpected structure, return neutral.
		if ( null === $data || ! is_array( $data ) ) {
			return $this->neutral_result( 'Invalid JSON' );
		}

		$normalized = $this->normalize_result( $data );

		// Cache briefly if configured.
		if ( $this->cache_ttl > 0 ) {
			set_transient( $cache_key, $normalized, $this->cache_ttl );
		}

		return $normalized;
	}

	/**
	 * Build sanitized payload for the Risk API.
	 *
	 * @param array $p Raw input.
	 * @return array
	 */
	private function build_payload( array $p ): array {
		// Sanitize typical checkout fields; all are optional.
		$name     = isset( $p['name'] ) ? sanitize_text_field( $p['name'] ) : '';
		$email    = isset( $p['email'] ) ? sanitize_text_field( $p['email'] ) : '';
		$phone    = isset( $p['phone'] ) ? sanitize_text_field( $p['phone'] ) : '';
		$address  = isset( $p['address'] ) ? sanitize_text_field( $p['address'] ) : '';
		$pincode  = isset( $p['pincode'] ) ? sanitize_text_field( $p['pincode'] ) : '';
		$city     = isset( $p['city'] ) ? sanitize_text_field( $p['city'] ) : '';
		$state    = isset( $p['state'] ) ? sanitize_text_field( $p['state'] ) : '';
		$country  = isset( $p['country'] ) ? sanitize_text_field( $p['country'] ) : '';
		$total    = isset( $p['order_total'] ) ? max( 0, floatval( $p['order_total'] ) ) : 0.0;
		$items    = isset( $p['items'] ) && is_array( $p['items'] ) ? $this->sanitize_items( $p['items'] ) : array();

		$context  = isset( $p['context'] ) && is_array( $p['context'] ) ? $p['context'] : array();

		return array(
			'customer' => array(
				'name'    => $name,
				'email'   => $email,
				'phone'   => $phone,
			),
			'shipping' => array(
				'address' => $address,
				'pincode' => $pincode,
				'city'    => $city,
				'state'   => $state,
				'country' => $country,
			),
			'order'    => array(
				'total' => $total,
				'items' => $items,
			),
			'context'  => $context, // any extra device/session data if needed.
		);
	}

	/**
	 * Sanitize items array quickly (only the minimal fields the API might need).
	 *
	 * @param array $items Raw items.
	 * @return array
	 */
	private function sanitize_items( array $items ): array {
		$out = array();

		foreach ( $items as $it ) {
			$out[] = array(
				'sku'      => isset( $it['sku'] ) ? sanitize_text_field( $it['sku'] ) : '',
				'qty'      => isset( $it['qty'] ) ? max( 1, absint( $it['qty'] ) ) : 1,
				'price'    => isset( $it['price'] ) ? max( 0, floatval( $it['price'] ) ) : 0.0,
				'category' => isset( $it['category'] ) ? sanitize_text_field( $it['category'] ) : '',
			);
		}

		return $out;
	}

	/**
	 * Normalize API response to a stable structure the plugin can rely on.
	 *
	 * @param array $data Raw API response array.
	 * @return array
	 */
	private function normalize_result( array $data ): array {
		// Accept common key variants and normalize.
		$tier   = isset( $data['tier'] ) ? sanitize_text_field( $data['tier'] ) :
		          ( isset( $data['risk'] ) ? sanitize_text_field( $data['risk'] ) : 'medium' );

		$score  = isset( $data['score'] ) ? intval( $data['score'] ) :
		          ( isset( $data['risk_score'] ) ? intval( $data['risk_score'] ) : 50 );

		$reason = isset( $data['reason'] ) ? sanitize_text_field( $data['reason'] ) :
		          ( isset( $data['message'] ) ? sanitize_text_field( $data['message'] ) : '' );

		// Clamp values to expected domain.
		$tier  = in_array( $tier, array( 'low', 'medium', 'high' ), true ) ? $tier : 'medium';
		$score = max( 0, min( 100, $score ) );

		return array(
			'tier'   => $tier,
			'score'  => $score,
			'reason' => $reason,
		);
	}

	/**
	 * A safe neutral result used on failures/misconfigurations.
	 *
	 * @param string $why Explanation.
	 * @return array
	 */
	private function neutral_result( string $why ): array {
		return array(
			'tier'   => 'medium',
			'score'  => 50,
			'reason' => $why,
		);
	}
}
