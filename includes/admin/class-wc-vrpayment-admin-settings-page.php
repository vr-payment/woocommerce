<?php
/**
 * Plugin Name: VRPayment
 * Author: VR Payment GmbH
 * Text Domain: vrpayment
 * Domain Path: /languages/
 *
 * VRPayment
 * This plugin will add support for all VRPayment payments methods and connect the VRPayment servers to your WooCommerce webshop (https://www.vr-payment.de/).
 *
 * @category Class
 * @package  VRPayment
 * @author   VR Payment GmbH (https://www.vr-payment.de)
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_VRPayment_Admin_Settings_Page.
 * Adds VRPayment settings to WooCommerce Settings Tabs
 *
 * @class WC_VRPayment_Admin_Settings_Page
 */
class WC_VRPayment_Admin_Settings_Page extends WC_Settings_Page {

	/**
	 * Adds Hooks to output and save settings
	 */
	public function __construct() {
		$this->id = 'vrpayment';
		$this->label = 'VRPay';

		add_filter(
			'woocommerce_settings_tabs_array',
			array(
				$this,
				'add_settings_page',
			),
			20
		);
		add_action(
			'woocommerce_settings_' . $this->id,
			array(
				$this,
				'settings_tab',
			)
		);
		add_action(
			'woocommerce_settings_save_' . $this->id,
			array(
				$this,
				'save',
			)
		);

		add_action(
			'woocommerce_update_options_' . $this->id,
			array(
				$this,
				'update_settings',
			)
		);

		add_action(
			'woocommerce_admin_field_vrpayment_links',
			array(
				$this,
				'output_links',
			)
		);
	}

	/**
	 * Add Settings Tab
	 *
	 * @param mixed $settings_tabs settings_tabs.
	 * @return mixed $settings_tabs
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs[ $this->id ] = 'VRPay';
		return $settings_tabs;
	}

	/**
	 * Settings Tab
	 *
	 * @return void
	 */
	public function settings_tab() {
		woocommerce_admin_fields( $this->get_settings() );
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save() {
		$settings = $this->get_settings();
		WC_Admin_Settings::save_fields( $settings );
	}

	/**
	 * Update Settings
	 *
	 * @return void
	 * @throws Exception Exception.
	 */
	public function update_settings() {
		WC_VRPayment_Helper::instance()->reset_api_client();
		$user_id  = get_option( WooCommerce_VRPayment::VRPAYMENT_CK_APP_USER_ID );
		$user_key = get_option( WooCommerce_VRPayment::VRPAYMENT_CK_APP_USER_KEY );
		if ( ! ( empty( $user_id ) || empty( $user_key ) ) ) {
			$error_message = '';
			try {
				WC_VRPayment_Service_Method_Configuration::instance()->synchronize();
			} catch ( \Exception $e ) {
				WooCommerce_VRPayment::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
				WooCommerce_VRPayment::instance()->log( $e->getTraceAsString(), WC_Log_Levels::DEBUG );
				$error_message = esc_html__( 'Could not update payment method configuration.', 'woo-vrpayment' );
				WC_Admin_Settings::add_error( $error_message );
			}
			try {
				WC_VRPayment_Service_Webhook::instance()->install();
			} catch ( \Exception $e ) {
				WooCommerce_VRPayment::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
				WooCommerce_VRPayment::instance()->log( $e->getTraceAsString(), WC_Log_Levels::DEBUG );
				$error_message = esc_html__( 'Could not install webhooks, please check if the feature is active in your space.', 'woo-vrpayment' );
				WC_Admin_Settings::add_error( $error_message );
			}
			try {
				WC_VRPayment_Service_Manual_Task::instance()->update();
			} catch ( \Exception $e ) {
				WooCommerce_VRPayment::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
				WooCommerce_VRPayment::instance()->log( $e->getTraceAsString(), WC_Log_Levels::DEBUG );
				$error_message = esc_html__( 'Could not update the manual task list.', 'woo-vrpayment' );
				WC_Admin_Settings::add_error( $error_message );
			}
			try {
				do_action( 'wc_vrpayment_settings_changed' );
			} catch ( \Exception $e ) {
				WooCommerce_VRPayment::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
				WooCommerce_VRPayment::instance()->log( $e->getTraceAsString(), WC_Log_Levels::DEBUG );
				$error_message = $e->getMessage();
				WC_Admin_Settings::add_error( $error_message );
			}

			if ( wc_tax_enabled() && ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) ) {
				if ( 'yes' === get_option( WooCommerce_VRPayment::VRPAYMENT_CK_ENFORCE_CONSISTENCY ) ) {
					$error_message = esc_html__( "'WooCommerce > Settings > VRPayment > Enforce Consistency' and 'WooCommerce > Settings > Tax > Rounding' are both enabled. Please disable at least one of them.", 'woo-vrpayment' );
					WC_Admin_Settings::add_error( $error_message );
					WooCommerce_VRPayment::instance()->log( $error_message, WC_Log_Levels::ERROR );
				}
			}

			if ( ! empty( $error_message ) ) {
				$error_message = esc_html__( 'Please check your credentials and grant the application user the necessary rights (Account Admin) for your space.', 'woo-vrpayment' );
				WC_Admin_Settings::add_error( $error_message );
			}
			WC_VRPayment_Helper::instance()->delete_provider_transients();
		}
	}

	/**
	 * Output Links
	 *
	 * @param mixed $value value.
	 * @return void
	 */
	public function output_links( $value ) {
		foreach ( $value['links'] as $url => $text ) {
			echo '<a href="' . esc_url( $url ) . '" class="page-title-action">' . esc_html( $text ) . '</a>';
		}
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {

		$settings = array(
			array(
				'links' => array(
					'https://gateway.vr-payment.de/doc/woocommerce/3.3.3/docs/en/documentation.html' => esc_html__( 'Documentation', 'woo-vrpayment' ),
					'https://gateway.vr-payment.de/user/login' => esc_html__( 'Sign Up', 'woo-vrpayment' ),
				),
				'type'  => 'vrpayment_links',
			),

			array(
				'title' => esc_html__( 'General Settings', 'woo-vrpayment' ),
				'desc'  =>
					esc_html__(
						'Enter your application user credentials and space id, if you don\'t have an account already sign up above.',
						'woo-vrpayment'
					),
				'type'  => 'title',
				'id' => 'general_options',
			),

			array(
				'title' => esc_html__( 'Space Id', 'woo-vrpayment' ),
				'id' => WooCommerce_VRPayment::VRPAYMENT_CK_SPACE_ID,
				'type' => 'text',
				'css' => 'min-width:300px;',
				'desc' => esc_html__( '(required)', 'woo-vrpayment' ),
			),

			array(
				'title' => esc_html__( 'User Id', 'woo-vrpayment' ),
				'desc_tip' => esc_html__( 'The user needs to have full permissions in the space this shop is linked to.', 'woo-vrpayment' ),
				'id' => WooCommerce_VRPayment::VRPAYMENT_CK_APP_USER_ID,
				'type' => 'text',
				'css' => 'min-width:300px;',
				'desc' => esc_html__( '(required)', 'woo-vrpayment' ),
			),

			array(
				'title' => esc_html__( 'Authentication Key', 'woo-vrpayment' ),
				'id' => WooCommerce_VRPayment::VRPAYMENT_CK_APP_USER_KEY,
				'type' => 'password',
				'css' => 'min-width:300px;',
				'desc' => esc_html__( '(required)', 'woo-vrpayment' ),
			),

			array(
				'type' => 'sectionend',
				'id' => 'general_options',
			),

			array(
				'title' => esc_html__( 'Email Options', 'woo-vrpayment' ),
				'type' => 'title',
				'id' => 'email_options',
			),

			array(
				'title' => esc_html__( 'Send Order Email', 'woo-vrpayment' ),
				'desc' => esc_html__( 'Send the order email of WooCommerce.', 'woo-vrpayment' ),
				'id' => WooCommerce_VRPayment::VRPAYMENT_CK_SHOP_EMAIL,
				'type' => 'checkbox',
				'default' => 'yes',
				'css' => 'min-width:300px;',
			),

			array(
				'type' => 'sectionend',
				'id' => 'email_options',
			),

			array(
				'title' => esc_html__( 'Document Options', 'woo-vrpayment' ),
				'type' => 'title',
				'id' => 'document_options',
			),

			array(
				'title' => esc_html__( 'Invoice Download', 'woo-vrpayment' ),
				'desc' => esc_html__( 'Allow customers to download the invoice.', 'woo-vrpayment' ),
				'id' => WooCommerce_VRPayment::VRPAYMENT_CK_CUSTOMER_INVOICE,
				'type' => 'checkbox',
				'default' => 'yes',
				'css' => 'min-width:300px;',
			),
			array(
				'title' => esc_html__( 'Packing Slip Download', 'woo-vrpayment' ),
				'desc' => esc_html__( 'Allow customers to download the packing slip.', 'woo-vrpayment' ),
				'id' => WooCommerce_VRPayment::VRPAYMENT_CK_CUSTOMER_PACKING,
				'type' => 'checkbox',
				'default' => 'yes',
				'css' => 'min-width:300px;',
			),

			array(
				'type' => 'sectionend',
				'id' => 'document_options',
			),

			array(
				'title' => esc_html__( 'Space View Options', 'woo-vrpayment' ),
				'type' => 'title',
				'id' => 'space_view_options',
			),

			array(
				'title' => esc_html__( 'Space View Id', 'woo-vrpayment' ),
				'desc_tip' => esc_html__( 'The Space View Id allows to control the styling of the payment form and the payment page within the space.', 'woo-vrpayment' ),
				'id' => WooCommerce_VRPayment::VRPAYMENT_CK_SPACE_VIEW_ID,
				'type' => 'number',
				'css' => 'min-width:300px;',
			),

			array(
				'type' => 'sectionend',
				'id' => 'space_view_options',
			),

			array(
				'title' => esc_html__( 'Integration Options', 'woo-vrpayment' ),
				'type' => 'title',
				'id' => 'integration_options',
			),

			array(
				'title' => esc_html__( 'Integration Type', 'woo-vrpayment' ),
				'desc_tip' => esc_html__( 'The integration type controls how the payment form is integrated into the WooCommerce checkout. The Lightbox integration type offers better performance but with a less compelling checkout experience.', 'woo-vrpayment' ),
				'id'  => WooCommerce_VRPayment::VRPAYMENT_CK_INTEGRATION,
				'type' => 'select',
				'css' => 'min-width:300px;',
				'default' => WC_VRPayment_Integration::VRPAYMENT_IFRAME,
				'options' => array(
					WC_VRPayment_Integration::VRPAYMENT_IFRAME => $this->format_display_string( esc_html__( 'iframe', 'woo-vrpayment' ) ),
					WC_VRPayment_Integration::VRPAYMENT_LIGHTBOX  => $this->format_display_string( esc_html__( 'lightbox', 'woo-vrpayment' ) ),
				),
			),

			array(
				'type' => 'sectionend',
				'id' => 'integration_options',
			),

			array(
				'title' => esc_html__( 'Line Items Options', 'woo-vrpayment' ),
				'type' => 'title',
				'id' => 'line_items_options',
			),

			array(
				'title' => esc_html__( 'Enforce Consistency', 'woo-vrpayment' ),
				'desc' => esc_html__( 'Require that the transaction line items total is matching the order total.', 'woo-vrpayment' ),
				'id' => WooCommerce_VRPayment::VRPAYMENT_CK_ENFORCE_CONSISTENCY,
				'type' => 'checkbox',
				'default' => 'yes',
				'css' => 'min-width:300px;',
			),

			array(
				'type' => 'sectionend',
				'id' => 'line_items_options',
			),

			array(
				'title' => esc_html__( 'Reference Options', 'woo-vrpayment' ),
				'type' => 'title',
				'id' => 'reference_options',
			),

			array(
				'title' => esc_html__( 'Order Reference Type', 'woo-vrpayment' ),
				'desc_tip' => esc_html__( 'Choose which order reference is sent.', 'woo-vrpayment' ),
				'id' => WooCommerce_VRPayment::VRPAYMENT_CK_ORDER_REFERENCE,
				'type' => 'select',
				'css' => 'min-width:300px;',
				'default' => WC_VRPayment_Order_Reference::VRPAYMENT_ORDER_ID,
				'options' => array(
					WC_VRPayment_Order_Reference::VRPAYMENT_ORDER_ID => $this->format_display_string( esc_html__( 'order_id', 'woo-vrpayment' ) ),
					WC_VRPayment_Order_Reference::VRPAYMENT_ORDER_NUMBER  => $this->format_display_string( esc_html__( 'order_number', 'woo-vrpayment' ) ),
				),
			),

			array(
				'type' => 'sectionend',
				'id' => 'reference_options',
			),

		);

		return apply_filters( 'wc_vrpayment_settings', $settings );
	}

	/**
	 * Format Display String
	 *
	 * @param string $display_string display string.
	 * @return string
	 */
	private function format_display_string( $display_string ) {
		return ucwords( str_replace( '_', ' ', $display_string ) );
	}
}
