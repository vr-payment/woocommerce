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
 * Provider of payment method information from the gateway.
 */
class WC_VRPayment_Provider_Payment_Method extends WC_VRPayment_Provider_Abstract {

	/**
	 * Construct.
	 */
	protected function __construct() {
		parent::__construct( 'wc_vrpayment_payment_methods' );
	}

	/**
	 * Returns the payment method by the given id.
	 *
	 * @param int $id id.
	 * @return \VRPayment\Sdk\Model\PaymentMethod
	 */
	public function find( $id ) { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::find( $id );
	}

	/**
	 * Returns a list of payment methods.
	 *
	 * @return \VRPayment\Sdk\Model\PaymentMethod[]
	 */
	public function get_all() { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get_all();
	}

	/**
	 * Fetch data.
	 *
	 * @return array|\VRPayment\Sdk\Model\PaymentMethod[]
	 * @throws \VRPayment\Sdk\ApiException ApiException.
	 * @throws \VRPayment\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \VRPayment\Sdk\VersioningException VersioningException.
	 */
	protected function fetch_data() {
		$method_service = new \VRPayment\Sdk\Service\PaymentMethodService( WC_VRPayment_Helper::instance()->get_api_client() );
		return $method_service->all();
	}

	/**
	 * Get id.
	 *
	 * @param mixed $entry entry.
	 * @return int|string
	 */
	protected function get_id( $entry ) {
		/* @var \VRPayment\Sdk\Model\PaymentMethod $entry */ //phpcs:ignore
		return $entry->getId();
	}
}
