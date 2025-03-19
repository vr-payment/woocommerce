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
		$this->label = __( 'VRPay', 'woo-vrpayment' );

		add_filter(
			'woocommerce_settings_tabs_array',
			array(
				$this,
				'add_settings_page',
			),
			20
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'woocommerce_update_options_' . $this->id, array( $this, 'update_settings' ) );
		add_action( 'woocommerce_admin_field_vrpayment_order_statuses_table', array( $this, 'order_statuses_table' ) );
		add_action( 'woocommerce_admin_field_vrpayment_links', array( $this, 'output_links' ) );

		parent::__construct();
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
	 * Get all sections for this page, both the own ones and the ones defined via filters.
	 * This method overrides the parent method to add the own sections to the list of sections.
	 *
	 * @return array
	 */
	protected function get_own_sections() {
		return array(
			'' => esc_html__( 'General Settings', 'woo-vrpayment' ),
			'vrpayment_order_statuses_settings' => esc_html__( 'Order Statuses Settings', 'woo-vrpayment' ),
		);
	}

	/**
	 * Get settings for the default section.
	 *
	 * This method returns the settings array for the default section (i.e., when the section ID is empty).
	 * Originally, the get_settings() method returned the settings for the "Options" section when the supplied
	 * $current_section was an empty string.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {
		return $this->get_default_settings();
	}

	/**
	 * Get settings for the order status settings section.
	 *
	 * This method returns the settings array for the "Order Status Settings" section.
	 * This replaces the original behavior of get_settings() which returned the "Options" section when the
	 * supplied $current_section was empty.
	 *
	 * @return array
	 */
	protected function get_settings_for_vrpayment_order_statuses_settings_section() {
		return $this->get_order_status_settings();
	}

	/**
	 * Get settings for the add new order status section.
	 *
	 * This method returns the settings array for the "Add New Order Status" section.
	 * This replaces the original behavior of get_settings() which returned the "Options" section when the
	 * supplied $current_section was empty.
	 *
	 * @return array
	 */
	protected function get_settings_for_vrpayment_order_statuses_section() {
		global $current_section, $hide_save_button;
		$hide_save_button = true;
		return $this->get_order_statuses();
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save() {
		$this->save_settings_for_current_section();
		$this->do_update_options_action();
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
	public function get_default_settings() {
		$settings = array(
			array(
				'links' => array(
					'https://gateway.vr-payment.de/doc/woocommerce/3.3.7/docs/en/documentation.html' => esc_html__( 'Documentation', 'woo-vrpayment' ),
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
				'default' => WC_VRPayment_Integration::VRPAYMENT_PAYMENTPAGE,
				'options' => array(
					WC_VRPayment_Integration::VRPAYMENT_IFRAME => $this->format_display_string( esc_html__( 'iframe', 'woo-vrpayment' ) ),
					WC_VRPayment_Integration::VRPAYMENT_LIGHTBOX  => $this->format_display_string( esc_html__( 'lightbox', 'woo-vrpayment' ) ),
					WC_VRPayment_Integration::VRPAYMENT_PAYMENTPAGE => $this->format_display_string( esc_html__( 'payment page', 'woo-vrpayment' ) ),
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

		return apply_filters( 'vrpayment_settings', $settings );
	}

	/**
	 * Returns the configuration for the Order Status section.
	 *
	 * @return array
	 */
	public function get_order_status_settings() {
		$settings = array(
			array(
				'title' => __( 'Order Status Settings', 'woo-vrpayment' ),
				'type' => 'title',
				'id' => 'order_status_mapping_options',
				'desc' => __( 'Map WooCommerce Order Statuses to VRPayment Transaction Statuses to ensure seamless integration and consistent order tracking across both platforms.', 'woo-vrpayment' ) . '
						<table class="form-table" style="width: 100%;">
							<thead>
								<tr style="">
									<th scope="row" class="titledesc">' . __( 'VRPayment payment status', 'woo-vrpayment' ) . '</th>
									<th class="forminp forminp-select" style="width: 100%;"><label style="margin: 0 10px;">' . __( 'WooCommerce Order Status', 'woo-vrpayment' ) . '</label></th>
								</tr>
							</thead>
						</table>',
			),
		);

		$woocommerce_statuses = apply_filters( 'vrpayment_woocommerce_statuses', array() );
		$vrpayment_statuses = apply_filters( 'vrpayment_order_statuses', array() );
		$default_mappings = apply_filters( 'vrpayment_default_order_status_mappings', array() );

		foreach ( $vrpayment_statuses as $status_key => $status_label ) {
			$default_mapped_status = isset( $default_mappings[ $status_key ] ) ? $default_mappings[ $status_key ] : '';

			$settings[] = array(
				'title' => __( $status_label, 'woo-vrpayment' ), // phpcs:ignore
				'id' => WC_VRPayment_Order_Status_Adapter::VRPAYMENT_ORDER_STATUS_MAPPING_PREFIX . $status_key,
				'type' => 'select',
				'options' => array_map( function ( $status ) {
						return __( $status, 'woo-vrpayment' ); // phpcs:ignore
					},
					$woocommerce_statuses
				),
				/* translators: %s: replaces string */
				'default' => sprintf( __( '%s', 'woo-vrpayment' ), $default_mapped_status ), // phpcs:ignore
				'desc' => sprintf( __( 'Set a custom WooCommerce order status to be applied automatically when a transaction is in the %s state.', 'woo-vrpayment' ), strtolower( __( $status_label, 'woo-vrpayment' ) ) ), // phpcs:ignore
			);
		}

		$settings[] = array(
			'type' => 'sectionend',
			'id' => 'status_mapping_options',
		);

		return apply_filters( 'vrpayment_custom_order_statuses_settings', $settings );
	}

	/**
	 * Output order statuses section.
	 */
	public function get_order_statuses() {
		$settings = array(
			array( 'type' => 'vrpayment_order_statuses_table' ),
			array(
				'type' => 'sectionend',
				'id'   => 'order_statuses_section',
			),
		);

		return apply_filters( 'vrpayment_custom_order_statuses', $settings );
	}

	/**
	 * Output order statuses section.
	 */
	public function order_statuses_table() {
		// Extendable columns to show on the order statuses screen.
		$order_statuses_columns = apply_filters(
			'vrpayment_order_statuses_columns',
			array(
				'vrpayment-order-status-key' => __( 'Status key', 'woo-vrpayment' ),
				'vrpayment-order-status-label' => __( 'Label', 'woo-vrpayment' ),
				'vrpayment-order-status-type' => __( 'Type', 'woo-vrpayment' ),
			)
		);

		require_once WC_VRPAYMENT_ABSPATH . '/views/admin-settings/html-admin-page-order-statuses.php';
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


	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		global $current_section;

		$version = WC_VRPAYMENT_REQUIRED_WC_MAXIMUM_VERSION;

		// Register scripts.
		wp_register_script(
			'vrpayment-order-statuses',
			WooCommerce_VRPayment::instance()->plugin_url() . '/assets/js/admin/order-statuses.js',
			array(
				'jquery',
				'wp-util',
				'underscore',
				'backbone',
				'wc-backbone-modal'
			),
			$version,
			array( 'in_footer' => false )
		);
		wp_enqueue_script( 'vrpayment-order-statuses' );

		$localized_object = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'statuses' => WC_VRPayment_Helper::instance()->get_woocommerce_order_statuses_json(),
			'default_order_status' => array(
				'key' => '',
				'label' => '',
				'type' => '',
			),
			'vrpayment_order_statuses_nonce' => wp_create_nonce( 'vrpayment_order_statuses_nonce' ),
			'strings' => array(
				'custom_order_status_prefix' => WC_VRPayment_Order_Status_Adapter::VRPAYMENT_CUSTOM_ORDER_STATUS_PREFIX,
				'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'woo-vrpayment' ),
				'save_failed' => __( 'Your changes were not saved. Please retry.', 'woo-vrpayment' ),
				'delete_confirmation_msg' => __( 'Are you sure you want to delete this custom status?', 'woo-vrpayment' ),
				'characters_remaining' => __( 'characters remaining', 'woo-vrpayment' ),
			),
		);
		wp_localize_script(
			'vrpayment-order-statuses',
			'vrpaymentOrderStatusesLocalizeScript',
			$localized_object
		);
	}
}
