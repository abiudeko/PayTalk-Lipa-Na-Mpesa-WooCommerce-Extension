<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
/**
* @package PaytalkWoocommerce
*/
/*
Plugin Name: Paytalk Lipa Na Mpesa
Plugin URI: https://wordpress.org/plugins/paytalk-lipa-na-pesa
Description: Paytalk Lipa Na Mpesa allows woocommerce users to accept payment from Mpesa customers.
Version: 1.5.0
Author: Abiud Ekidon
Author URI: https://ekidonhot.com/profile-abiud-ekidon-omonya/
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: paytalk-lipanampesa-woocommerce
*/
add_action( 'plugins_loaded', 'init_paytalk_class' );
function init_paytalk_class() {
    class WC_Paytalk_Gateway extends WC_Payment_Gateway {
    	function __construct() {

			// Setup our default vars
			$this->id                 = 'paytalk';
			$this->method_title       = __('PayTalk.co.ke', 'woocommerce');
			$this->method_description = __('Paytalk works by adding form fields on the checkout and then sending the details to Paytalk.co.ke for verification and processing.', 'woocommerce');
			$this->icon               = plugins_url( '/images/paytalk_160x68.png', __FILE__ );
			$this->has_fields         = true;
			$this->supports           = array( 'products' );
			
			$this->liveurl            = 'https://developer.paytalk.co.ke/api/';
			$this->testurl            = 'https://developer.paytalk.co.ke/api/';

			$this->init_form_fields();
			$this->init_settings();

			// Get setting values
			//$this->title       = $this->settings['title'];
			$this->description = $this->settings['description'];
			$this->enabled     = $this->settings['enabled'];
			$this->testmode    = $this->settings['testmode'];
			$this->paytill     = $this->settings['paytill'];
			$this->description = $this->settings['description'];
			$this->api_user    = $this->settings['api_user'];
			$this->trans_key    = $this->settings['trans_key'];

			// Hooks
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );

		}

		function init_form_fields() {

			$this->form_fields = array(
				'enabled' => array(
					'title'       => __( 'Enable/Disable', 'woocommerce' ),
					'label'       => __( 'Enable PayTalk', 'woocommerce' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
					),

				'paytill' => array(
					'title'       => __( 'Paybill/Till Number', 'woocommerce' ),
					'type'        => 'text',
					'description' => '',
					'default'     => ''
					),

				'description' => array(
					'title'       => __( 'Description', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
					'default'     => 'Pay with Till Number/Paybill via PayTalk.'
					),

				'api_user' => array(
					'title'       => __( 'API Username', 'woocommerce' ),
					'type'        => 'text',
					'description' => sprintf( __( 'Your API credentials can be located in your <a href="%s" target="_blank">PayTalk Account.</a>', 'woocommerce' ), 'https://developer.paytalk.co.ke/' ),
					'default'     => ''
					),

				'trans_key' => array(
					'title'       => __( 'API Transaction Key', 'woocommerce' ),
					'type'        => 'password',
					'description' => __( 'Your API credentials can be located in your PayTalk merchant interface.', 'woocommerce' ),
					'default'     => ''
					),
				'testmode' => array(
					'title'       => __( 'Test mode', 'woocommerce' ),
					'label'       => __( 'Enable Test Mode', 'woocommerce' ),
					'type'        => 'checkbox',
					'description' => __( 'Place the payment gateway in test mode using test account credentials (Test Mode).', 'woocommerce' ),
					'default'     => 'no'
					)
				/*
				'debug' => array(
					'title'       => __( 'Debug Log', 'woocommerce' ),
					'label'       => __( 'Enable Logging', 'woocommerce' ),
					'type'        => 'checkbox',
					'description' => __( 'Log PayLeap events inside <code>/wp-content/uploads/wc-logs/payleap-{tag}.log</code>', 'woocommerce'),
					'default'     => 'no'
				),*/
			);

		}

		public function payment_fields() {
				if ($description = $this->get_description()) {
				  echo wpautop(wptexturize($description));
				}
				$environment = ( $this->testmode == "yes" ) ? 'TRUE' : 'FALSE';
				if($environment == "TRUE"){
					$testmode = '<p>
					Test Mode detected<br>
					For testing purposes, please use any phone number with <b>MFG65387456</b> as your Transaction ID.</p>';
				}else{
					$testmode = "";
				}
				echo $testmode.' 
					<div style="max-width:300px"> 
					<p class="form-row form-row form-row-wide woocommerce-validated" id="mpesa_phone_field" data-o_class="form-row form-row form-row-wide">
						<label for="mpesa_phone" class="">Phone Number <abbr class="required" title="required">*</abbr></label>
						<input type="text" class="input-text " name="mpesa_phone" id="mpesa_phone" placeholder="Phone Number" />
					</p>
					<p class="form-row form-row form-row-wide woocommerce-validated" id="mpesa_code_field" data-o_class="form-row form-row form-row-wide">
						<label for="mpesa_code" class="">Transaction ID <abbr class="required" title="required">*</abbr></label>
						<input type="text" class="input-text " name="mpesa_code" id="mpesa_code" placeholder="Transaction ID" />
					</p></div>
					';
			}

		public function validate_fields() { 
				if ($_POST['mpesa_code']) {
					$success = true;
				} else {					
					$error_message = __("The ", 'woothemes') . $this->field_title . __(" Phone Number is required", 'woothemes');
					wc_add_notice(__('Field error: ', 'woothemes') . $error_message, 'error');
					$success = False;
				}
				if ($_POST['mpesa_phone']) {
					$success = true;
				} else {					
					$error_message = __("The ", 'woothemes') . $this->phone_title . __(" Transaction ID is required", 'woothemes');
					wc_add_notice(__('Field error: ', 'woothemes') . $error_message, 'error');
					$success = False;
				}
				return $success;
			}

			// Submit payment and handle response
	public function process_payment( $order_id ) {
		global $woocommerce;
		
		// Get this Order's information so that we know
		// who to charge and how much
		$customer_order = new WC_Order( $order_id );
		
		$trans_key = $this->trans_key;

		// Are we testing right now or is it a real transaction
		$environment = ( $this->testmode == "yes" ) ? 'TRUE' : 'FALSE';

		// Decide which URL to post to
		$environment_url = ( "FALSE" == $environment ) 
						   ? 'https://developer.paytalk.co.ke/api/'
						   : 'https://developer.paytalk.co.ke/api/';
		/*$environment_url = ( "FALSE" == $environment ) 
						   ? 'https://localhost/developers/api/'
						   : 'https://localhost/developers/api/';*/

		$mpesa_phone    = isset($_POST['mpesa_phone']) ? woocommerce_clean($_POST['mpesa_phone']) : '';
		$mpesa_code    = isset($_POST['mpesa_code']) ? woocommerce_clean($_POST['mpesa_code']) : '';

		// This is where the fun stuff begins
		$payload = [
			// Paytalk.co.ke Credentials and API Info
			"trans_key"           	=> $trans_key,
			"api_user"              => $this->api_user,
			"paytill"              	=> $this->paytill,
			"x_version"            	=> "1.5.0",
			
			// Order total
			"x_amount"             	=> $customer_order->order_total,
			
			// Lipa Na Mpesa Information
			"mpesa_code"			=> $mpesa_code,
			"mpesa_phone"			=> $mpesa_phone,
			
			"x_type"               	=> 'AUTH_CAPTURE',
			"x_invoice_num"        	=> str_replace( "#", "", $customer_order->get_order_number() ),
			"x_test_request"       	=> $environment,
			"x_delim_char"         	=> '|',
			"x_encap_char"         	=> '',
			"x_delim_data"         	=> "TRUE",
			"x_relay_response"     	=> "FALSE",
			"x_method"             	=> "CC",
			
			// Billing Information
			"x_first_name"         	=> $customer_order->billing_first_name,
			"x_last_name"          	=> $customer_order->billing_last_name,
			"x_address"            	=> $customer_order->billing_address_1,
			"x_city"              	=> $customer_order->billing_city,
			"x_state"              	=> $customer_order->billing_state,
			"x_zip"                	=> $customer_order->billing_postcode,
			"x_country"            	=> $customer_order->billing_country,
			"x_phone"              	=> $customer_order->billing_phone,
			"x_email"              	=> $customer_order->billing_email,
			"order_id"              => $customer_order->get_id(),
			
			// Shipping Information
			"x_ship_to_first_name" 	=> $customer_order->shipping_first_name,
			"x_ship_to_last_name"  	=> $customer_order->shipping_last_name,
			"x_ship_to_company"    	=> $customer_order->shipping_company,
			"x_ship_to_address"    	=> $customer_order->shipping_address_1,
			"x_ship_to_city"       	=> $customer_order->shipping_city,
			"x_ship_to_country"    	=> $customer_order->shipping_country,
			"x_ship_to_state"      	=> $customer_order->shipping_state,
			"x_ship_to_zip"        	=> $customer_order->shipping_postcode,
			
			// Some Customer Information
			"x_cust_id"            	=> $customer_order->user_id,
			"x_customer_ip"        	=> $_SERVER['REMOTE_ADDR']
			
		];
	
		// Send this payload to Paytalk.co.ke for processing

		$response = wp_remote_post( $environment_url, array(
			'method'    => 'POST',
			'body'      => http_build_query( $payload ),
			'timeout'   => 90,
			'sslverify' => true,
		) );


		//$file = ABSPATH . 'wp-content/plugins/woo-paytalk-lipa-na-mpesa/errors.txt'; 
		//file_put_contents($file, $response['body'], FILE_TEXT ); 

		if ( is_wp_error( $response ) ) 
			throw new Exception( __( 'We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.', 'woocommerce' ) );

		if ( empty( $response['body'] ) )
			throw new Exception( __( 'PayTalk\'s Response was empty.', 'woocommerce' ) );

		if (  $response['body'] == "no_account" )
			throw new Exception( __( 'Make sure you are using PayTalk\'s Test API Username and Test API Transaction Key. Paybill/Till Number field is also required. You will need to change these values when you go live. Go to <a href="https://developer.paytalk.co.ke" target="_blank">Paytalk.co.ke Developer</a> to copy these credentials.', 'woocommerce' ) );

		if ( $response['body'] == "used_trans" )
			throw new Exception( __( 'Sorry, the Transaction ID you are trying to use has already been used. Please check and try again.', 'woocommerce' ) );

		if ( is_numeric($response['body']) )
			throw new Exception( __( 'We have detected that you have made payment less <b>Ksh'.$response['body'].'</b>. Kindly pay the balance before we can accept your order. Please make sure you have paid the balance of exactly <b>Ksh'.$response['body'].'</b> to avoid any further delays. Thank you.', 'woocommerce' ) );

		if ( $response['body'] == "no_trans" )
			throw new Exception( __( 'Sorry, we could not verify your payment. Please check your Mpesa Transaction ID and Phone Number to make sure they are valid and correct.', 'woocommerce' ) );
			
		// Retrieve the body's resopnse if no errors found	
		if ( ( $response['body'] == "Success" ) ) {
			// Payment has been successful
			$customer_order->add_order_note( __( 'Paytalk.co.ke payment completed.', 'woocommerce' ) );
												 
			// Mark order as Paid
			$customer_order->payment_complete();

			// Empty the cart (Very important step)
			$woocommerce->cart->empty_cart();

			// Redirect to thank you page
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $customer_order ),
			);
		} else {
			// Transaction was not succesful
			// Add notice to the cart
			wc_add_notice( $r['response_reason_text'], 'error' );
			// Add note to the order for your reference
			$customer_order->add_order_note( 'Error: '. $r['response_reason_text'] );
		}

	}
    	
}

function add_init_paytalk_class($methods) {
		$methods[] = 'WC_Paytalk_Gateway'; 
		return $methods;
	}
	add_filter('woocommerce_payment_gateways', 'add_init_paytalk_class');
}