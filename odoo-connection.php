<?php

/**
 * Plugin Name: Odoo Connection SWMX
 * Plugin URI: https://github.io
 * Description: This plugins create the pipes for the odoo-woocommerce connection.
 * Version: 1.0.0
 * Author: Narfhtag
 * Author URI: http://github.io/narfthag
 */


defined( 'ABSPATH' ) or die;
if(! in_array('woocommerce/woocommerce.php', apply_filters('active_plugins',get_option('active_plugins')))){
	return;
}

if( !class_exists('SWMX_Odoo_Connection') ){
	class SWMX_Odoo_Connection {

		public function __construct(){
			add_action('admin_init', array( $this, 'register_settings'));
			add_action('admin_menu', array( $this, 'register_options_page'));
			add_action('woocommerce_order_status_on-hold', array($this,'send_to_ws' ) );
			add_action('woocommerce_order_status_processing', array($this, 'send_to_ws' ) )
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

			wp_remote_post($endpoint, $body);
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

			return data;
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
				'email'				=> $order->get_shipping_email(),
				'first_name'	=> $order->get_shipping_first_name(),
				'last_name' 	=> $order->get_shipping_last_name(),
				'phone'				=> $order->get_shipping_phone(),
				'postcode'    => $order->get_shipping_postcode()
			];
			return $address;
		}

		public function get_line_items($order){
			$line_items = [];
			foreach($order->get_items() as $item){
				if( $item->is_type('product') ) {
					$product 	= wc_get_product($item->get_product_id());
					$name 		= $item->get_name();
					$sku  		= $product->get_sku();
					$qty  		= $item->get_quantity();
					$total 		= $item->get_total();
					$subtotal = $item->get_subtotal();

					$line_items[] = [
						'name' => $name,
						'sku'  => $sku,
						'qty'  => $qty,
						'total' => $total,
						'subtotal' => $subtotal
					];
				}
				return $line_items;
			}
		}

		public function register_settings(){
			add_option('swmx_woc_hostname', 'https://domain.com');
			register_setting('swmx_woc', 'swmx_woc_hostname');
		}

		public function register_options_page(){
			add_menu_page(
				'Odoo Connection',
				'Odoo Connection',
				'manage_options',
				'swmx_woc',
				array($this, 'options_page')
			);
		}


		public function options_page(){
			?>
			<div>
				<?php screen_icon(); ?>
				<h2>Configuración Webservice</h2>
				<?php settings_fields('swmx_woc'); ?>
				<form method="post" action="options.php">
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

	}
}



?>
