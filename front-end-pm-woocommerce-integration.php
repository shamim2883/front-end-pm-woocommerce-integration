<?php
/*
Plugin Name: Front End PM - WooCommerce Integration
Plugin URI: https://wordpress.org/plugins/front-end-pm-woocommerce-integration/
Description: Front End PM extension to integrate with WooCommerce
Version: 1.1
Author: Shamim Hasan
Author URI: https://www.shamimsplugins.com/contact-us/
Text Domain: front-end-pm-woocommerce-integration
License: GPLv2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Front_End_Pm_WC_Integration {

	private static $instance;

	private function __construct() {
		if ( ! function_exists( 'fep_get_option' ) || ! defined( 'WC_VERSION' ) ) {
			// Display notices to admins.
			add_action( 'admin_notices', array( $this, 'notices' ) );
			return;
		}
		// $this->constants();
		$this->includes();
		$this->actions();
		$this->filters();

	}

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function constants() {
	}

	private function includes() {
		require_once plugin_dir_path( __FILE__ ) . 'admin/class-fep-wc-admin-settings.php';
	}

	private function actions() {
	}

	private function filters() {
		add_filter( 'fep_current_user_can', array( $this, 'current_user_can' ), 10, 3 );
		add_filter( 'fep_directory_arguments', array( $this, 'user_query_args' ), 10, 1 );
		add_filter( 'fep_autosuggestion_arguments', array( $this, 'user_query_args' ), 10, 1 );
		
		// Add our custom product tabs section to the product page
		add_filter( 'woocommerce_product_tabs', array( $this, 'product_tabs' ) );
	}

	public function notices() {
		echo '<div class="error"><p>' . __( 'Front End PM and WooCommerce must be activated to use Front End PM - WooCommerce Integration.', 'front-end-pm-woocommerce-integration' ) . '</p></div>';
	}
	
	public function purchased_product_ids() {
		global $wpdb;
		static $products_ids;
		if ( is_array( $products_ids ) ) {
			return $products_ids;
		}

		$statuses     = array_map( 'sanitize_key', fep_get_option( 'wc-order-statuses', [ 'wc-completed', 'wc-processing' ] ) );
		$products_ids = $wpdb->get_col( $wpdb->prepare(
			"
			SELECT      itemmeta.meta_value
			FROM        {$wpdb->prefix}woocommerce_order_itemmeta AS itemmeta
			INNER JOIN  {$wpdb->prefix}woocommerce_order_items AS items
			            ON itemmeta.order_item_id = items.order_item_id
			INNER JOIN  $wpdb->posts AS orders
			            ON orders.ID = items.order_id
			INNER JOIN  $wpdb->postmeta AS ordermeta
			            ON orders.ID = ordermeta.post_id
			WHERE       itemmeta.meta_key IN ( '_product_id', '_variation_id' )
			            AND orders.post_type = 'shop_order'
						AND orders.post_status IN ( '" . implode( "','", $statuses ) . "' )
			            AND ordermeta.meta_key = '_customer_user'
			            AND ordermeta.meta_value = %s
			",
			get_current_user_id()
		) );
		$products_ids = array_unique( $products_ids );
		return $products_ids;
	}
	
	public function purchased_product_authors() {
		global $wpdb;
		static $authors;
		if ( is_array( $authors ) ) {
			return $authors;
		}
		$product_ids = $this->purchased_product_ids();
		
		if ( ! $product_ids || ! is_array( $product_ids ) ) {
			$authors = [];
			return $authors;
		}
		$product_ids = array_filter( array_map( 'absint', $product_ids ) );
		
		$authors = $wpdb->get_col( "SELECT post_author FROM $wpdb->posts WHERE post_type = 'product' AND post_status = 'publish' AND ID IN ( " . implode( ',', $product_ids ) . " )" );
		
		$authors = array_unique( $authors );
		return $authors;
	}
	
	public function current_user_can( $can, $cap, $id ) {
		if ( 'send_new_message_to' !== $cap ) {
			return $can;
		}
		if ( ! $can || ! fep_get_option( 'wc-require-purchase' ) || fep_is_user_admin() || fep_is_user_whitelisted() ) {
			return $can;
		}
		$authors = $this->purchased_product_authors();
		
		if ( ! in_array( $id, $authors ) ) {
			$can = false;
		}
		return $can;
	}
	
	public function user_query_args( $args ) {
		if ( isset( $args['include'] ) || ! fep_get_option( 'wc-require-purchase' ) ) {
			return $args;
		}
		$authors = $this->purchased_product_authors();
		
		if ( $authors ) {
			$args['include'] = $authors;
		} else {
			$args['include'] = array( 0 );
		}
		return $args;
	}
	
	public function product_tabs( $tabs ) {
		$tabs[ 'fep_wc_contact_seller' ] = array(
			'title'		=> __( 'Contact Seller', 'front-end-pm-woocommerce-integration' ),
			'priority'	=> 100,
			'callback'	=> array( $this, 'tab_content' ),
		);
		return $tabs;
	}
	
	public function tab_content(){
		if ( is_user_logged_in() ) {
			if ( ! fep_get_option( 'wc-require-purchase' ) ) {
				echo do_shortcode( '[fep_shortcode_new_message_form subject="{current-post-title}" heading=""]' );
			} elseif ( in_array( get_the_ID(), $this->purchased_product_ids() ) ) {
				echo do_shortcode( '[fep_shortcode_new_message_form subject="{current-post-title}" heading=""]' );
			} else {
				echo '<div class="fep-error">' . __( 'You have to purchase this product to contact seller', 'front-end-pm-woocommerce-integration' ) . '</div>';
			}
		} else {
			echo '<div class="fep-error">' . sprintf( __( 'You must <a href="%s">login</a> to contact seller', 'front-end-pm-woocommerce-integration' ), wp_login_url( get_permalink() ) ) . '</div>';
		}
	}

} //END Class

add_action( 'init', array( 'Front_End_Pm_WC_Integration', 'init' ) );
