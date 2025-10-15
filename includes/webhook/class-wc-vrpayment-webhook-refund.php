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
 * Webhook processor to handle refund state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_VRPayment_Service_Refund
 */
class WC_VRPayment_Webhook_Refund extends WC_VRPayment_Webhook_Order_Related_Abstract {

	/**
	 * Canonical processor.
	 *
	 * @var WC_VRPayment_Webhook_Refund_Strategy
	 */
	private $strategy;

	/**
	 * Construct to initialize canonical processor.
	 *
	 */
	public function __construct() {
		$this->strategy = new WC_VRPayment_Webhook_Refund_Strategy();
	}

	/**
	 * Load entity.
	 *
	 * @param WC_VRPayment_Webhook_Request $request request.
	 * @return object|\VRPayment\Sdk\Model\Refund
	 * @throws \VRPayment\Sdk\ApiException ApiException.
	 * @throws \VRPayment\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \VRPayment\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_VRPayment_Webhook_Request $request ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_VRPayment_Webhook_Refund_Strategy::load_entity'
        );
		return $this->strategy->load_entity( $request );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $refund refund.
	 * @return int|string
	 */
	protected function get_order_id( $refund ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_VRPayment_Webhook_Refund_Strategy::get_order_id'
        );
		return $this->strategy->get_order_id( $refund );
	}

	/**
	 * Get transaction id.
	 *
	 * @param mixed $refund refund.
	 * @return int
	 */
	protected function get_transaction_id( $refund ) {
		/* @var \VRPayment\Sdk\Model\Refund $refund */ //phpcs:ignore
		return $refund->getTransaction()->getId();
	}

	/**
	 * Process order related inner.
	 *
	 * @param WC_Order $order order.
	 * @param mixed $refund refund.
	 * @param WC_VRPayment_Webhook_Request $request request.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, $refund, $request ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_VRPayment_Webhook_Refund_Strategy::process_order_related_inner'
        );
        $this->strategy->bridge_process_order_related_inner( $order, $refund, $request );
	}
}
