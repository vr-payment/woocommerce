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
 * Class WC_VRPayment_Admin_Refund.
 * WC VRPayment Admin Refund class
 *
 * @class WC_VRPayment_Admin_Refund
 */
class WC_VRPayment_Admin_Refund {
	/**
	 * Refundable states
	 *
	 * @var array
	 */
	private static $refundable_states = array(
		\VRPayment\Sdk\Model\TransactionState::COMPLETED,
		\VRPayment\Sdk\Model\TransactionState::DECLINE,
		\VRPayment\Sdk\Model\TransactionState::FULFILL,
	);

	/**
	 * Init
	 *
	 * @return void
	 */
	public static function init() {
		add_action(
			'woocommerce_order_item_add_action_buttons',
			array(
				__CLASS__,
				'render_refund_button_state',
			),
			1000
		);

		add_action(
			'woocommerce_create_refund',
			array(
				__CLASS__,
				'store_refund_in_globals',
			),
			10,
			2
		);
		add_action(
			'vrpayment_five_minutes_cron',
			array(
				__CLASS__,
				'update_refunds',
			)
		);

		add_action(
			'woocommerce_admin_order_items_after_refunds',
			array(
				__CLASS__,
				'render_refund_states',
			),
			1000,
			1
		);

		add_action(
			'vrpayment_update_running_jobs',
			array(
				__CLASS__,
				'update_for_order',
			)
		);
	}

	/**
	 * Render refund button
	 *
	 * @param WC_Order $order Wc Order.
	 * @return void
	 */
	public static function render_refund_button_state( WC_Order $order ) {
		$gateway = wc_get_payment_gateway_by_order( $order );
		if ( $gateway instanceof WC_VRPayment_Gateway ) {
			$transaction_info = WC_VRPayment_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
			if ( ! in_array( $transaction_info->get_state(), self::$refundable_states, true ) ) {
				echo '<span id="vrpayment-remove-refund" style="display:none;"></span>';
			} else {
				$existing_refund_job = WC_VRPayment_Entity_Refund_Job::load_running_refund_for_transaction(
					$transaction_info->get_space_id(),
					$transaction_info->get_transaction_id()
				);
				if ( $existing_refund_job->get_id() > 0 ) {
					printf( '<span class="vrpayment-action-in-progress">%s</span>', esc_html__( 'There is a refund in progress.', 'woo-vrpayment' ) );
					printf( '<button type="button" class="button vrpayment-update-order">%s</button>', esc_html__( 'Update', 'woo-vrpayment' ) );
					printf( '<span id="vrpayment-remove-refund" style="display:none;"></span>' );
				}
				printf( '<span id="vrpayment-refund-restrictions" style="display:none;"></span>' );
			}
		}
	}

	/**
	 * Render refund states.
	 *
	 * @param mixed $order_id order_id.
	 * @return void
	 */
	public static function render_refund_states( $order_id ) {
		$refunds = WC_VRPayment_Entity_Refund_Job::load_refunds_for_order( $order_id );
		if ( ! empty( $refunds ) ) {
			echo '<tr style="display:none"><td>';
			foreach ( $refunds as $refund ) {
				echo '<div class="vrpayment-refund-status" data-refund-id="' . esc_attr( $refund->get_wc_refund_id() ) . '" data-refund-state="' .
					esc_attr( $refund->get_state() ) . '" ></div>';
			}
			echo '</td></tr>';
		}
	}

	/**
	 * Store refund in globals.
	 *
	 * @param mixed $refund refund.
	 * @param mixed $request_args  wc_order.
	 * @return void
	 */
	public static function store_refund_in_globals( $refund, $request_args ) {
		$GLOBALS['vrpayment_refund_id'] = $refund->get_id();
		$GLOBALS['vrpayment_refund_request_args'] = $request_args;
	}

	/**
	 * Executes refund.
	 *
	 * @param WC_Order $order wc_order.
	 * @param WC_Order_Refund $refund refund.
	 * @return void
	 * @throws Exception Exception.
	 */
	public static function execute_refund( WC_Order $order, WC_Order_Refund $refund ) {
		$current_refund_job_id = null;
		$transaction_info = null;
		$refund_service = WC_VRPayment_Service_Refund::instance();
		try {
			WC_VRPayment_Helper::instance()->start_database_transaction();
			$transaction_info = WC_VRPayment_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
			if ( ! $transaction_info->get_id() ) {
				throw new Exception( esc_html__( 'Could not load corresponding transaction', 'woo-vrpayment' ) );
			}

			WC_VRPayment_Helper::instance()->lock_by_transaction_id( $transaction_info->get_space_id(), $transaction_info->get_transaction_id() );

			if ( WC_VRPayment_Entity_Refund_Job::count_running_refund_for_transaction(
				$transaction_info->get_space_id(),
				$transaction_info->get_transaction_id()
			) > 0 ) {
				throw new Exception( esc_html__( 'Please wait until the pending refund is processed.', 'woo-vrpayment' ) );
			}
			$refund_create = $refund_service->create( $order, $refund );
			$refund_job = self::create_refund_job( $order, $refund, $refund_create );
			$current_refund_job_id = $refund_job->get_id();

			$refund->add_meta_data( '_vrpayment_refund_job_id', $refund_job->get_id() );
			$refund->set_status( 'pending' );
			$refund->save();
			WC_VRPayment_Helper::instance()->commit_database_transaction();
		} catch ( Exception $e ) {
			WC_VRPayment_Helper::instance()->rollback_database_transaction();
			throw $e;
		}
		self::send_refund( $current_refund_job_id );
	}

	/**
	 * Sends refund.
	 *
	 * @param mixed $refund_job_id refund_job_id.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected static function send_refund( $refund_job_id ) {
		$refund_job = WC_VRPayment_Entity_Refund_Job::load_by_id( $refund_job_id );
		WC_VRPayment_Helper::instance()->start_database_transaction();
		WC_VRPayment_Helper::instance()->lock_by_transaction_id( $refund_job->get_space_id(), $refund_job->get_transaction_id() );
		// Reload void job.
		$refund_job = WC_VRPayment_Entity_Refund_Job::load_by_id( $refund_job_id );

		if ( $refund_job->get_state() != WC_VRPayment_Entity_Refund_Job::VRPAYMENT_STATE_CREATED ) {
			// Already sent in the meantime.
			WC_VRPayment_Helper::instance()->rollback_database_transaction();
			return;
		}
		try {
			$refund_service  = WC_VRPayment_Service_Refund::instance();
			$executed_refund = $refund_service->refund( $refund_job->get_space_id(), $refund_job->get_refund() );
			$refund_job->set_state( WC_VRPayment_Entity_Refund_Job::VRPAYMENT_STATE_SENT );

			if ( $executed_refund->getState() == \VRPayment\Sdk\Model\RefundState::PENDING ) {
				$refund_job->set_state( WC_VRPayment_Entity_Refund_Job::VRPAYMENT_STATE_PENDING );
			}
			$refund_job->save();
			WC_VRPayment_Helper::instance()->commit_database_transaction();
		} catch ( \VRPayment\Sdk\ApiException $e ) {
			$error_message = $e->getMessage();
			if ( $e->getResponseObject() instanceof \VRPayment\Sdk\Model\ClientError ) {
				$refund_job->set_failure_reason(
					array(
						'en-US' => $e->getResponseObject()->getMessage(),
					)
				);
				$refund_job->set_state( WC_VRPayment_Entity_Refund_Job::VRPAYMENT_STATE_FAILURE );
				$refund_job->save();
				WC_VRPayment_Helper::instance()->commit_database_transaction();
			} else {
				$refund_job->save();
				WC_VRPayment_Helper::instance()->commit_database_transaction();
				WooCommerce_VRPayment::instance()->log( 'Error sending refund. ' . $error_message, WC_Log_Levels::INFO );
				/* translators: %s: message */
				throw new Exception( sprintf( esc_html__( 'There has been an error while sending the refund to the gateway. Error: %s', 'woo-vrpayment' ), esc_html( $error_message ) ) );
			}
		} catch ( Exception $e ) {
			$refund_job->save();
			$error_message = $e->getMessage();
			WC_VRPayment_Helper::instance()->commit_database_transaction();
			WooCommerce_VRPayment::instance()->log( 'Error sending refund. ' . $error_message, WC_Log_Levels::INFO );
			/* translators: %s: message */
			throw new Exception( sprintf( esc_html__( 'There has been an error while sending the refund to the gateway. Error: %s', 'woo-vrpayment' ), esc_html( $error_message ) ) );
		}
	}

	/**
	 * Updates for order.
	 *
	 * @param WC_Order $order wc_order.
	 * @return void
	 */
	public static function update_for_order( WC_Order $order ) {

		$transaction_info = WC_VRPayment_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		$refund_job = WC_VRPayment_Entity_Refund_Job::load_running_refund_for_transaction( $transaction_info->get_space_id(), $transaction_info->get_transaction_id() );

		if ( $refund_job->get_state() == WC_VRPayment_Entity_Refund_Job::VRPAYMENT_STATE_CREATED ) {
			self::send_refund( $refund_job->get_id() );
		}
	}

	/**
	 * Updates refunds.
	 *
	 * @return void
	 */
	public static function update_refunds() {
		$to_process = WC_VRPayment_Entity_Refund_Job::load_not_sent_job_ids();
		foreach ( $to_process as $id ) {
			try {
				self::send_refund( $id );
			} catch ( Exception $e ) {
				/* translators: %d: id, %s: message */
				$message = sprintf( esc_html__( 'Error updating refund job with id %1$d: %2$s', 'woo-vrpayment' ), $id, $e->getMessage() );
				WooCommerce_VRPayment::instance()->log( $message, WC_Log_Levels::ERROR );
			}
		}
	}

	/**
	 * Creates a new refund job for the given order and refund.
	 *
	 * @param WC_Order $order wc_order.
	 * @param WC_Order_Refund $refund refund.
	 * @param \VRPayment\Sdk\Model\RefundCreate $refund_create refund_create.
	 * @return WC_VRPayment_Entity_Refund_Job
	 */
	private static function create_refund_job( WC_Order $order, WC_Order_Refund $refund, \VRPayment\Sdk\Model\RefundCreate $refund_create ) {
		$transaction_info = WC_VRPayment_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		$refund_job = new WC_VRPayment_Entity_Refund_Job();
		$refund_job->set_state( WC_VRPayment_Entity_Refund_Job::VRPAYMENT_STATE_CREATED );
		$refund_job->set_wc_refund_id( $refund->get_id() );
		$refund_job->set_order_id( $order->get_id() );
		$refund_job->set_space_id( $transaction_info->get_space_id() );
		$refund_job->set_transaction_id( $refund_create->getTransaction() );
		$refund_job->set_external_id( $refund_create->getExternalId() );
		$refund_job->set_refund( $refund_create );
		$refund_job->save();
		return $refund_job;
	}
}
WC_VRPayment_Admin_Refund::init();
