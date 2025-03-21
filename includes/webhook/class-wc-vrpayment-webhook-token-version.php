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
 * Webhook processor to handle token version state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_VRPayment_Webhook_Token_Version_Strategy
 */
class WC_VRPayment_Webhook_Token_Version extends WC_VRPayment_Webhook_Abstract {

	/**
	 * Process.
	 *
	 * @param WC_VRPayment_Webhook_Request $request request.
	 * @return void
	 * @throws \VRPayment\Sdk\ApiException ApiException.
	 * @throws \VRPayment\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \VRPayment\Sdk\VersioningException VersioningException.
	 */
	public function process( WC_VRPayment_Webhook_Request $request ) {
		$token_service = WC_VRPayment_Service_Token::instance();
		$token_service->update_token_version( $request->get_space_id(), $request->get_entity_id() );
	}
}
