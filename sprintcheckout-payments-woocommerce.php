<?php
/**
 * Plugin Name: Sprintcheckout
 * Plugin URI: https://www.sprintcheckout.com
 * Author: Sprintcheckout
 * Author URI: https://www.sprintcheckout.com
 * Description: Accept crypto payments on Woocommerce at scale, thanks to the first Ethereum Layer 2
 * Version: 2.0.0
 * License: GPL2
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: sprintcheckout
 * 
 * Class WC_Gateway_Sprintcheckout file.
 *
 * @package WooCommerce\Sprintcheckout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

//if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'sprintcheckout_payment_init', 11 );
add_filter( 'woocommerce_payment_gateways', 'add_to_woo_sprintcheckout_payment_gateway');

function sprintcheckout_payment_init() {
    if( class_exists( 'WC_Payment_Gateway' ) ) {
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-payment-gateway-sprintcheckout.php';
	}
}

function add_to_woo_sprintcheckout_payment_gateway( $gateways ) {
    $gateways[] = 'WC_Gateway_Sprintcheckout';
    return $gateways;
}