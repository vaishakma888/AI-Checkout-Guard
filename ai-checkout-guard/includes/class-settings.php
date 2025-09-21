<?php
/**
 * Settings management for AI Checkout Guard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Checkout_Guard_Admin_Settings {

	/**
	 * Option key where all settings are stored.
	 *
	 * @var string
	 */
	private $option_key = 'ai_checkout_guard_settings';

	/**
	 * Plugin version (for cache busting).
	 *
	 * @var string
	 */
	private $version = '1.0.0';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	private $page_slug = 'ai-checkout-guard';

	/**
	 * Settings group name (used in register_setting / options form).
	 *
	 * @var string
	 */
	private $group = 'ai_checkout_guard_group';

	/**
	 * Message slug for settings_errors.
	 *
	 * @var string
	 */
	private $notices_slug = 'ai_checkout_guard_messages';

	/**
	 * Construct.
	 *
	 * @param string $option_key Option key.
	 * @param string $version    Version.
	 */
	public function __construct( $option_key = 'ai_checkout_guard_settings', $version = '1.0.0' ) {
		$this->option_key = $option_key;
		$this->version    = $version;
	}

	/**
	 * Hook the menu item (called from main class).
	 */
	public function register_menu() {
		// Add under WooCommerce menu if available; otherwise fallback to Settings.
		if ( class_exists( 'WooCommerce' ) ) {
			add_submenu_page(
				'woocommerce',
				__( 'AI Checkout Guard', 'ai-checkout-guard' ),
				__( 'AI Checkout Guard', 'ai-checkout-guard' ),
				'manage_woocommerce',
				$this->page_slug,
				array( $this, 'render_page' )
			);
		} else {
			add_options_page(
				__( 'AI Checkout Guard', 'ai-checkout-guard' ),
				__( 'AI Checkout Guard', 'ai-checkout-guard' ),
				'manage_options',
				$this->page_slug,
				array( $this, 'render_page' )
			);
		}
	}

	/**
	 * Register settings, sections, and fields (called from main class on admin_init).
	 */
	public function register_settings() {

		// Register the option with sanitize callback.
		register_setting(
			$this->group,
			$this->option_key,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->default_settings(),
			)
		); // WordPress Settings API requires register_setting for the option to be saved. [2][1]

		// General section.
		add_settings_section(
			'ai_guard_section_general',
			__( 'General', 'ai-checkout-guard' ),
			array( $this, 'section_general_cb' ),
			$this->page_slug
		); // Sections structure the settings page and precede fields. [1][8]

		// API URL.
		add_settings_field(
			'api_url',
			__( 'Risk API URL', 'ai-checkout-guard' ),
			array( $this, 'field_api_url_cb' ),
			$this->page_slug,
			'ai_guard_section_general',
			array( 'label_for' => 'api_url' )
		); // Fields render inputs and must be paired with register_setting. [3][5]

		// API Key.
		add_settings_field(
			'api_key',
			__( 'API Key (Bearer)', 'ai-checkout-guard' ),
			array( $this, 'field_api_key_cb' ),
			$this->page_slug,
			'ai_guard_section_general',
			array( 'label_for' => 'api_key' )
		); // Adding multiple fields to the same section is standard. [3][11]

		// Timeouts & cache.
		add_settings_field(
			'timeout',
			__( 'HTTP Timeout (seconds)', 'ai-checkout-guard' ),
			array( $this, 'field_timeout_cb' ),
			$this->page_slug,
			'ai_guard_section_general',
			array( 'label_for' => 'timeout' )
		); // Numeric settings should be sanitized and validated. [16]

		add_settings_field(
			'cache_ttl',
			__( 'API Cache TTL (seconds)', 'ai-checkout-guard' ),
			array( $this, 'field_cache_ttl_cb' ),
			$this->page_slug,
			'ai_guard_section_general',
			array( 'label_for' => 'cache_ttl' )
		); // Cache TTL helps reduce API calls across a short window. [3]

		// Policy section.
		add_settings_section(
			'ai_guard_section_policy',
			__( 'Risk Policy', 'ai-checkout-guard' ),
			array( $this, 'section_policy_cb' ),
			$this->page_slug
		); // Separate policy fields for clarity. [8]

		add_settings_field(
			'low_threshold',
			__( 'Low Risk Threshold (0–100)', 'ai-checkout-guard' ),
			array( $this, 'field_low_threshold_cb' ),
			$this->page_slug,
			'ai_guard_section_policy',
			array( 'label_for' => 'low_threshold' )
		); // Fields added after section registration show in that section. [5]

		add_settings_field(
			'high_threshold',
			__( 'High Risk Threshold (0–100)', 'ai-checkout-guard' ),
			array( $this, 'field_high_threshold_cb' ),
			$this->page_slug,
			'ai_guard_section_policy',
			array( 'label_for' => 'high_threshold' )
		); // Another policy field using same pattern. [3]

		add_settings_field(
			'cod_action',
			__( 'COD Action for High Risk', 'ai-checkout-guard' ),
			array( $this, 'field_cod_action_cb' ),
			$this->page_slug,
			'ai_guard_section_policy',
			array( 'label_for' => 'cod_action' )
		); // The select field controlling checkout behavior. [3]
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	private function default_settings(): array {
		return array(
			'api_url'        => '',
			'api_key'        => '',
			'timeout'        => 3,
			'cache_ttl'      => 60,
			'low_threshold'  => 30,
			'high_threshold' => 70,
			'cod_action'     => 'verify', // verify|hide|allow
		);
	}

	/**
	 * Section callbacks.
	 */
	public function section_general_cb() {
		echo '<p>' . esc_html__( 'Connect your risk service and basic performance settings.', 'ai-checkout-guard' ) . '</p>'; // Provides help text for the section. [8]
	}

	public function section_policy_cb() {
		echo '<p>' . esc_html__( 'Define how COD behaves for different risk levels.', 'ai-checkout-guard' ) . '</p>'; // Simple explainer text. [8]
	}

	/**
	 * Field callbacks.
	 */
	public function field_api_url_cb() {
		$opt     = get_option( $this->option_key, $this->default_settings() );
		$current = isset( $opt['api_url'] ) ? $opt['api_url'] : '';
		printf(
			'<input type="url" id="api_url" name="%1$s[api_url]" class="regular-text code" value="%2$s" placeholder="https://risk.example.com/v1/score" />',
			esc_attr( $this->option_key ),
			esc_attr( $current )
		); // Input fields render bound to the registered option. [5]
	}

	public function field_api_key_cb() {
		$opt     = get_option( $this->option_key, $this->default_settings() );
		$current = isset( $opt['api_key'] ) ? $opt['api_key'] : '';
		printf(
			'<input type="text" id="api_key" name="%1$s[api_key]" class="regular-text" value="%2$s" autocomplete="off" />',
			esc_attr( $this->option_key ),
			esc_attr( $current )
		); // API keys should not be auto-filled; still stored as plain since WP core lacks secrets vault. [3]
	}

	public function field_timeout_cb() {
		$opt     = get_option( $this->option_key, $this->default_settings() );
		$current = isset( $opt['timeout'] ) ? (int) $opt['timeout'] : 3;
		printf(
			'<input type="number" min="1" max="20" step="1" id="timeout" name="%1$s[timeout]" value="%2$d" />',
			esc_attr( $this->option_key ),
			intval( $current )
		); // Numeric field with sensible bounds; validated again in sanitize. [16]
	}

	public function field_cache_ttl_cb() {
		$opt     = get_option( $this->option_key, $this->default_settings() );
		$current = isset( $opt['cache_ttl'] ) ? (int) $opt['cache_ttl'] : 60;
		printf(
			'<input type="number" min="0" max="3600" step="5" id="cache_ttl" name="%1$s[cache_ttl]" value="%2$d" />',
			esc_attr( $this->option_key ),
			intval( $current )
		); // TTL can be zero to disable cache. [3]
	}

	public function field_low_threshold_cb() {
		$opt     = get_option( $this->option_key, $this->default_settings() );
		$current = isset( $opt['low_threshold'] ) ? (int) $opt['low_threshold'] : 30;
		printf(
			'<input type="number" min="0" max="100" step="1" id="low_threshold" name="%1$s[low_threshold]" value="%2$d" />',
			esc_attr( $this->option_key ),
			intval( $current )
		); // Thresholds clamped to 0–100 in sanitize. [3]
	}

	public function field_high_threshold_cb() {
		$opt     = get_option( $this->option_key, $this->default_settings() );
		$current = isset( $opt['high_threshold'] ) ? (int) $opt['high_threshold'] : 70;
		printf(
			'<input type="number" min="0" max="100" step="1" id="high_threshold" name="%1$s[high_threshold]" value="%2$d" />',
			esc_attr( $this->option_key ),
			intval( $current )
		); // Complement to low threshold for policy split. [3]
	}

	public function field_cod_action_cb() {
		$opt     = get_option( $this->option_key, $this->default_settings() );
		$current = isset( $opt['cod_action'] ) ? $opt['cod_action'] : 'verify';
		$options = array(
			'verify' => __( 'Require WhatsApp/SMS verification', 'ai-checkout-guard' ),
			'hide'   => __( 'Hide COD for high risk', 'ai-checkout-guard' ),
			'allow'  => __( 'Allow COD', 'ai-checkout-guard' ),
		);
		printf(
			'<select id="cod_action" name="%1$s[cod_action]">',
			esc_attr( $this->option_key )
		);
		foreach ( $options as $val => $label ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $val ),
				selected( $current, $val, false ),
				esc_html( $label )
			);
		}
		echo '</select>'; // Standard select field rendering. [3]
	}

	/**
	 * Sanitize and validate settings.
	 *
	 * @param array $input Raw input from form.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();

		$clean = array();
		$clean['api_url']        = isset( $input['api_url'] ) ? sanitize_text_field( $input['api_url'] ) : '';
		$clean['api_key']        = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '';
		$clean['timeout']        = isset( $input['timeout'] ) ? max( 1, (int) $input['timeout'] ) : 3;
		$clean['cache_ttl']      = isset( $input['cache_ttl'] ) ? max( 0, (int) $input['cache_ttl'] ) : 60;
		$clean['low_threshold']  = isset( $input['low_threshold'] ) ? min( 100, max( 0, (int) $input['low_threshold'] ) ) : 30;
		$clean['high_threshold'] = isset( $input['high_threshold'] ) ? min( 100, max( 0, (int) $input['high_threshold'] ) ) : 70;

		$allowed_actions         = array( 'verify', 'hide', 'allow' );
		$clean['cod_action']     = ( isset( $input['cod_action'] ) && in_array( $input['cod_action'], $allowed_actions, true ) )
			? $input['cod_action']
			: 'verify';

		// Basic validation: high should be >= low.
		if ( $clean['high_threshold'] < $clean['low_threshold'] ) {
			add_settings_error(
				$this->notices_slug,
				'ai_guard_thresholds',
				__( 'High risk threshold must be greater than or equal to Low risk threshold.', 'ai-checkout-guard' ),
				'error'
			); // Register a validation error that will be displayed on the settings page. [12][9]
		} else {
			add_settings_error(
				$this->notices_slug,
				'ai_guard_saved',
				__( 'Settings saved.', 'ai-checkout-guard' ),
				'updated'
			); // Register success notice per Settings API conventions. [12][15]
		}

		return $clean; // Settings API will persist the returned array. [2]
	}

	/**
	 * Render the settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'AI Checkout Guard', 'ai-checkout-guard' ); ?></h1>

			<?php
			// Display Settings API messages for this page.
			settings_errors( $this->notices_slug );
			// settings_errors automatically outputs messages added via add_settings_error. [15][6]
			?>

			<form method="post" action="options.php">
				<?php
				settings_fields( $this->group );
				do_settings_sections( $this->page_slug );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
