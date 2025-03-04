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
 * Class WC_VRPayment_Download_Helper.
 * This class provides function to download documents from VRPay
 *
 * @class WC_VRPayment_Download_Helper
 */
class WC_VRPayment_Download_Helper {

	/**
	 * Downloads the transaction's invoice PDF document.
	 *
	 * @param int $order_id order id.
	 */
	public static function download_invoice( $order_id ) {
		$transaction_info = WC_VRPayment_Entity_Transaction_Info::load_by_order_id( $order_id );
		if ( ! is_null( $transaction_info->get_id() ) && in_array(
			$transaction_info->get_state(),
			array(
				\VRPayment\Sdk\Model\TransactionState::COMPLETED,
				\VRPayment\Sdk\Model\TransactionState::FULFILL,
				\VRPayment\Sdk\Model\TransactionState::DECLINE,
			),
			true
		) ) {

			$service = new \VRPayment\Sdk\Service\TransactionService( WC_VRPayment_Helper::instance()->get_api_client() );
			$document = $service->getInvoiceDocument( $transaction_info->get_space_id(), $transaction_info->get_transaction_id() );
			self::download( $document );
		}
	}

	/**
	 * Downloads the transaction's packing slip PDF document.
	 *
	 * @param int $order_id order id.
	 */
	public static function download_packing_slip( $order_id ) {
		$transaction_info = WC_VRPayment_Entity_Transaction_Info::load_by_order_id( $order_id );
		if ( ! is_null( $transaction_info->get_id() ) && $transaction_info->get_state() == \VRPayment\Sdk\Model\TransactionState::FULFILL ) {

			$service = new \VRPayment\Sdk\Service\TransactionService( WC_VRPayment_Helper::instance()->get_api_client() );
			$document = $service->getPackingSlip( $transaction_info->get_space_id(), $transaction_info->get_transaction_id() );
			self::download( $document );
		}
	}

	/**
	 * Sends the data received by calling the given path to the browser and ends the execution of the script
	 *
	 * @param \VRPayment\Sdk\Model\RenderedDocument $document document.
	 */
	public static function download( \VRPayment\Sdk\Model\RenderedDocument $document ) {
		header( 'Pragma: public' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . esc_html( $document->getTitle() ) . '.pdf"' );
		header( 'Content-Description: ' . esc_html( $document->getTitle() ) );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$data_safe = base64_decode( $document->getData() );
		// The following line outputs binary PDF data, escaping is not applied as it's not HTML content.
		echo $data_safe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit();
	}
}
