<?php
/**
 * WhatsApp confirmation integration for AI Checkout Guard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Checkout_Guard_WhatsApp_API {

	/**
	 * Settings option key.
	 *
	 * @var string
	 */
	private $option_key = 'ai_checkout_guard_settings';

	/**
	 * Construct.
	 *
	 * @param string $option_key Option key.
	 */
	public function __construct( $option_key = 'ai_checkout_guard_settings' ) {
		$this->option_key = $option_key;
	}

	/**
	 * Send a WhatsApp confirmation message.
	 *
	 * @param int    $order_id    Order ID.
	 * @param string $phone       Recipient phone number in E.164 format.
	 * @param string $template_id WhatsApp template ID.
	 * @param array  $variables   Template variable substitutions.
	 * @return bool|WP_Error      True on success or WP_Error on failure.
	 */
	public function send_confirmation( $order_id, $phone, $template_id, array $variables = array() ) {
		$settings     = get_option( $this->option_key, array() );
		$api_endpoint = isset( $settings['whatsapp_api_url'] ) ? sanitize_text_field( $settings['whatsapp_api_url'] ) : '';
		$api_token    = isset( $settings['whatsapp_api_token'] ) ? sanitize_text_field( $settings['whatsapp_api_token'] ) : '';

		if ( empty( $api_endpoint ) || empty( $api_token ) ) {
			return new WP_Error( 'wa_api_missing', __( 'WhatsApp API URL or token not configured.', 'ai-checkout-guard' ) );
		}

		$payload = array(
			"to"       => $phone,
			"type"     => "template",
			"template" => array(
				"name"       => $template_id,
				"language"   => array( "code" => "en_US" ),
				"components" => array(
					array(
						"type"       => "body",
						"parameters" => array_map( function( $var ) {
							return array( "type" => "text", "text" => sanitize_text_field( $var ) );
						}, $variables ),
					),
				),
			),
		);

		$args = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_token,
			),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 5,
		);

		$response = wp_remote_post( $api_endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code > 299 ) {
			$body = wp_remote_retrieve_body( $response );
			return new WP_Error( 'wa_api_error', sprintf( __( 'WhatsApp API error %d: %s', 'ai-checkout-guard' ), $code, wp_strip_all_tags( $body ) ) );
		}

		return true;
	}
}
