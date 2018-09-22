<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class FEP_WC_Admin_Settings {

	private static $instance;

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function actions_filters() {
		add_filter( 'fep_admin_settings_tabs', array( $this, 'admin_settings_tabs' ) );
		add_filter( 'fep_settings_fields', array( $this, 'settings_fields' ) );
	}

	function admin_settings_tabs( $tabs ) {

		$tabs['wc_section'] = array(
			'section_title' => __( 'WooCommerce', 'front-end-pm-woocommerce-integration' ),
			'section_page'  => 'fep_settings_misc',
			'priority'      => 50,
			'tab_output'    => false,
		);

		return $tabs;
	}

	public function settings_fields( $fields ) {
		$fields['wc-require-purchase'] = array(
			'type'     => 'checkbox',
			'value'    => fep_get_option( 'wc-require-purchase' ),
			'priority' => 5,
			'class'    => 'fep_toggle_next_tr',
			'section'  => 'wc_section',
			'label'    => __( 'Require purchase', 'front-end-pm-woocommerce-integration' ),
			'cb_label' => __( 'Can Customer contact seller only after purchase?', 'front-end-pm-woocommerce-integration' ),
		);
		$fields['wc-order-statuses']   = array(
			'type'        => 'checkbox',
			'value'       => fep_get_option( 'wc-order-statuses', [ 'wc-completed', 'wc-processing' ] ),
			'priority'    => 10,
			'class'       => '',
			'section'     => 'wc_section',
			'multiple'    => true,
			'label'       => __( 'Order statuses', 'front-end-pm-woocommerce-integration' ),
			'description' => __( 'Which order statuses customer can contact seller?', 'front-end-pm-woocommerce-integration' ),
			'options'     => wc_get_order_statuses(),
		);

		return $fields;
	}

} //END CLASS

add_action( 'admin_init', array( FEP_WC_Admin_Settings::init(), 'actions_filters' ) );

