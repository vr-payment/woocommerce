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
 * Class WC_VRPayment_Webhook_Delivery_Indication_Strategy
 *
 * Handles strategy for processing delivery indication-related webhook requests.
 * This class extends the base webhook strategy to manage webhook requests specifically
 * dealing with delivery indications. It focuses on updating order states based on the delivery indication details
 * retrieved from the webhook data.
 */
class WC_VRPayment_Webhook_Delivery_Indication_Strategy extends WC_VRPayment_Webhook_Strategy_Base {

	/**
	 * Match function.
	 *
	 * @inheritDoc
	 * @param string $webhook_entity_id The webhook entity id.
	 */
	public function match( string $webhook_entity_id ) {
		return WC_VRPayment_Service_Webhook::VRPAYMENT_DELIVERY_INDICATION == $webhook_entity_id;
	}

	/**
	 * Load the entity
	 *
	 * @inheritDoc
	 * @param WC_VRPayment_Webhook_Request $request The webhook request.
	 */
	public function load_entity( WC_VRPayment_Webhook_Request $request ) {
		$delivery_indication_service = new \VRPayment\Sdk\Service\DeliveryIndicationService( WC_VRPayment_Helper::instance()->get_api_client() );
		return $delivery_indication_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Get the order ID.
	 *
	 * @inheritDoc
	 * @param object $object The webhook request.
	 */
	public function get_order_id( $object ) {
		/* @var \VRPayment\Sdk\Model\DeliveryIndication $object */
		return WC_VRPayment_Entity_Transaction_Info::load_by_transaction(
			$object->getTransaction()->getLinkedSpaceId(),
			$object->getTransaction()->getId()
		)->get_order_id();
	}

	/**
	 * Meant to bridge code from deprecated processor.
	 *
	 * @param WC_Order $order The WooCommerce order linked to the delivery indication.
	 * @param \VRPayment\Sdk\Model\DeliveryIndication $delivery_indication The delivery indication object.
	 * @param WC_VRPayment_Webhook_Request $request The webhook request.
	 * @return void
	 */
	public function bridge_process_order_related_inner( WC_Order $order, \VRPayment\Sdk\Model\DeliveryIndication $delivery_indication, WC_VRPayment_Webhook_Request $request ) {
        $this->process_order_related_inner( $order, $delivery_indication, $request, true );
    }

	/**
	 * Processes the incoming webhook request pertaining to delivery indications.
	 *
	 * This method retrieves the delivery indication details from the API and updates the associated
	 * WooCommerce order based on the indication state.
	 *
	 * @param WC_VRPayment_Webhook_Request $request The webhook request object.
	 * @return void
	 */
	public function process( WC_VRPayment_Webhook_Request $request ) {
		/* @var \VRPayment\Sdk\Model\DeliveryIndication $delivery_indication */
		$delivery_indication = $this->load_entity( $request );
		$order = $this->get_order( $delivery_indication );
		if ( false != $order && $order->get_id() ) {
			$this->process_order_related_inner( $order, $delivery_indication, $request );
		}
	}

	/**
	 * Additional processing on the order based on the state of the delivery indication.
	 *
	 * @param WC_Order $order The WooCommerce order linked to the delivery indication.
	 * @param \VRPayment\Sdk\Model\DeliveryIndication $delivery_indication The delivery indication object.
	 * @param WC_VRPayment_Webhook_Request $request The webhook request.
	 * @param bool $legacy_mode legacy code used.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, \VRPayment\Sdk\Model\DeliveryIndication $delivery_indication, WC_VRPayment_Webhook_Request $request, $legacy_mode = false ) {
		$entity_state = $legacy_mode ? $delivery_indication->getState() : $request->get_state();
		switch ( $entity_state ) {
			case \VRPayment\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED:
				$this->review( $order );
				break;
			default:
				// Nothing to do.
				break;
		}
	}

	/**
	 * Review and potentially update the order status based on manual review requirements.
	 *
	 * @param WC_Order $order The associated WooCommerce order.
	 * @return void
	 */
	protected function review( WC_Order $order ) {
		$order->add_meta_data( '_vrpayment_manual_check', true );
		$default_status = WC_VRPayment_Helper::map_status_to_current_mode( 'vrpaym-manual' );
		$status = apply_filters( 'wc_vrpayment_manual_task_status', $default_status, $order );
		$status = WC_VRPayment_Helper::map_status_to_current_mode( $status );
		$status = apply_filters( 'vrpayment_order_update_status', $order, $status, __( 'A manual decision about whether to accept the payment is required.', 'woo-vrpayment' ) );
		$order->update_status( $status, __( 'A manual decision about whether to accept the payment is required.', 'woo-vrpayment' ) );
	}
}
