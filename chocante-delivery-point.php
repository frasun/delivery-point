<?php
/**
 * Plugin Name: Delivery Point
 * Description: Choose delivery point for shipping methods (InPost).
 * Version: 1.0.0
 * Author: Chocante
 * Text Domain: chocante-delivery-point
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Chocante_Delivery_Point
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'MAIN_PLUGIN_FILE' ) ) {
	define( 'MAIN_PLUGIN_FILE', __FILE__ );
}

/**
 * Current plugin version.
 */
define( 'CHOCANTE_DELIVERY_POINT_VERSION', '1.0.0' );

require_once plugin_dir_path( __FILE__ ) . '/includes/class-chocante-delivery-point.php';
require_once plugin_dir_path( __FILE__ ) . '/includes/class-chocante-inpost.php';

/**
 * Init plugin instance.
 */
function chocante_delivery_point_init() {
	Chocante_Delivery_Point::instance();
}

add_action( 'plugins_loaded', 'chocante_delivery_point_init', 10 );

/**
 * Load text domain
 */
function chocante_delivery_point_load_textdomain() {
	load_plugin_textdomain( 'chocante-delivery-point', false, plugin_basename( __DIR__ ) . '/languages' );
}

add_action( 'init', 'chocante_delivery_point_load_textdomain', 10 );

/**
 * Activation hook
 */
function chocante_delivery_point_activate() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'chocante_delivery_point_missing_wc_notice' );
		return;
	}
}

register_activation_hook( __FILE__, 'chocante_delivery_point_activate' );

/**
 * WooCommerce fallback notice
 */
function chocante_delivery_point_missing_wc_notice() {
	/* translators: %s WC download URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Delivery Point requires WooCommerce to be installed and active. You can download %s here.', 'chocante-delivery-point' ), '<a href="https://woo.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}
