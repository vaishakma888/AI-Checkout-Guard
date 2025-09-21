<?php
/**
 * SMS OTP verification handler for AI Checkout Guard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Checkout_Guard_OTP_Handler {

	/**
	 * Option key for settings.
	 *
	 * @var string
	 */
	private $option_key = 'ai_checkout_guard_settings';

	/**
	 * Transient prefix for OTP codes.
	 *
	 * @var string
	 */
	private $transient_prefix = 'ai_guard_otp_';

	/**
	 * OTP length.
	 *
	 * @var int
	 */
	private $length = 6;

	/**
	 * OTP TTL in seconds.
	 *
	 * @var int
	 */
	private $ttl = 300; // 5 minutes.

	/**
	 * Constructor.
	 *
	 * @param string $option_key Settings option key.
	 */
	public function __construct( $option_key = 'ai_checkout_guard_settings' ) {
		$this->option_key = $option_key;
	}

	/**
	 * Send OTP to a phone number.
	 *
	 * @param string $phone E.164 formatted phone number.
	 * @return bool|WP_Error True on success or WP_Error on failure.
	 */
	public function send_otp( $phone ) {
		$settings    = get_option( $this->option_key, array() );
		$api_endpoint = isset( $settings['sms_api_url'] ) ? sanitize_text_field( $settings['sms_api_url'] ) : '';
		$api_key      = isset( $settings['sms_api_key'] ) ? sanitize_text_field( $settings['sms_api_key'] ) : '';

		if ( empty( $api_endpoint ) || empty( $api_key ) ) {
			return new WP_Error( 'otp_api_missing', __( 'SMS API URL or key not configured.', 'ai-checkout-guard' ) );
		}

		$otp  = wp_generate_password( $this->length, false, false );
		$transient_key = $this->transient_prefix . md5( $phone );

		// Store OTP in transient.
		set_transient( $transient_key, $otp, $this->ttl );

		// Prepare SMS payload.
		$body = array(
			'to'      => $phone,
			'message' => sprintf( __( 'Your verification code is: %s', 'ai-checkout-guard' ), $otp ),
		);

		$args = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 5,
		);

		$response = wp_remote_post( $api_endpoint, $args );

		if ( is_wp_error( $response ) ) {
			delete_transient( $transient_key );
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code > 299 ) {
			delete_transient( $transient_key );
			$resp_body = wp_remote_retrieve_body( $response );
			return new WP_Error( 'otp_api_error', sprintf( __( 'SMS API error %d: %s', 'ai-checkout-guard' ), $code, wp_strip_all_tags( $resp_body ) ) );
		}

		return true;
	}

	/**
	 * Verify an OTP code for a phone number.
	 *
	 * @param string $phone E.164 formatted phone number.
	 * @param string $code  OTP code entered by user.
	 * @return bool True if valid, false otherwise.
	 */
	public function verify_otp( $phone, $code ) {
		$transient_key = $this->transient_prefix . md5( $phone );
		$stored = get_transient( $transient_key );

		if ( false === $stored ) {
			return false; // OTP expired or not found.
		}

		if ( hash_equals( $stored, sanitize_text_field( $code ) ) ) {
			delete_transient( $transient_key );
			return true;
		}

		return false;
	}
}
