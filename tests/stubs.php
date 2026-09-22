<?php
/**
 * Lightweight WooCommerce and WordPress stubs for isolated Unit Tests.
 */

namespace Automattic\WooCommerce\Blocks\Payments\Integrations {
	if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		abstract class AbstractPaymentMethodType {
			protected $name = '';
			protected $settings = array();

			public function initialize() {}
			public function is_active() { return true; }
			public function get_name() { return $this->name; }
			public function get_payment_method_script_handles() { return array(); }
			public function get_payment_method_data() { return array(); }
			public function get_setting( $key, $default = '' ) {
				return $this->settings[ $key ] ?? $default;
			}
		}
	}
}

namespace Automattic\WooCommerce\Blocks\Payments {
	if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry' ) ) {
		class PaymentMethodRegistry {
			public array $registered = array();
			public function register( $payment_method ) {
				$this->registered[] = $payment_method;
			}
		}
	}
}

namespace Automattic\WooCommerce\Utilities {
	if ( ! class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		class FeaturesUtil {
			public static array $features = array();
			public static function declare_compatibility( $feature, $plugin_file, $positive = true ) {
				self::$features[ $feature ] = compact( 'plugin_file', 'positive' );
			}
		}
	}
}

namespace {
	if ( ! class_exists( 'WP_Error' ) ) {
		class WP_Error {
			protected array $errors = array();
			protected array $error_data = array();

			public function __construct( $code = '', $message = '', $data = '' ) {
				if ( ! empty( $code ) ) {
					$this->errors[ $code ][] = $message;
					if ( ! empty( $data ) ) {
						$this->error_data[ $code ] = $data;
					}
				}
			}

			public function get_error_message( $code = '' ) {
				if ( empty( $code ) ) {
					$code = array_key_first( $this->errors );
				}
				return $this->errors[ $code ][0] ?? '';
			}

			public function get_error_code() {
				return array_key_first( $this->errors );
			}
		}
	}

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		class WC_Payment_Gateway {
			public $id = '';
			public $icon = '';
			public $has_fields = false;
			public $method_title = '';
			public $method_description = '';
			public $title = '';
			public $description = '';
			public $enabled = 'no';
			public $supports = array( 'products' );
			public $form_fields = array();
			public $settings = array();
			public $errors = array();

			public function init_settings() {
				$this->settings = get_option( 'woocommerce_' . $this->id . '_settings', array() );
				$this->enabled  = $this->get_option( 'enabled', 'no' );
			}

			public function get_option( $key, $empty_value = null ) {
				if ( empty( $this->settings ) ) {
					$this->init_settings();
				}
				return $this->settings[ $key ] ?? $empty_value;
			}

			public function update_option( $key, $value = '' ): bool {
				$this->settings[ $key ] = $value;
				return true;
			}

			public function init_form_fields() {}

			public function get_return_url(): string {
				return 'https://example.com/checkout/order-received/123';
			}

			public function is_available() {
				return 'yes' === $this->enabled;
			}

			public function supports( $feature ): bool {
				return in_array( $feature, $this->supports, true );
			}

			public function can_refund_order( $order ) {
				return $this->supports( 'refunds' );
			}

			public function process_payment( $order_id ) {
				return array( 'result' => 'success', 'redirect' => '' );
			}

			public function process_refund( $order_id, $amount = null, $reason = '' ) {
				return false;
			}

			public function payment_fields() {}

			public function validate_fields() {
				return true;
			}

			public function admin_options() {}
		}
	}

	if ( ! class_exists( 'WC_Payment_Token' ) ) {
		class WC_Payment_Token {
			protected int $id = 0;
			protected string $token = '';
			protected $type = '';
			protected int $user_id = 0;
			protected string $gateway_id = '';
			protected bool $is_default = false;
			protected array $meta_data = array();
			protected $extra_data = array();

			public function get_prop( $prop ) {
				return $this->extra_data[ $prop ] ?? ( $this->$prop ?? null );
			}

			public function set_prop( $prop, $value ) {
				$this->extra_data[ $prop ] = $value;
			}

			public function get_id(): int { return $this->id; }
			public function set_id( $id ) { $this->id = $id; }
			public function get_token(): string { return $this->token; }
			public function set_token( $token ) { $this->token = $token; }
			public function get_type() { return $this->type; }
			public function set_type( $type ) { $this->type = $type; }
			public function get_user_id(): int { return $this->user_id; }
			public function set_user_id( $user_id ) { $this->user_id = $user_id; }
			public function get_gateway_id(): string { return $this->gateway_id; }
			public function set_gateway_id( $gateway_id ) { $this->gateway_id = $gateway_id; }
			public function is_default(): bool { return $this->is_default; }
			public function set_default( $is_default ) { $this->is_default = (bool) $is_default; }

			public function get_meta( $key ) {
				return $this->meta_data[ $key ] ?? '';
			}

			public function add_meta_data( $key, $value ) {
				$this->meta_data[ $key ] = $value;
			}

			public function update_meta_data( $key, $value ) {
				$this->meta_data[ $key ] = $value;
			}

			public function save(): int { return $this->id ?: 1; }
			public function read( $id ) { $this->id = $id; }
		}
	}

	if ( ! class_exists( 'WC_Payment_Token_CC' ) ) {
		class WC_Payment_Token_CC extends WC_Payment_Token {
			protected $type = 'CC';
			protected string $card_type = '';
			protected string $last4 = '';
			protected string $expiry_month = '';

			public function get_card_type(): string { return $this->card_type; }

			public function get_last4(): string { return $this->last4; }

			public function get_expiry_month(): string { return $this->expiry_month; }
		}
	}

	if ( ! class_exists( 'WC_Payment_Tokens' ) ) {
		class WC_Payment_Tokens {
			public static function get_customer_tokens(): array { return array(); }
			public static function get( $token_id ) { return null; }
			public static function delete( $token_id ) {}
			public static function set_users_default( $user_id, $token_id ) {}
		}
	}

	if ( ! class_exists( 'WC_Order' ) ) {
		class WC_Order {
			protected $id = 0;
			protected string $status = 'pending';
			protected string $total = '100.00';
			protected string $currency = 'EUR';
			protected array $meta_data = array();
			protected string $payment_method = 'monri';
			protected string $transaction_id = '';
			protected array $billing = array();

			public function __construct( $id = 0 ) {
				$this->id = $id;
			}

			public function get_id() { return $this->id; }
			public function get_status(): string { return $this->status; }
			public function get_total(): string { return $this->total; }
			public function set_total( $total ) { $this->total = $total; }
			public function get_currency(): string { return $this->currency; }
			public function set_currency( $currency ) { $this->currency = $currency; }
			public function get_payment_method(): string { return $this->payment_method; }
			public function set_payment_method( $method ) { $this->payment_method = $method; }
			public function get_transaction_id(): string { return $this->transaction_id; }
			public function set_transaction_id( $id ) { $this->transaction_id = $id; }
			public function get_user_id(): int { return 1; }
			public function get_meta( $key ) {
				return $this->meta_data[ $key ] ?? '';
			}
			public function add_meta_data( $key, $value, $unique = false ) {
				$this->meta_data[ $key ] = $value;
			}
			public function update_meta_data( $key, $value ) {
				$this->meta_data[ $key ] = $value;
			}
			public function save_meta_data() {
				return true;
			}
			public function update_status( $new_status ): bool {
				$this->status = $new_status;
				return true;
			}
			public function payment_complete( $transaction_id = '' ): bool {
				$this->status = 'processing';
				$this->transaction_id = $transaction_id;
				return true;
			}
			public function add_order_note( $note, $is_customer_note = 0, $added_by_user = false ) {}
			public function get_cancel_order_url(): string {
				return 'https://example.com/cancel-order';
			}
			public function get_checkout_payment_url(): string {
				return 'https://example.com/checkout/pay/' . $this->id;
			}
			public function get_billing_first_name() { return $this->billing['first_name'] ?? 'John'; }
			public function get_billing_last_name() { return $this->billing['last_name'] ?? 'Doe'; }
			public function get_billing_email() { return $this->billing['email'] ?? 'john@example.com'; }
			public function get_billing_phone() { return $this->billing['phone'] ?? '123456789'; }
			public function get_billing_country() { return $this->billing['country'] ?? 'HR'; }
			public function get_billing_city() { return $this->billing['city'] ?? 'Zagreb'; }
			public function get_billing_address_1() { return $this->billing['address_1'] ?? 'Ilica 1'; }
			public function get_billing_postcode() { return $this->billing['postcode'] ?? '10000'; }
			public function save() { return $this->id; }
			public function needs_payment(): bool {
				return in_array( $this->status, array( 'pending', 'failed' ), true );
			}
		}
	}

	if ( ! class_exists( 'WC_Cart_Fees' ) ) {
		class WC_Cart_Fees {
			public array $fees = array();

			public function add_fee( $fee_data ) {
				$this->fees[] = $fee_data;
			}

			public function get_fees(): array {
				return $this->fees;
			}
		}
	}

	if ( ! class_exists( 'WC_Cart' ) ) {
		class WC_Cart {
			public array $fees = array();
			public $fees_api = null;
			public array $cart_contents = array();
			public float $total = 0;
			public float $subtotal = 0;
			public float $fee_total = 0;

			public function fees_api(): WC_Cart_Fees {
				if ( is_null( $this->fees_api ) ) {
					$this->fees_api = new WC_Cart_Fees();
				}
				return $this->fees_api;
			}

			public function get_fees(): array {
				return $this->fees;
			}

			public function get_total() {
				return $this->total;
			}

			public function set_total( $total ) {
				$this->total = $total;
			}

			public function get_subtotal() {
				return $this->subtotal;
			}

			public function get_fee_total() {
				return $this->fee_total;
			}

			public function set_fee_total( $fee_total ) {
				$this->fee_total = $fee_total;
			}
		}
	}

	if ( ! class_exists( 'WC_Session' ) ) {
		class WC_Session {
			protected array $data = array();

			public function get( $key, $default = null ) {
				return $this->data[ $key ] ?? $default;
			}

			public function set( $key, $value ) {
				$this->data[ $key ] = $value;
			}

			public function __get( $key ) {
				return $this->get( $key );
			}

			public function __set( $key, $value ) {
				$this->set( $key, $value );
			}

			public function __isset( $key ) {
				return isset( $this->data[ $key ] );
			}

			public function __unset( $key ) {
				unset( $this->data[ $key ] );
			}
		}
	}

	if ( ! class_exists( 'WC_Logger' ) ) {
		class WC_Logger {
			public array $logs = array();

			public function add( $handle, $message, $level = 'info' ): bool {
				$this->logs[] = compact( 'handle', 'message', 'level' );
				return true;
			}
			public function debug( $message ) { $this->add( 'monri', $message, 'debug' ); }
			public function info( $message ) { $this->add( 'monri', $message, 'info' ); }
			public function notice( $message ) { $this->add( 'monri', $message, 'notice' ); }
			public function warning( $message ) { $this->add( 'monri', $message, 'warning' ); }
			public function error( $message ) { $this->add( 'monri', $message, 'error' ); }
			public function critical( $message ) { $this->add( 'monri', $message, 'critical' ); }
			public function alert( $message ) { $this->add( 'monri', $message, 'alert' ); }
			public function emergency( $message ) { $this->add( 'monri', $message, 'emergency' ); }
			public function log( $level, $message ) { $this->add( 'monri', $message, $level ); }
		}
	}

	if ( ! function_exists( '__' ) ) {
		function __( $text, $domain = 'default' ) {
			return $text;
		}
	}

	if ( ! function_exists( '_e' ) ) {
		function _e( $text, $domain = 'default' ) {
			echo $text;
		}
	}

	if ( ! function_exists( '_x' ) ) {
		function _x( $text, $context, $domain = 'default' ) {
			return $text;
		}
	}

	if ( ! function_exists( '_n' ) ) {
		function _n( $single, $plural, $number, $domain = 'default' ) {
			return $number === 1 ? $single : $plural;
		}
	}

	if ( ! function_exists( 'esc_html__' ) ) {
		function esc_html__( $text, $domain = 'default' ) {
			return $text;
		}
	}

	if ( ! function_exists( 'esc_html_e' ) ) {
		function esc_html_e( $text, $domain = 'default' ) {
			echo $text;
		}
	}

	if ( ! function_exists( 'esc_attr__' ) ) {
		function esc_attr__( $text, $domain = 'default' ) {
			return $text;
		}
	}

	if ( ! function_exists( 'esc_attr_e' ) ) {
		function esc_attr_e( $text, $domain = 'default' ) {
			echo $text;
		}
	}

	if ( ! function_exists( 'esc_html' ) ) {
		function esc_html( $text ): string {
			return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
		}
	}

	if ( ! function_exists( 'esc_attr' ) ) {
		function esc_attr( $text ): string {
			return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
		}
	}

	if ( ! function_exists( 'esc_url' ) ) {
		function esc_url( $url ) {
			return filter_var( $url, FILTER_SANITIZE_URL ) ?: $url;
		}
	}

	if ( ! function_exists( 'sanitize_text_field' ) ) {
		function sanitize_text_field( $str ): string {
			return is_scalar( $str ) ? strip_tags( trim( (string) $str ) ) : '';
		}
	}

	if ( ! function_exists( 'wp_kses_post' ) ) {
		function wp_kses_post( $data ) {
			return $data;
		}
	}

	if ( ! function_exists( 'wp_unslash' ) ) {
		function wp_unslash( $value ) {
			return is_string( $value ) ? stripslashes( $value ) : $value;
		}
	}

	if ( ! function_exists( 'wp_json_encode' ) ) {
		function wp_json_encode( $data, $options = 0, $depth = 512 ) {
			return json_encode( $data, $options, $depth );
		}
	}

	if ( ! function_exists( 'is_wp_error' ) ) {
		function is_wp_error( $thing ): bool {
			return $thing instanceof WP_Error;
		}
	}

	if ( ! function_exists( 'wc_get_is_paid_statuses' ) ) {
		function wc_get_is_paid_statuses(): array {
			return array( 'processing', 'completed' );
		}
	}

	if ( ! function_exists( 'wc_get_credit_card_type_label' ) ) {
		function wc_get_credit_card_type_label( $type ): string {
			return ucwords( (string) $type );
		}
	}

	if ( ! function_exists( 'wc_get_logger' ) ) {
		function wc_get_logger(): WC_Logger {
			static $logger = null;
			if ( null === $logger ) {
				$logger = new WC_Logger();
			}
			return $logger;
		}
	}

	if ( ! class_exists( 'WC_Payment_Gateways' ) ) {
		class WC_Payment_Gateways {
			public function payment_gateways(): array {
				return array(
					'monri' => new Monri_WC_Gateway(),
					'monri_components_apple_pay' => new \Monri_WC_Gateway_Webpay_Components_Apple_Pay(),
					'monri_components_google_pay' => new \Monri_WC_Gateway_Webpay_Components_Google_Pay(),
					'monri_components_keks_pay' => new \Monri_WC_Gateway_Webpay_Components_Keks_Pay(),
					'monri_components_pay_cek' => new \Monri_WC_Gateway_Webpay_Components_Pay_Cek(),
				);
			}
		}
	}

	if ( ! function_exists( 'get_option' ) ) {
		function get_option( $option, $default = false ) {
			global $test_wp_options;
			return $test_wp_options[ $option ] ?? $default;
		}
	}

	if ( ! function_exists( 'add_option' ) ) {
		function add_option( $option, $value = '' ): bool {
			global $test_wp_options;
			if ( ! isset( $test_wp_options[ $option ] ) ) {
				$test_wp_options[ $option ] = $value;
				return true;
			}
			return false;
		}
	}

	if ( ! function_exists( 'update_option' ) ) {
		function update_option( $option, $value ): bool {
			global $test_wp_options;
			$test_wp_options[ $option ] = $value;
			return true;
		}
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		class WooCommerce {
			public WC_Cart $cart;
			public WC_Session $session;
			public WC_Payment_Gateways $gateways;

			public function __construct() {
				$this->cart = new WC_Cart();
				$this->session = new WC_Session();
				$this->gateways = new WC_Payment_Gateways();
			}

			public function payment_gateways(): WC_Payment_Gateways {
				return $this->gateways;
			}
		}
	}

	if ( ! function_exists( 'WC' ) ) {
		function WC(): WooCommerce {
			static $instance = null;
			if ( null === $instance ) {
				$instance = new WooCommerce();
			}
			return $instance;
		}
	}

	if ( ! function_exists( 'wc_get_order' ) ) {
		function wc_get_order( $the_order = false ) {
			global $test_wc_orders;
			if ( is_object( $the_order ) && is_a( $the_order, 'WC_Order' ) ) {
				return $the_order;
			}
			if ( is_numeric( $the_order ) ) {
				$order_id = (int) $the_order;
				if ( isset( $test_wc_orders[ $order_id ] ) ) {
					return $test_wc_orders[ $order_id ];
				}
				$order = new WC_Order( $order_id );
				$test_wc_orders[ $order_id ] = $order;
				return $order;
			}
			return false;
		}
	}

	if ( ! function_exists( 'wc_add_notice' ) ) {
		function wc_add_notice( $message, $notice_type = 'success', $data = array() ) {}
	}

	if ( ! function_exists( 'status_header' ) ) {
		function status_header( $code, $description = '' ) {}
	}

	if ( ! function_exists( 'sanitize_textarea_field' ) ) {
		function sanitize_textarea_field( $str ): string {
			return sanitize_text_field( $str );
		}
	}

	if ( ! function_exists( 'is_admin' ) ) {
		function is_admin(): bool {
			return false;
		}
	}

	if ( ! function_exists( 'admin_url' ) ) {
		function admin_url( $path = '' ): string {
			return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
		}
	}

	if ( ! function_exists( 'get_home_url' ) ) {
		function get_home_url( $blog_id = null, $path = '' ): string {
			return 'https://example.com' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
		}
	}

	if ( ! function_exists( 'home_url' ) ) {
		function home_url( $path = '' ): string {
			return 'https://example.com' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
		}
	}

	if ( ! function_exists( 'site_url' ) ) {
		function site_url( $path = '' ): string {
			return 'https://example.com' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
		}
	}

	if ( ! function_exists( 'plugins_url' ) ) {
		function plugins_url( $path = '' ): string {
			return 'https://example.com/wp-content/plugins/' . ltrim( $path, '/' );
		}
	}

	if ( ! function_exists( 'wp_enqueue_script' ) ) {
		function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {}
	}

	if ( ! function_exists( 'plugin_dir_url' ) ) {
		function plugin_dir_url(): string {
			return 'https://example.com/wp-content/plugins/monri/';
		}
	}

	if ( ! function_exists( 'plugin_dir_path' ) ) {
		function plugin_dir_path( $file ): string {
			return dirname( $file ) . '/';
		}
	}

	if ( ! function_exists( 'plugin_basename' ) ) {
		function plugin_basename( $file ): string {
			return basename( dirname( $file ) ) . '/' . basename( $file );
		}
	}

	if ( ! function_exists( 'add_action' ) ) {
		function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ): bool {
			if ( is_null( $priority ) ) {
				$priority = 10;
			}
			$args = [ $callback, $priority, $accepted_args ];
			$container = \Brain\Monkey\Container::instance();
			$container->hookStorage()->pushToAdded( \Brain\Monkey\Hook\HookStorage::ACTIONS, $hook_name, $args );
			$container->hookExpectationExecutor()->executeAddAction( $hook_name, $args );

			return true;
		}
	}

	if ( ! function_exists( 'add_filter' ) ) {
		function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ): bool {
			if ( is_null( $priority ) ) {
				$priority = 10;
			}
			$args = [ $callback, $priority, $accepted_args ];
			$container = \Brain\Monkey\Container::instance();
			$container->hookStorage()->pushToAdded( \Brain\Monkey\Hook\HookStorage::FILTERS, $hook_name, $args );
			$container->hookExpectationExecutor()->executeAddFilter( $hook_name, $args );

			return true;
		}
	}

	if ( ! function_exists( 'get_woocommerce_currency' ) ) {
		function get_woocommerce_currency(): string {
			return 'EUR';
		}
	}

	if ( ! function_exists( 'wc_trim_string' ) ) {
		function wc_trim_string( $string, $count = 0, $more = '...' ): string {
			if ( $count > 0 && mb_strlen( (string) $string ) > $count ) {
				return mb_substr( (string) $string, 0, $count ) . $more;
			}
			return (string) $string;
		}
	}

	if ( ! function_exists( 'wc_get_template' ) ) {
		function wc_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {}
	}

	if ( ! function_exists( 'wc_price' ) ) {
		function wc_price( $price, $args = array() ) {
			return number_format( (float) $price, 2 ) . ' ' . ( $args['currency'] ?? 'EUR' );
		}
	}

	if ( ! function_exists( 'add_query_arg' ) ) {
		function add_query_arg( ...$args ): string {
			if ( is_array( $args[0] ) ) {
				$params = $args[0];
				$url = $args[1] ?? '';
			} else {
				$params = array( $args[0] => $args[1] );
				$url = $args[2] ?? '';
			}
			$query = http_build_query( $params );
			return $url . ( strpos( $url, '?' ) !== false ? '&' : '?' ) . $query;
		}
	}

	if ( ! function_exists( 'wp_script_is' ) ) {
		function wp_script_is(): bool {
			return false;
		}
	}

	if ( ! function_exists( 'wp_register_script' ) ) {
		function wp_register_script(): bool {
			return true;
		}
	}

	if ( ! function_exists( 'wp_set_script_translations' ) ) {
		function wp_set_script_translations( $handle, $domain = 'default', $path = null ): bool {
			return true;
		}
	}

	if ( ! function_exists( 'is_checkout' ) ) {
		function is_checkout(): bool {
			return false;
		}
	}

	if ( ! function_exists( 'is_checkout_pay_page' ) ) {
		function is_checkout_pay_page(): bool {
			return false;
		}
	}

	if ( ! function_exists( 'is_cart' ) ) {
		function is_cart(): bool {
			return false;
		}
	}

	if ( ! function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
		function woocommerce_store_api_register_update_callback( $params ) {}
	}

	if ( ! function_exists( 'add_option' ) ) {
		function add_option( $option, $value = '', $deprecated = '', $autoload = 'yes' ): bool {
			return true;
		}
	}

	if ( ! function_exists( 'update_option' ) ) {
		function update_option( $option, $value, $autoload = null ): bool {
			return true;
		}
	}

	if ( ! function_exists( 'register_activation_hook' ) ) {
		function register_activation_hook( $file, $callback ) {}
	}
}
