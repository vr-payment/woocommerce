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
 * Webhook processor to handle transaction state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_VRPayment_Webhook_Transaction_Strategy
 */
class WC_VRPayment_Webhook_Transaction extends WC_VRPayment_Webhook_Order_Related_Abstract {

	/**
	 * Load entity.
	 *
	 * @param WC_VRPayment_Webhook_Request $request request.
	 * @return object|\VRPayment\Sdk\Model\Transaction
	 * @throws \VRPayment\Sdk\ApiException ApiException.
	 * @throws \VRPayment\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \VRPayment\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_VRPayment_Webhook_Request $request ) {
		$transaction_service = new \VRPayment\Sdk\Service\TransactionService( WC_VRPayment_Helper::instance()->get_api_client() );
		return $transaction_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $transaction transaction.
	 * @return int|string
	 */
	protected function get_order_id( $transaction ) {
		/* @var \VRPayment\Sdk\Model\Transaction $transaction */ //phpcs:ignore
		return WC_VRPayment_Entity_Transaction_Info::load_by_transaction( $transaction->getLinkedSpaceId(), $transaction->getId() )->get_order_id();
	}

	/**
	 * Get transaction id.
	 *
	 * @param mixed $transaction transaction.
	 * @return int
	 */
	protected function get_transaction_id( $transaction ) {
		/* @var \VRPayment\Sdk\Model\Transaction $transaction */ //phpcs:ignore
		return $transaction->getId();
	}

	/**
	 * Process order related inner.
	 *
	 * @param WC_Order $order order.
	 * @param mixed $transaction transaction.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function process_order_related_inner( WC_Order $order, $transaction ) {

		/* @var \VRPayment\Sdk\Model\Transaction $transaction */ //phpcs:ignore
		$transaction_info = WC_VRPayment_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		if ( $transaction->getState() != $transaction_info->get_state() ) {
			switch ( $transaction->getState() ) {
				case \VRPayment\Sdk\Model\TransactionState::CONFIRMED:
				case \VRPayment\Sdk\Model\TransactionState::PROCESSING:
					$this->confirm( $transaction, $order );
					break;
				case \VRPayment\Sdk\Model\TransactionState::AUTHORIZED:
					$this->authorize( $transaction, $order );
					break;
				case \VRPayment\Sdk\Model\TransactionState::DECLINE:
					$this->decline( $transaction, $order );
					break;
				case \VRPayment\Sdk\Model\TransactionState::FAILED:
					$this->failed( $transaction, $order );
					break;
				case \VRPayment\Sdk\Model\TransactionState::FULFILL:
					$this->authorize( $transaction, $order );
					$this->fulfill( $transaction, $order );
					break;
				case \VRPayment\Sdk\Model\TransactionState::VOIDED:
					$this->voided( $transaction, $order );
					break;
				case \VRPayment\Sdk\Model\TransactionState::COMPLETED:
					$this->authorize( $transaction, $order );
					$this->waiting( $transaction, $order );
					break;
				default:
					// Nothing to do.
					break;
			}
		}

		WC_VRPayment_Service_Transaction::instance()->update_transaction_info( $transaction, $order );
	}

	/**
	 * Confirm.
	 *
	 * @param \VRPayment\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function confirm( \VRPayment\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		if ( ! $order->get_meta( '_vrpayment_confirmed', true ) && ! $order->get_meta( '_vrpayment_authorized', true ) ) {
			do_action( 'wc_vrpayment_confirmed', $transaction, $order );
			$order->add_meta_data( '_vrpayment_confirmed', 'true', true );
			$default_status = apply_filters( 'wc_vrpayment_confirmed_status', 'vrpaym-redirected', $order );
			apply_filters( 'vrpayment_order_update_status', $order, $transaction->getState(), $default_status );
			wc_maybe_reduce_stock_levels( $order->get_id() );
		}
	}

	/**
	 * Authorize.
	 *
	 * @param \VRPayment\Sdk\Model\Transaction $transaction transaction.
	 * @param \WC_Order $order order.
	 */
	protected function authorize( \VRPayment\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		if ( ! $order->get_meta( '_vrpayment_authorized', true ) ) {
			do_action( 'wc_vrpayment_authorized', $transaction, $order );
			$order->add_meta_data( '_vrpayment_authorized', 'true', true );
			$default_status = apply_filters( 'wc_vrpayment_authorized_status', 'on-hold', $order );
			apply_filters( 'vrpayment_order_update_status', $order, $transaction->getState(), $default_status );
			wc_maybe_reduce_stock_levels( $order->get_id() );
			if ( isset( WC()->cart ) ) {
				WC()->cart->empty_cart();
			}
		}
	}

	/**
	 * Waiting.
	 *
	 * @param \VRPayment\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function waiting( \VRPayment\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		if ( ! $order->get_meta( '_vrpayment_manual_check', true ) ) {
			do_action( 'wc_vrpayment_completed', $transaction, $order );
			$default_status = apply_filters( 'wc_vrpayment_completed_status', 'vrpaym-waiting', $order );
			apply_filters( 'vrpayment_order_update_status', $order, $transaction->getState(), $default_status );
		}
	}

	/**
	 * Decline.
	 *
	 * @param \VRPayment\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function decline( \VRPayment\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		do_action( 'wc_vrpayment_declined', $transaction, $order );
		$default_status = apply_filters( 'wc_vrpayment_decline_status', 'cancelled', $order );
		apply_filters( 'vrpayment_order_update_status', $order, $transaction->getState(), $default_status );
		WC_VRPayment_Helper::instance()->maybe_restock_items_for_order( $order );
	}

	/**
	 * Failed.
	 *
	 * @param \VRPayment\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function failed( \VRPayment\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		do_action( 'wc_vrpayment_failed', $transaction, $order );
		$valid_order_statuses = array(
			// Default pending status.
			'pending',
			// Custom order statuses mapped.
			apply_filters( 'vrpayment_wc_status_for_transaction', 'confirmed' ),
			apply_filters( 'vrpayment_wc_status_for_transaction', 'failed' )
		);
		if ( in_array( $order->get_status( 'edit' ), $valid_order_statuses ) ) {
			$default_status = apply_filters( 'wc_vrpayment_failed_status', 'failed', $order );
			apply_filters( 'vrpayment_order_update_status', $order, $transaction->getState(), $default_status );
			WC_VRPayment_Helper::instance()->maybe_restock_items_for_order( $order );
		}
	}

	/**
	 * Fulfill.
	 *
	 * @param \VRPayment\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function fulfill( \VRPayment\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		do_action( 'wc_vrpayment_fulfill', $transaction, $order );
		// Sets the status to procesing or complete depending on items.
		$order->payment_complete( $transaction->getId() );
	}

	/**
	 * Voided.
	 *
	 * @param \VRPayment\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function voided( \VRPayment\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		$default_status = apply_filters( 'wc_vrpayment_voided_status', 'cancelled', $order );
		apply_filters( 'vrpayment_order_update_status', $order, $transaction->getState(), $default_status );
		do_action( 'wc_vrpayment_voided', $transaction, $order );
	}
}
