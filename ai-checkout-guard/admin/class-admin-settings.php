<?php
/**
 * Admin settings page for AI Checkout Guard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Checkout_Guard_Admin_Settings {

	/**
	 * Option key where settings are stored.
	 *
	 * @var string
	 */
	private $option_key;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Page slug.
	 *
	 * @var string
	 */
	private $page_slug = 'ai-checkout-guard';

	/**
	 * Settings group.
	 *
	 * @var string
	 */
	private $group = 'ai_checkout_guard_group';

	/**
	 * Slug for settings errors.
	 *
	 * @var string
	 */
	private $notices_slug = 'ai_checkout_guard_messages';

	/**
	 * Constructor.
	 *
	 * @param string $option_key Option key.
	 * @param string $version    Plugin version.
	 */
	public function __construct( $option_key, $version ) {
		$this->option_key = $option_key;
		$this->version    = $version;
	}

	/**
	 * Register the admin menu.
	 */
	public function register_menu() {
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
	 * Register settings, sections, and fields.
	 */
	public function register_settings() {
		register_setting(
			$this->group,
			$this->option_key,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->default_settings(),
			)
		);

		add_settings_section(
			'ai_guard_section_general',
			__( 'General Settings', 'ai-checkout-guard' ),
			array( $this, 'section_general_cb' ),
			$this->page_slug
		);

		// Fields
		$this->add_field( 'api_url', __( 'Risk API URL', 'ai-checkout-guard' ), 'field_api_url_cb' );
		$this->add_field( 'api_key', __( 'API Key', 'ai-checkout-guard' ), 'field_api_key_cb' );
		$this->add_field( 'timeout', __( 'Timeout (seconds)', 'ai-checkout-guard' ), 'field_timeout_cb' );
		$this->add_field( 'cache_ttl', __( 'Cache TTL (seconds)', 'ai-checkout-guard' ), 'field_cache_ttl_cb' );
	}

	/**
	 * Helper to add a settings field.
	 *
	 * @param string $id      Field ID.
	 * @param string $title   Field title.
	 * @param string $callback Callback method name.
	 */
	private function add_field( $id, $title, $callback ) {
		add_settings_field(
			$id,
			$title,
			array( $this, $callback ),
			$this->page_slug,
			'ai_guard_section_general',
			array( 'label_for' => $id )
		);
	}

	/**
	 * Default settings values.
	 *
	 * @return array
	 */
	private function default_settings() {
		return array(
			'api_url'   => '',
			'api_key'   => '',
			'timeout'   => 3,
			'cache_ttl' => 60,
		);
	}

	/**
	 * General section callback.
	 */
	public function section_general_cb() {
		echo '<p>' . esc_html__( 'Configure your AI risk service settings.', 'ai-checkout-guard' ) . '</p>';
	}

	/**
	 * Render API URL field.
	 */
	public function field_api_url_cb() {
		$opts    = get_option( $this->option_key, $this->default_settings() );
		$current = isset( $opts['api_url'] ) ? $opts['api_url'] : '';
		printf(
			'<input type="url" id="api_url" name="%1$s[api_url]" value="%2$s" class="regular-text code" />',
			esc_attr( $this->option_key ),
			esc_url( $current )
		);
	}

	/**
	 * Render API key field.
	 */
	public function field_api_key_cb() {
		$opts    = get_option( $this->option_key, $this->default_settings() );
		$current = isset( $opts['api_key'] ) ? $opts['api_key'] : '';
		printf(
			'<input type="text" id="api_key" name="%1$s[api_key]" value="%2$s" class="regular-text" />',
			esc_attr( $this->option_key ),
			esc_attr( $current )
		);
	}

	/**
	 * Render timeout field.
	 */
	public function field_timeout_cb() {
		$opts    = get_option( $this->option_key, $this->default_settings() );
		$current = isset( $opts['timeout'] ) ? (int) $opts['timeout'] : 3;
		printf(
			'<input type="number" id="timeout" name="%1$s[timeout]" value="%2$d" min="1" max="60" />',
			esc_attr( $this->option_key ),
			intval( $current )
		);
	}

	/**
	 * Render cache TTL field.
	 */
	public function field_cache_ttl_cb() {
		$opts    = get_option( $this->option_key, $this->default_settings() );
		$current = isset( $opts['cache_ttl'] ) ? (int) $opts['cache_ttl'] : 60;
		printf(
			'<input type="number" id="cache_ttl" name="%1$s[cache_ttl]" value="%2$d" min="0" max="3600" />',
			esc_attr( $this->option_key ),
			intval( $current )
		);
	}

	/**
	 * Sanitize and validate settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$clean = array();

		$clean['api_url']   = ! empty( $input['api_url'] ) ? sanitize_text_field( $input['api_url'] ) : '';
		$clean['api_key']   = ! empty( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '';
		$clean['timeout']   = isset( $input['timeout'] ) ? max( 1, absint( $input['timeout'] ) ) : 3;
		$clean['cache_ttl'] = isset( $input['cache_ttl'] ) ? max( 0, absint( $input['cache_ttl'] ) ) : 60;

		add_settings_error( $this->notices_slug, 'settings_updated', __( 'Settings saved.', 'ai-checkout-guard' ), 'updated' );

		return $clean;
	}

	/**
	 * Render the settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'AI Checkout Guard Settings', 'ai-checkout-guard' ); ?></h1>
			<?php settings_errors( $this->notices_slug ); ?>
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
