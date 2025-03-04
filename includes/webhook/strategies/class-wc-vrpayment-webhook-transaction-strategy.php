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
 * Class WC_VRPayment_Webhook_Transaction_Strategy
 *
 * This class provides the implementation for processing transaction webhooks.
 * It includes methods for handling specific actions that need to be taken when
 * transaction-related webhook notifications are received, such as updating order
 * statuses, recording transaction logs, or triggering further business logic.
 */
class WC_VRPayment_Webhook_Transaction_Strategy extends WC_VRPayment_Webhook_Strategy_Base {

	/**
	 * Match function.
	 *
	 * @inheritDoc
	 * @param string $webhook_entity_id The webhook entity id.
	 */
	public function match( string $webhook_entity_id ) {
		return WC_VRPayment_Service_Webhook::VRPAYMENT_TRANSACTION == $webhook_entity_id;
	}

	/**
	 * Process the webhook request.
	 *
	 * @param WC_VRPayment_Webhook_Request $request The webhook request object.
	 * @return mixed The result of the processing.
	 */
	public function process( WC_VRPayment_Webhook_Request $request ) {
		$order = $this->get_order( $request );
		if ( false != $order && $order->get_id() ) {
			$this->process_order_related_inner( $order, $request );
		}
	}

	/**
	 * Process order related inner.
	 *
	 * @param WC_Order $order order.
	 * @param WC_VRPayment_Webhook_Request $request request.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function process_order_related_inner( WC_Order $order, WC_VRPayment_Webhook_Request $request ) {
		$transaction_info = WC_VRPayment_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		if ( $request->get_state() != $transaction_info->get_state() ) {
			switch ( $request->get_state() ) {
				case \VRPayment\Sdk\Model\TransactionState::CONFIRMED:
				case \VRPayment\Sdk\Model\TransactionState::PROCESSING:
					$this->confirm( $request, $order );
					break;
				case \VRPayment\Sdk\Model\TransactionState::AUTHORIZED:
					$this->authorize( $request, $order );
					break;
				case \VRPayment\Sdk\Model\TransactionState::DECLINE:
					$this->decline( $request, $order );
					break;
				case \VRPayment\Sdk\Model\TransactionState::FAILED:
					$this->failed( $request, $order );
					break;
				case \VRPayment\Sdk\Model\TransactionState::FULFILL:
					$this->authorize( $request, $order );
					$this->fulfill( $request, $order );
					break;
				case \VRPayment\Sdk\Model\TransactionState::VOIDED:
					$this->voided( $request, $order );
					break;
				case \VRPayment\Sdk\Model\TransactionState::COMPLETED:
					$this->authorize( $request, $order );
					$this->waiting( $request, $order );
					break;
				default:
					// Nothing to do.
					break;
			}
		}

		WC_VRPayment_Service_Transaction::instance()->update_transaction_info( $this->load_entity( $request ), $order );
	}

	/**
	 * Confirm.
	 *
	 * @param WC_VRPayment_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function confirm( WC_VRPayment_Webhook_Request $request, WC_Order $order ) {
		if ( ! $order->get_meta( '_vrpayment_confirmed', true ) && ! $order->get_meta( '_vrpayment_authorized', true ) ) {
			do_action( 'wc_vrpayment_confirmed', $this->load_entity( $request ), $order );
			$order->add_meta_data( '_vrpayment_confirmed', 'true', true );
			$default_status = apply_filters( 'wc_vrpayment_confirmed_status', 'vrpaym-redirected', $order );
			apply_filters( 'vrpayment_order_update_status', $order, \VRPayment\Sdk\Model\TransactionState::CONFIRMED, $default_status );
			wc_maybe_reduce_stock_levels( $order->get_id() );
		}
	}

	/**
	 * Authorize.
	 *
	 * @param WC_VRPayment_Webhook_Request $request request.
	 * @param \WC_Order $order order.
	 */
	protected function authorize( WC_VRPayment_Webhook_Request $request, WC_Order $order ) {
		if ( ! $order->get_meta( '_vrpayment_authorized', true ) ) {
			do_action( 'wc_vrpayment_authorized', $this->load_entity( $request ), $order );
			$order->add_meta_data( '_vrpayment_authorized', 'true', true );
			$default_status = apply_filters( 'wc_vrpayment_authorized_status', 'on-hold', $order );
			apply_filters( 'vrpayment_order_update_status', $order, \VRPayment\Sdk\Model\TransactionState::AUTHORIZED, $default_status );
			wc_maybe_reduce_stock_levels( $order->get_id() );
			if ( isset( WC()->cart ) ) {
				WC()->cart->empty_cart();
			}
		}
	}

	/**
	 * Waiting.
	 *
	 * @param WC_VRPayment_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function waiting( WC_VRPayment_Webhook_Request $request, WC_Order $order ) {
		if ( ! $order->get_meta( '_vrpayment_manual_check', true ) ) {
			do_action( 'wc_vrpayment_completed', $this->load_entity( $request ), $order );
			$default_status = apply_filters( 'wc_vrpayment_completed_status', 'processing', $order );
			apply_filters( 'vrpayment_order_update_status', $order, \VRPayment\Sdk\Model\TransactionState::COMPLETED, $default_status );
		}
	}

	/**
	 * Decline.
	 *
	 * @param WC_VRPayment_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function decline( WC_VRPayment_Webhook_Request $request, WC_Order $order ) {
		do_action( 'wc_vrpayment_declined', $this->load_entity( $request ), $order );
		$default_status = apply_filters( 'wc_vrpayment_decline_status', 'cancelled', $order );
		apply_filters( 'vrpayment_order_update_status', $order, \VRPayment\Sdk\Model\TransactionState::DECLINE, $default_status );
		WC_VRPayment_Helper::instance()->maybe_restock_items_for_order( $order );
	}

	/**
	 * Failed.
	 *
	 * @param WC_VRPayment_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function failed( WC_VRPayment_Webhook_Request $request, WC_Order $order ) {
		do_action( 'wc_vrpayment_failed', $this->load_entity( $request ), $order );
		$valid_order_statuses = array(
			// Default pending status.
			'pending',
			// Custom order statuses mapped.
			apply_filters( 'vrpayment_wc_status_for_transaction', 'confirmed' ),
			apply_filters( 'vrpayment_wc_status_for_transaction', 'failed' )
		);
		if ( in_array( $order->get_status( 'edit' ), $valid_order_statuses ) ) {
			$default_status = apply_filters( 'wc_vrpayment_failed_status', 'failed', $order );
			apply_filters( 'vrpayment_order_update_status', $order, \VRPayment\Sdk\Model\TransactionState::FAILED, $default_status, );
			WC_VRPayment_Helper::instance()->maybe_restock_items_for_order( $order );
		}
	}

	/**
	 * Fulfill.
	 *
	 * @param WC_VRPayment_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function fulfill( WC_VRPayment_Webhook_Request $request, WC_Order $order ) {
		do_action( 'wc_vrpayment_fulfill', $this->load_entity( $request ), $order );
		// Sets the status to procesing or complete depending on items.
		$order->payment_complete( $request->get_entity_id() );
	}

	/**
	 * Voided.
	 *
	 * @param WC_VRPayment_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function voided( WC_VRPayment_Webhook_Request $request, WC_Order $order ) {
		$default_status = apply_filters( 'wc_vrpayment_voided_status', 'cancelled', $order );
		apply_filters( 'vrpayment_order_update_status', $order, \VRPayment\Sdk\Model\TransactionState::VOIDED, $default_status );
		do_action( 'wc_vrpayment_voided', $this->load_entity( $request ), $order );
	}
}
