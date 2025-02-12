<?php
/**
 * Fired during plugin activation
 *
 * @package Chocante_Delivery_Point
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Chocante_Delivery_Point class.
 */
class Chocante_Delivery_Point {
	const DELIVERY_POINT   = 'chocante_delivery_point';
	const DELIVERY_ADDRESS = 'chocante_delivery_point_address';

	/**
	 * This class instance.
	 *
	 * @var \Chocante_Delivery_Point Single instance of this class.
	 */
	private static $instance;

	/**
	 * The current version of the plugin.
	 *
	 * @var string The current version of the plugin.
	 */
	protected $version;

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( defined( 'CHOCANTE_DELIVERY_POINT_VERSION' ) ) {
			$this->version = CHOCANTE_DELIVERY_POINT_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		$this->init();

		Chocante_Inpost::init();
	}

	/**
	 * Cloning is forbidden
	 */
	public function __clone() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'chocante-delivery-point' ), $this->version );
	}

	/**
	 * Unserializing instances of this class is forbidden
	 */
	public function __wakeup() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'chocante-delivery-point' ), $this->version );
	}

	/**
	 * Gets the main instance.
	 *
	 * Ensures only one instance can be loaded.
	 *
	 * @return \Chocante_Delivery_Point
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks
	 */
	private function init() {
		// Add fields to shipping methods.
		add_action( 'woocommerce_init', array( $this, 'register_shipping_methods_fields' ) );

		// Include assets.
		add_action( 'wp_footer', array( $this, 'include_widgets' ) );

		// Display widgets.
		add_action( 'woocommerce_review_order_before_order_total', array( $this, 'display_delivery_point_selection' ) );

		// Save data in session.
		add_action( 'wp_ajax_chocante_delivery_point_save', array( $this, 'ajax_delivery_point_save' ) );
		add_action( 'wp_ajax_nopriv_chocante_delivery_point_save', array( $this, 'ajax_delivery_point_save' ) );

		// Validate in checkout.
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_checkout' ) );

		// Save in order meta.
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_in_order' ) );

		// Display in order summary.
		add_filter( 'woocommerce_order_get_formatted_shipping_address', array( $this, 'display_in_order' ), 10, 3 );

		// Include in Rest API.
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'add_to_rest' ), 10, 2 );
	}


	/**
	 * Add delivery time field to shipping method settings
	 *
	 * @param array $fields Shipping method form fields.
	 * @return array
	 */
	public function delivery_point_field( $fields ) {
		$delivery_options = apply_filters(
			'chocante_delivery_point_options',
			array(
				'' => __( 'Disabled', 'chocante-delivery-point' ),
			)
		);

		$add_fields = array(
			self::DELIVERY_POINT => array(
				'title'   => __( 'Set delivery point', 'chocante-delivery-point' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => '',
				'options' => $delivery_options,
			),
		);

		return array_merge( $fields, $add_fields );
	}

	/**
	 * Add delivery time field to all shipping methods
	 */
	public function register_shipping_methods_fields() {
		$shipping_methods = WC()->shipping->get_shipping_methods();

		foreach ( $shipping_methods as $shipping_method ) {
			add_filter( 'woocommerce_shipping_instance_form_fields_' . $shipping_method->id, array( $this, 'delivery_point_field' ) );
		}
	}

	/**
	 * Include delivery point widgets
	 */
	public function include_widgets() {
		if ( ! is_checkout() ) {
			return;
		}

		do_action( 'chocante_delivery_point_widgets' );
	}

	/**
	 * Validate delivery point in checkout
	 */
	public function validate_checkout() {
		$delivery_point = WC()->session->get( self::DELIVERY_POINT );

		if ( isset( $delivery_point ) && ! isset( $delivery_point['number'] ) ) {
			wc_add_notice( esc_html__( 'Please select your delivery point', 'chocante-delivery-point' ), 'error' );
		}
	}

	/**
	 * Save selected delivery point to session
	 *
	 * @param string $number Number of delivery point.
	 * @param string $address Delivery point address.
	 */
	private function delivery_point_save( $number, $address ) {
		$delivery_point_data = WC()->session->get( self::DELIVERY_POINT );

		$delivery_point = array(
			'provider' => $delivery_point_data['provider'],
			'number'   => $number,
			'address'  => $address,
		);

		WC()->session->set( self::DELIVERY_POINT, $delivery_point );
	}

	/**
	 * Ajax handle delivery point selection
	 */
	public function ajax_delivery_point_save() {
		check_ajax_referer( self::DELIVERY_POINT );

		$number  = isset( $_POST['number'] ) ? sanitize_text_field( wp_unslash( $_POST['number'] ) ) : null;
		$address = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : null;

		if ( isset( $number ) && isset( $address ) ) {
			$this->delivery_point_save( $number, $address );
			wp_die();
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Display delivery point selection in checkout
	 */
	public function display_delivery_point_selection() {
		$current_shipping_method = WC()->session->get( 'chosen_shipping_methods' );
		$packages                = WC()->shipping->get_packages();
		$shipping_class_names    = WC()->shipping->get_shipping_method_class_names();
		$package                 = $packages[0];
		$available_methods       = $package['rates'];

		foreach ( $available_methods as $method ) {
			if ( $current_shipping_method[0] === $method->id ) {
				$method_instance = new $shipping_class_names[ $method->get_method_id() ]( $method->get_instance_id() );
				break;
			}
		}

		if ( ! isset( $method_instance ) ) {
			return;
		}

		$delivery_point = $method_instance->get_instance_option( self::DELIVERY_POINT );

		if ( empty( $delivery_point ) ) {
			WC()->session->__unset( self::DELIVERY_POINT );
			return;
		}

		$delivery_point_data = WC()->session->get( self::DELIVERY_POINT );

		if ( ! empty( $delivery_point_data['provider'] ) && $delivery_point_data['provider'] !== $delivery_point ) {
			$delivery_point_data = array();
		}

		$delivery_point_data['provider'] = $delivery_point;
		WC()->session->set( self::DELIVERY_POINT, $delivery_point_data );

		do_action( 'chocante_delivery_point_selection', $delivery_point, $this->get_delivery_point( $delivery_point_data ), $this->get_delivery_point_address( $delivery_point_data ) );
	}

	/**
	 * Get delivery point number
	 *
	 * @param array $delivery_point_data Selected delivery point data.
	 * @return string
	 */
	private function get_delivery_point( $delivery_point_data ) {
		return isset( $delivery_point_data['number'] ) ? $delivery_point_data['number'] : '';
	}

	/**
	 * Get delivery point address
	 *
	 * @param array $delivery_point_data Selected delivery point data.
	 * @return string
	 */
	private function get_delivery_point_address( $delivery_point_data ) {
		return isset( $delivery_point_data['address'] ) ? $delivery_point_data['address'] : '';
	}

	/**
	 * Save delivery point in order
	 *
	 * @param mixed $order_id Post ID of processed order.
	 */
	public function save_in_order( $order_id ) {
		$delivery_point = WC()->session->get( self::DELIVERY_POINT );

		if ( isset( $delivery_point['number'] ) && isset( $delivery_point['address'] ) ) {
			$order = wc_get_order( $order_id );

			$order->update_meta_data( self::DELIVERY_POINT, sanitize_text_field( $delivery_point['number'] ) );
			$order->update_meta_data( self::DELIVERY_ADDRESS, sanitize_text_field( $delivery_point['address'] ) );

			$order->save_meta_data();
		}
	}

	/**
	 * Display delivery point in order shipping address
	 *
	 * @param string   $address Order address.
	 * @param array    $raw_address Order address array.
	 * @param WC_Order $order Current order.
	 * @return string
	 */
	public function display_in_order( $address, $raw_address, $order ) {
		$delivery_point         = $order->get_meta( self::DELIVERY_POINT );
		$delivery_point_address = $order->get_meta( self::DELIVERY_ADDRESS );
		$shipping_method        = $order->get_shipping_method();

		if ( ! empty( $delivery_point ) && ! empty( $delivery_point_address ) && ! empty( $address ) ) {
			$address .= '<br /><br />';
			// translators: Delivery point info.
			$address .= esc_html__( 'Delivery Point', 'chocante-delivery-point' ) . ':';
			$address .= '<br />';
			$address .= "{$shipping_method} {$delivery_point} ({$delivery_point_address})";
		}

		return $address;
	}

	/**
	 * Add delivery point data to REST API response
	 *
	 * @param mixed    $response REST API response.
	 * @param WC_Order $order Order data.
	 * @return mixed
	 */
	public function add_to_rest( $response, $order ) {
		$delivery_point = $order->get_meta( self::DELIVERY_POINT );

		if ( ! empty( $delivery_point ) ) {
			$response->data[ self::DELIVERY_POINT ] = $delivery_point;
		}

		return $response;
	}
}
