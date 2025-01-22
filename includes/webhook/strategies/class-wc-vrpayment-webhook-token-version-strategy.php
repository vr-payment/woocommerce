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
 * Class WC_VRPayment_Webhook_Token_Version_Strategy
 *
 * Handles the strategy for processing webhook requests related to token versions.
 * This class extends the base webhook strategy class to specifically manage webhook
 * requests that involve updates or changes to token versions. Token versions are crucial
 * for maintaining the integrity and version control of tokens used within the system.
 */
class WC_VRPayment_Webhook_Token_Version_Strategy extends WC_VRPayment_Webhook_Strategy_Base {

	/**
	 * Match function.
	 *
	 * @inheritDoc
	 * @param string $webhook_entity_id The webhook entity id.
	 */
	public function match( string $webhook_entity_id ) {
		return WC_VRPayment_Service_Webhook::VRPAYMENT_TOKEN_VERSION == $webhook_entity_id;
	}

	/**
	 * Processes the incoming webhook request associated with token versions.
	 *
	 * This method leverages the token service to update the version of a token identified by
	 * the space ID and entity ID provided in the webhook request. It ensures that the token version
	 * information is accurate and reflects any changes dictated by the incoming webhook data.
	 *
	 * @param WC_VRPayment_Webhook_Request $request The webhook request.
	 * @return void
	 * @throws Exception Throws an exception if there is a failure in updating the token version.
	 */
	public function process( WC_VRPayment_Webhook_Request $request ) {
		$token_service = WC_VRPayment_Service_Token::instance();
		$token_service->update_token_version( $request->get_space_id(), $request->get_entity_id() );
	}
}
