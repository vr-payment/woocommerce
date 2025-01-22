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
 * Manages the strategy for processing webhook requests that pertain to payment method configurations.
 *
 * This class extends the base webhook strategy to specifically handle webhooks related to
 * payment method configuration updates. It ensures that payment method configurations are synchronized
 * with the latest changes indicated by incoming webhook requests.
 */
class WC_VRPayment_Webhook_Method_Configuration_Strategy extends WC_VRPayment_Webhook_Strategy_Base {

	/**
	 * Match function.
	 *
	 * @inheritDoc
	 * @param string $webhook_entity_id The webhook entity id.
	 */
	public function match( string $webhook_entity_id ) {
		return WC_VRPayment_Service_Webhook::VRPAYMENT_PAYMENT_METHOD_CONFIGURATION == $webhook_entity_id;
	}

	/**
	 * Processes the incoming webhook request related to payment method configurations.
	 *
	 * This method calls upon the payment method configuration service to synchronize configuration
	 * data based on the webhook information. This could involve updating local data stores to reflect
	 * changes made on the remote server side, ensuring that payment method settings are current.
	 *
	 * @param WC_VRPayment_Webhook_Request $request The webhook request object containing necessary data.
	 * @return void
	 * @throws \Exception Throws an exception if the synchronization process encounters a problem.
	 */
	public function process( WC_VRPayment_Webhook_Request $request ) {
		$payment_method_configuration_service = WC_VRPayment_Service_Method_Configuration::instance();
		$payment_method_configuration_service->synchronize();
	}
}
