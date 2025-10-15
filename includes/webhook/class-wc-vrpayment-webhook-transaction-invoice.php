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
 * Webhook processor to handle transaction completion state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_VRPayment_Webhook_Transaction_Invoice_Strategy
 */
class WC_VRPayment_Webhook_Transaction_Invoice extends WC_VRPayment_Webhook_Order_Related_Abstract {

	/**
	 * Canonical processor.
	 *
	 * @var WC_VRPayment_Webhook_Transaction_Invoice_Strategy
	 */
	private $strategy;

	/**
	 * Construct to initialize canonical processor.
	 *
	 */
	public function __construct() {
		$this->strategy = new WC_VRPayment_Webhook_Transaction_Invoice_Strategy();
	}

	/**
	 * Load entity.
	 *
	 * @param WC_VRPayment_Webhook_Request $request request.
	 * @return object|\VRPayment\Sdk\Model\TransactionInvoice
	 * @throws \VRPayment\Sdk\ApiException ApiException.
	 * @throws \VRPayment\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \VRPayment\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_VRPayment_Webhook_Request $request ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_VRPayment_Webhook_Transaction_Invoice_Strategy::load_entity'
        );
		return $this->strategy->load_entity( $request );
	}

	/**
	 * Load transaction.
	 *
	 * @param mixed $transaction_invoice transaction invoice.
	 * @return \VRPayment\Sdk\Model\Transaction
	 * @throws \VRPayment\Sdk\ApiException ApiException.
	 * @throws \VRPayment\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \VRPayment\Sdk\VersioningException VersioningException.
	 */
	protected function load_transaction( $transaction_invoice ) {
		/* @var \VRPayment\Sdk\Model\TransactionInvoice $transaction_invoice */ //phpcs:ignore
		$transaction_service = new \VRPayment\Sdk\Service\TransactionService( WC_VRPayment_Helper::instance()->get_api_client() );
		return $transaction_service->read( $transaction_invoice->getLinkedSpaceId(), $transaction_invoice->getCompletion()->getLineItemVersion()->getTransaction()->getId() );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $transaction_invoice transaction invoice.
	 * @return int|string
	 */
	protected function get_order_id( $transaction_invoice ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_VRPayment_Webhook_Transaction_Invoice_Strategy::get_order_id'
        );
		return $this->strategy->get_order_id( $transaction_invoice );
	}

	/**
	 * Get transaction invoice.
	 *
	 * @param mixed $transaction_invoice transaction invoice.
	 * @return int
	 */
	protected function get_transaction_id( $transaction_invoice ) {
		/* @var \VRPayment\Sdk\Model\TransactionInvoice $transaction_invoice */ //phpcs:ignore
		return $transaction_invoice->getLinkedTransaction();
	}

	/**
	 * Process
	 *
	 * @param WC_Order $order order.
	 * @param mixed $transaction_invoice transaction invoice.
	 * @param WC_VRPayment_Webhook_Request $request request.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, $transaction_invoice, $request ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_VRPayment_Webhook_Transaction_Invoice_Strategy::process_order_related_inner'
        );
        $this->strategy->bridge_process_order_related_inner( $order, $transaction_invoice, $request );
	}
}
