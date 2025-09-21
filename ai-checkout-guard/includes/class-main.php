<?php
/**
 * Main plugin controller class for AI Checkout Guard.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Checkout_Guard_Main {

    /**
     * Plugin version.
     *
     * @var string
     */
    private $version;

    /**
     * Absolute path to main plugin file.
     *
     * @var string
     */
    private $plugin_file;

    /**
     * Plugin basename (dir/file.php).
     *
     * @var string
     */
    private $plugin_basename;

    /**
     * Absolute filesystem path to plugin directory, with trailing slash.
     *
     * @var string
     */
    private $plugin_dir;

    /**
     * URL to plugin directory, with trailing slash.
     *
     * @var string
     */
    private $plugin_url;

    /**
     * Options key used to store settings.
     *
     * @var string
     */
    private $option_key = 'ai_checkout_guard_settings';

    /**
     * Constructor.
     *
     * @param string      $version     Plugin version.
     * @param string      $plugin_file Absolute path to main plugin file.
     */
    public function __construct( $version, $plugin_file ) {
        $this->version         = $version;
        $this->plugin_file     = $plugin_file;
        $this->plugin_basename = plugin_basename( $plugin_file );
        $this->plugin_dir      = plugin_dir_path( $plugin_file );
        $this->plugin_url      = plugin_dir_url( $plugin_file );

        $this->load_dependencies();
    }

    /**
     * Bootstrap all hooks.
     */
    public function run() {
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        add_action( 'admin_init',     [ $this, 'queue_dependency_notice' ] );

        // Admin
        add_action( 'admin_menu',        [ $this, 'init_admin' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // Public
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );
        add_action( 'rest_api_init',       [ $this, 'register_api_routes' ] );
    }

    /**
     * Include PHP dependencies exactly once.
     */
    private function load_dependencies() {
        require_once $this->plugin_dir . 'includes/class-risk-api.php';
        require_once $this->plugin_dir . 'admin/class-admin-settings.php';
        require_once $this->plugin_dir . 'public/class-checkout-filter.php';
    }

    /**
     * Load plugin textdomain.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'ai-checkout-guard',
            false,
            dirname( $this->plugin_basename ) . '/languages'
        );
    }

    /**
     * Initialize admin components.
     */
    public function init_admin() {
        if ( ! is_admin() ) {
            return;
        }

        $settings = new AI_Checkout_Guard_Admin_Settings( $this->option_key, $this->version );
        $settings->register_menu();
        $settings->register_settings();
    }

    /**
     * Queue a notice if WooCommerce is inactive.
     */
    public function queue_dependency_notice() {
        if ( ! class_exists( 'WooCommerce' ) && is_admin() ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible">'
                   . '<p>' . esc_html__( 'AI Checkout Guard requires WooCommerce. Please install and activate WooCommerce.', 'ai-checkout-guard' ) . '</p>'
                   . '</div>';
            } );
        }
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets() {
        if ( ! is_admin() ) {
            return;
        }

        wp_enqueue_style(
            'ai-checkout-guard-admin',
            $this->plugin_url . 'admin/css/admin-styles.css',
            [],
            $this->version
        );

        wp_enqueue_script(
            'ai-checkout-guard-admin',
            $this->plugin_url . 'admin/js/admin-scripts.js',
            [ 'jquery' ],
            $this->version,
            true
        );
    }

    /**
     * Enqueue public assets only on checkout page.
     */
    public function enqueue_public_assets() {
        if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
            return;
        }

        wp_enqueue_style(
            'ai-checkout-guard-public',
            $this->plugin_url . 'public/css/checkout-styles.css',
            [],
            $this->version
        );

        wp_enqueue_script(
            'ai-checkout-guard-public',
            $this->plugin_url . 'public/js/checkout-handler.js',
            [ 'jquery' ],
            $this->version,
            true
        );

        wp_localize_script(
            'ai-checkout-guard-public',
            'ACG',
            [
                'predictUrl' => esc_url_raw( rest_url( 'acg/v1/predict' ) ),
                'nonce'      => wp_create_nonce( 'wp_rest' ),
            ]
        );
    }

    /**
     * Register REST API routes.
     */
    public function register_api_routes() {
        register_rest_route(
            'acg/v1',
            '/predict',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'rest_handle_risk' ],
                'permission_callback' => '__return_true',
            ]
        );
    }

    /**
     * Handle risk evaluation via REST.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_handle_risk( WP_REST_Request $request ) {
        $params = $request->get_json_params();

        if ( empty( $params ) ) {
            return rest_ensure_response( [
                'tier'   => 'medium',
                'score'  => 50,
                'reason' => 'Insufficient data provided',
            ] );
        }

        $risk_api = new AI_Checkout_Guard_Risk_API( $this->option_key );
        $result   = $risk_api->calculate_risk( $params );

        if ( is_wp_error( $result ) ) {
            return rest_ensure_response( [
                'tier'   => 'medium',
                'score'  => 50,
                'reason' => 'Error calculating risk',
            ] );
        }

        return rest_ensure_response( $result );
    }

    /**
     * Accessors.
     */
    public function get_version()    { return $this->version; }
    public function get_plugin_dir() { return $this->plugin_dir; }
    public function get_plugin_url() { return $this->plugin_url; }
}
