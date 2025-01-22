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
 * Webhook processor to handle payment method configuration state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_VRPayment_Webhook_Method_Configuration_Strategy
 */
class WC_VRPayment_Webhook_Method_Configuration extends WC_VRPayment_Webhook_Abstract {

	/**
	 * Synchronizes the payment method configurations on state transition.
	 *
	 * @param WC_VRPayment_Webhook_Request $request request.
	 */
	public function process( WC_VRPayment_Webhook_Request $request ) {
		$payment_method_configuration_service = WC_VRPayment_Service_Method_Configuration::instance();
		$payment_method_configuration_service->synchronize();
	}
}
