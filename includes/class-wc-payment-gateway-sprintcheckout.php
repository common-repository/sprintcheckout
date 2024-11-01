<?php

/**
 * Sprintcheckout Payments Gateway.
 *
 * Provides a Sprintcheckout Payments Gateway.
 *
 * @class       WC_Gateway_Sprintcheckout
 * @extends     WC_Payment_Gateway
 * @version     2.1.0
 * @package     WooCommerce/Classes/Payment
 */
class WC_Gateway_Sprintcheckout extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		// Setup general properties.
		$this->setup_properties();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Get settings.
		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->api_key            = $this->get_option( 'api_key' );
		$this->instructions       = $this->get_option( 'instructions' );
		$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
		$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        if (!function_exists('write_log')) {
            function write_log($log) {
                if (true === WP_DEBUG) {
                    if (is_array($log) || is_object($log)) {
                        error_log(print_r($log, true));
                    } else {
                        error_log($log);
                    }
                }
            }
        }
	}

	/**
	 * Setup general properties for the gateway.
	 */
	protected function setup_properties() {
		$this->id                 = 'sprintcheckout';
//		$this->icon               = apply_filters( 'woocommerce_sprintcheckout_icon', plugins_url('../assets/icon.png', __FILE__ ) );
		$this->method_title       = __( 'Sprintcheckout Payment', 'sprintcheckout-payments-woo' );
		$this->api_key            = __( 'Add API Key', 'sprintcheckout-payments-woo' );
		$this->method_description = __( 'Accept crypto payments with Sprintcheckout.', 'sprintcheckout-payments-woo' );
		$this->has_fields         = false;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'            => array(
				'title'       => __( 'Enable/Disable', 'sprintcheckout-payments-woo' ),
				'label'       => __( 'Enable Sprintcheckout', 'sprintcheckout-payments-woo' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
            'title' => array(
                'title'             => __( 'Title', 'woocommerce' ),
                'type'              => 'text',
                'default'           => __( 'Pay with crypto (Sprintcheckout)', 'woocommerce' ),
                'desc_tip'          => false,
                'custom_attributes' => array(
                    'readonly'      => 'readonly',
                ),
            ),
			'api_key'         => array(
				'title'       => __( 'API Key', 'sprintcheckout-payments-woo' ),
				'type'        => 'text',
				'description' => __( 'Get your API key from <a href="https://dashboard.sprintcheckout.com/#/admin/get-paid" target="_blank">dashboard.sprintcheckout.com/get-paid</a>', 'sprintcheckout-payments-woo' ),
				'desc_tip'    => false,
			),
		);
	}


	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			return $this->sprintcheckout_payment_processing( $order );
		}
	}

	private function sprintcheckout_payment_processing( $order ) {

		$total = intval( $order->get_total() );
		//var_dump($total);
        // Get Api Key from where is stored in woocommerce / wordpress
        $api_key_from_woocommerce_plugin = $this->api_key;

        $orderId  = $order->get_id(); // Get the order ID
        $currency = $order->get_currency(); // Get the currency used
        $order_data = $order->get_data(); // The Order data
        $order_total = $order_data['total'];
        //Alternative? WC()->cart->total;

        $data = array(
            "orderId" => $orderId,
            "amount" => $order_total,
            "currency" => $currency,
            "successUrl" => get_site_url() . "/checkout/order-received/" . $orderId,
            "failUrl" => get_site_url() . "/checkout/failed/",
            "cancelUrl" => get_site_url() . "/checkout/"
        );

        // Create SPC payment session
        $args = array(
            'body'        => json_encode($data),
            'timeout'     => '5',
            'redirection' => '5',
            'blocking'    => true,
            'headers'     => array('Content-Type' => 'application/json', 'X-SC-ApiKey' => $api_key_from_woocommerce_plugin),
            'cookies'     => array(),
        );

        $response = wp_remote_post( 'https://sprintcheckout-mvp.herokuapp.com/checkout/v2/payment_session', $args );
        // Check session is created -> 201

        if ( 201 === wp_remote_retrieve_response_code( $response ) ) {
            $response_body = wp_remote_retrieve_body( $response );
            $response_body_json = json_decode($response_body, true);
            //WC()->cart->empty_cart();
            //$order->payment_complete();

            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                return "Something went wrong: $error_message";
            }
            // redirect
            return array(
                'result'   => 'success',
                'redirect' => $response_body_json['redirectUrl']
            );
        }

	}

}