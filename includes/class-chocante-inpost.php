<?php
/**
 * InPost integration
 *
 * @package Chocante_Delivery_Point
 */

defined( 'ABSPATH' ) || exit;

/**
 * The ChocanteOmnibus class.
 */
class Chocante_Inpost {
	const WIDGET_LANGUAGES   = array( 'pl', 'en', 'uk' );
	const DEFAULT_LANGUAGE   = 'pl';
	const SECONDARY_LANGUAGE = 'en';

	/**
	 * InPost delivery methods
	 *
	 * @var array $methods Delivery method properties.
	 */
	public static $methods = array();

	/**
	 * Register hooks
	 */
	public static function init() {
		self::$methods = array(
			'inpost'         => array(
				'label'  => __( 'InPost Parcel Locker', 'chocante-delivery-point' ),
				'config' => 'parcelCollect',
			),
			'inpost_cod'     => array(
				'label'  => __( 'InPost Parcel Locker (Payment on Delivery)', 'chocante-delivery-point' ),
				'config' => 'parcelCollectPayment',
			),
			'inpost_weekend' => array(
				'label'  => __( 'InPost Parcel Locker (Weekend Parcel Service)', 'chocante-delivery-point' ),
				'config' => 'parcelCollect247',
			),
		);

		add_filter( 'chocante_delivery_point_options', array( self::class, 'add_delivery_method' ) );
		add_action( 'chocante_delivery_point_widgets', array( self::class, 'add_widget_assets' ) );
		add_action( 'chocante_delivery_point_selection', array( self::class, 'display_point_selection' ), 10, 3 );
	}

	/**
	 * Add option to delivery point field
	 *
	 * @param array $options Delivery point options.
	 * @return array
	 */
	public static function add_delivery_method( $options ) {
		$fields = array();

		foreach ( self::$methods as $key => $method ) {
			$fields[ $key ] = $method['label'];
		}

		return array_merge( $options, $fields );
	}

	/**
	 * Include widget
	 */
	public static function add_widget_assets() {
		wp_enqueue_style( 'inpost-geowidget-style', 'https://geowidget.inpost.pl/inpost-geowidget.css', array(), '1.0.0' );

		wp_enqueue_script(
			'inpost-geowidget',
			'https://geowidget.inpost.pl/inpost-geowidget.js',
			array(),
			'1.0.0',
			array(
				'strategy'  => 'async',
				'in_footer' => true,
			)
		);

		$script_asset = include plugin_dir_path( __DIR__ ) . 'build/js/chocante-inpost.asset.php';

		wp_enqueue_script(
			'chocante-inpost',
			plugin_dir_url( __DIR__ ) . 'build/js/chocante-inpost.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			array(
				'strategy'  => 'defer',
				'in_footer' => 'true',
			)
		);

		wp_localize_script(
			'chocante-inpost',
			'chocante_inpost',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'chocante_delivery_point' ),
			)
		);

		$modal_styles = include plugin_dir_path( __DIR__ ) . 'build/css/chocante-modal.asset.php';

		wp_enqueue_style(
			'chocante-modal',
			plugin_dir_url( __DIR__ ) . 'build/css/chocante-modal.css',
			$modal_styles['dependencies'],
			$modal_styles['version']
		);

		$inpost_styles = include plugin_dir_path( __DIR__ ) . 'build/css/chocante-modal.asset.php';

		wp_enqueue_style(
			'chocante-inpost',
			plugin_dir_url( __DIR__ ) . 'build/css/chocante-inpost.css',
			array_merge( $inpost_styles['dependencies'], array( 'jquery', 'selectWoo' ) ),
			$inpost_styles['version']
		);

		require_once plugin_dir_path( __FILE__ ) . 'inpost-modal.php';
	}

	/**
	 * Get widget language
	 *
	 * @return string
	 */
	private static function get_widget_language() {
		$current_language = self::get_current_language();
		$widget_language  = in_array( $current_language, self::WIDGET_LANGUAGES, true ) ? $current_language : self::SECONDARY_LANGUAGE;

		return $widget_language;
	}

	/**
	 * Get current language
	 *
	 * @return string
	 */
	private static function get_current_language() {
		return has_filter( 'wpml_current_language' ) ? apply_filters( 'wpml_current_language', null ) : self::DEFAULT_LANGUAGE;
	}

	/**
	 * Get widget config
	 *
	 * @param array|null $delivery_point Selected delivery point data.
	 * @return string
	 */
	private static function get_widget_config( $delivery_point ) {
		return self::$methods[ $delivery_point ]['config'];
	}

	/**
	 * Display delivery point selection in checkout
	 *
	 * @param string $delivery_point Name of delivery point service.
	 * @param string $delivery_point_number Selected delivery point number.
	 * @param string $delivery_point_address Address of selected delivery point.
	 */
	public static function display_point_selection( $delivery_point, $delivery_point_number, $delivery_point_address ) { // @codingStandardsIgnoreLine.
		if ( in_array( $delivery_point, array_keys( self::$methods ), true ) ) {
			// @codingStandardsIgnoreStart.
			$widget_language = self::get_widget_language();
			$widget_config   = self::get_widget_config( $delivery_point );
			// @codingStandardsIgnoreEnd.
			$current_language = self::get_current_language();

			include plugin_dir_path( __FILE__ ) . 'inpost-delivery-point.php';
		}
	}
}
