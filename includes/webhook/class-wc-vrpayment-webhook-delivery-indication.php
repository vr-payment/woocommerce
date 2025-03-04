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
 * Webhook processor to handle delivery indication state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_VRPayment_Webhook_Delivery_Indication_Strategy
 */
class WC_VRPayment_Webhook_Delivery_Indication extends WC_VRPayment_Webhook_Order_Related_Abstract {


	/**
	 * Load entity.
	 *
	 * @param WC_VRPayment_Webhook_Request $request request.
	 * @return object|\VRPayment\Sdk\Model\DeliveryIndication DeliveryIndication.
	 * @throws \VRPayment\Sdk\ApiException ApiException.
	 * @throws \VRPayment\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \VRPayment\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_VRPayment_Webhook_Request $request ) {
		$delivery_indication_service = new \VRPayment\Sdk\Service\DeliveryIndicationService( WC_VRPayment_Helper::instance()->get_api_client() );
		return $delivery_indication_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $delivery_indication delivery indication.
	 * @return int|string
	 */
	protected function get_order_id( $delivery_indication ) {
		/* @var \VRPayment\Sdk\Model\DeliveryIndication $delivery_indication */ //phpcs:ignore
		return WC_VRPayment_Entity_Transaction_Info::load_by_transaction( $delivery_indication->getTransaction()->getLinkedSpaceId(), $delivery_indication->getTransaction()->getId() )->get_order_id();
	}

	/**
	 * Get transaction id.
	 *
	 * @param mixed $delivery_indication delivery indication.
	 * @return int
	 */
	protected function get_transaction_id( $delivery_indication ) {
		/* @var \VRPayment\Sdk\Model\DeliveryIndication $delivery_indication */ //phpcs:ignore
		return $delivery_indication->getLinkedTransaction();
	}

	/**
	 * Process order related inner.
	 *
	 * @param WC_Order $order order.
	 * @param mixed $delivery_indication delivery indication.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, $delivery_indication ) {
		/* @var \VRPayment\Sdk\Model\DeliveryIndication $delivery_indication */ //phpcs:ignore
		switch ( $delivery_indication->getState() ) {
			case \VRPayment\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED:
				$this->review( $order );
				break;
			default:
				// Nothing to do.
				break;
		}
	}

	/**
	 * Review.
	 *
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function review( WC_Order $order ) {
		$order->add_meta_data( '_vrpayment_manual_check', true );
		$status = apply_filters( 'wc_vrpayment_manual_task_status', 'vrpaym-manual', $order );
		$status = apply_filters( 'vrpayment_order_update_status', $order, $status, esc_html__( 'A manual decision about whether to accept the payment is required.', 'woo-vrpayment' ) );
		$order->update_status( $status, esc_html__( 'A manual decision about whether to accept the payment is required.', 'woo-vrpayment' ) );
	}
}
