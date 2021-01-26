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
		}

		public function order_webhook_payment($order_id){
			$order = wc_get_order($order_id);
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

		public function get_line_items($order){
			$line_items = [];
			foreach($order->get_items() as $item){
				if( $item->is_type('product') ){
					$product 	= wc_get_product($item->get_product_id());
					$name 		= $item->get_name();
					$sku  		= $product->get_sku();
					$qty  		= $item->get_quantity();
					$total 		= $item->get_total();
					$subtotal = $item->get_subtotal();

					$line_items[] = [
						'name' =>
					]
				}
				// array_push($line_items, [
				// 	'name' => $line_item->get_name(),
				// 	'qty'  => $line_item->get_quantity(),
				// ]);
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
