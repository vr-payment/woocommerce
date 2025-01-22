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
 * Interface WC_VRPayment_Webhook_Strategy_Interface
 *
 * Defines a strategy interface for processing webhook requests.
 */
interface WC_VRPayment_Webhook_Strategy_Interface {

	/**
	 * Checks if the provided webhook entity ID matches the expected ID.
	 *
	 * This method is intended to verify whether the entity ID from a webhook request matches
	 * a specific ID configured within the WC_VRPayment_Service_Webhook. This can be used to validate that the
	 * webhook is relevant and should be processed further.
	 *
	 * @param string $webhook_entity_id The entity ID from the webhook request.
	 * @return bool Returns true if the ID matches the system's criteria, false otherwise.
	 */
	public function match( string $webhook_entity_id );

	/**
	 * Process the webhook request.
	 *
	 * @param WC_VRPayment_Webhook_Request $request The webhook request object.
	 * @return mixed The result of the processing.
	 */
	public function process( WC_VRPayment_Webhook_Request $request );
}
