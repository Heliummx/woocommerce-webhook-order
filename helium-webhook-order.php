<?php

/**
 * Plugin Name: Webhook Order SWMX
 * Plugin URI: https://github.io
 * Description: This plugins create a Webhook for each time an Order change state to On-Hold or Processing.
 * Version: 1.0.0
 * Author: Narfhtag
 * Author URI: http://github.io/narfthag
 */


defined( 'ABSPATH' ) or die;
if(! in_array('woocommerce/woocommerce.php', apply_filters('active_plugins',get_option('active_plugins')))){
	return;
}

if( !class_exists('SWMX_Webhook_Order') ){
	class SWMX_Webhook_Order {

		public function __construct(){
			add_action('admin_init', array( $this, 'register_settings'));
			add_action('admin_menu', array( $this, 'register_options_page'));
			add_action('woocommerce_order_status_on-hold', array($this,'order_webhook_payment' ) );
			add_action('woocommerce_order_status_processing', array($this, 'order_webhook_payment' ) );
		}

		public function register_settings(){
			add_option('swmx_woc_hostname', 'https://domain.com');
			register_setting('swmx_woc', 'swmx_woc_hostname');
		}

		public function register_options_page(){
			add_menu_page(
				'Webhook Order',
				'Webhook Order',
				'manage_options',
				'swmx_woc',
				array($this, 'options_page')
			);
		}

		public function options_page(){
			?>
			<div>
				<h2>Configuración Webservice</h2>
				<form method="post" action="options.php">
					<?php settings_fields('swmx_woc'); ?>
					<label for="swmx_woc_hostname">Dirección Webservice</label>
					<input
						name="swmx_woc_hostname"
						id="swmx_woc_hostname"
						value="<?php echo get_option("swmx_woc_hostname");?>" />
						<?php submit_button(); ?>
				</form>
			</div>
			<?php
		}

		public function order_webhook_payment($order_id){
			$order = wc_get_order($order_id);
			$this->send_to_ws($order);
		}

		public function send_to_ws($order){
			$endpoint = get_option('swmx_woc_hostname');
			$body = $this->get_order_data($order);
			$body = wp_json_encode($body);
			$options = [
				'body'			=> $body,
				'headers' 	=> [
					'Content-Type' => 'application/json'
				],
				'timeout' 		=> 60,
				'redirection' => 5,
				'blocking'		=> true,
				'httpversion'	=> '1.0',
				'sslverify'   => true,
				'data_format' => 'body'
			];

			wp_remote_post($endpoint, $options);
		}

		public function get_order_data($order){
			$data = [
				'id' 								=> 	$order->get_id(),
				'status' 						=> 	$order->get_status(),
				'customer-id'				=>	$order->get_customer_id(),
				'cart'							=> 	$this->get_line_items($order),
				'billing-address'		=>	$this->get_billing_address($order),
				'shipping-address' 	=>	$this->get_shipping_address($order)
			];

			return $data;
		}

		public function get_billing_address($order){
			$address = [
				'address_1' 	=> $order->get_billing_address_1(),
				'address_2' 	=> $order->get_billing_address_2(),
				'city' 				=> $order->get_billing_city(),
				'company' 		=> $order->get_billing_company(),
				'country'			=> $order->get_billing_country(),
				'email'				=> $order->get_billing_email(),
				'first_name'	=> $order->get_billing_first_name(),
				'last_name' 	=> $order->get_billing_last_name(),
				'phone'				=> $order->get_billing_phone(),
				'postcode'    => $order->get_billing_postcode()
			];
			return $address;
		}

		public function get_shipping_address($order){
			$address = [
				'method'			=> $order->get_shipping_method(),
				'address_1' 	=> $order->get_shipping_address_1(),
				'address_2' 	=> $order->get_shipping_address_2(),
				'city' 				=> $order->get_shipping_city(),
				'company' 		=> $order->get_shipping_company(),
				'country'			=> $order->get_shipping_country(),
				'first_name'	=> $order->get_shipping_first_name(),
				'last_name' 	=> $order->get_shipping_last_name(),
				'postcode'    => $order->get_shipping_postcode()
			];
			return $address;
		}

		public function get_line_items($order){
			$line_items = [];
			foreach($order->get_items() as $item){
					$product 	= wc_get_product($item->get_product_id());
					$name 		= $item->get_name();
					$sku  		= $product->get_sku();

					$qty  		= $item->get_quantity();
					$to_tax		= $item->get_total_tax();
					$total 		= $item->get_total();
					$price		= $total / $qty;
					$subtotal = $item->get_subtotal();

					$line_items[] = [
						'name' => $name,
						'sku'  => $sku,
						'qty'  => $qty,
						'price' => $price,
						'total' => $total,
						'subtotal' => $subtotal,
						'total-tax' => $to_tax
					];
			}
			return $line_items;
		}

	}
	global $SWMX_WOC;
	$SWMX_WOC = new SWMX_Webhook_Order();
}



?>
