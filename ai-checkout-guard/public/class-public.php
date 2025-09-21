<?php
/**
 * Public-facing functionality for AI Checkout Guard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Checkout_Guard_Public {

	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Plugin directory path.
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
	 * Constructor.
	 *
	 * @param string $plugin_url URL to plugin root.
	 * @param string $plugin_dir File system path to plugin root.
	 * @param string $option_key Settings option key.
	 */
	public function __construct( $plugin_url, $plugin_dir, $option_key ) {
		$this->plugin_url = untrailingslashit( $plugin_url );
		$this->plugin_dir = untrailingslashit( $plugin_dir );
		$this->option_key = $option_key;
	}

	/**
	 * Initialize public hooks.
	 */
	public function init() {
		// Enqueue styles and scripts on the front end.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Shortcode for displaying risk info if needed.
		add_shortcode( 'ai_guard_info', array( $this, 'render_info_shortcode' ) );
	}

	/**
	 * Enqueue public CSS and JS.
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'ai-checkout-guard-public',
			$this->plugin_url . '/public/css/checkout-styles.css',
			array(),
			filemtime( $this->plugin_dir . '/public/css/checkout-styles.css' )
		);

		wp_enqueue_script(
			'ai-checkout-guard-public',
			$this->plugin_url . '/public/js/checkout-handler.js',
			array( 'jquery' ),
			filemtime( $this->plugin_dir . '/public/js/checkout-handler.js' ),
			true
		);

		// Localize settings for public script as well.
		wp_localize_script(
			'ai-checkout-guard-public',
			'AI_Guard_Public',
			array(
				'ajax_url'   => rest_url( 'ai-guard/v1/risk' ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'cod_action' => $this->get_setting( 'cod_action', 'verify' ),
			)
		);
	}

	/**
	 * Render a simple info box via shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_info_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'type' => 'tier', // tier|score|reason
			),
			$atts,
			'ai_guard_info'
		);

		// Attempt to get decision from Checkout Filter class.
		if ( class_exists( 'AI_Checkout_Guard_Checkout_Filter' ) ) {
			$filter = new AI_Checkout_Guard_Checkout_Filter( $this->plugin_url, $this->plugin_dir, $this->option_key );
			$decision = $filter->get_decision();
		} else {
			$decision = array( 'tier' => 'medium', 'score' => 50, 'reason' => '' );
		}

		$output = '';
		switch ( $atts['type'] ) {
			case 'score':
				$output = esc_html( $decision['score'] );
				break;
			case 'reason':
				$output = esc_html( $decision['reason'] );
				break;
			case 'tier':
			default:
				$output = esc_html( ucfirst( $decision['tier'] ) );
				break;
		}

		return '<span class="ai-guard-info ai-guard-info-' . esc_attr( $atts['type'] ) . '">' . $output . '</span>';
	}

	/**
	 * Retrieve a setting value.
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
