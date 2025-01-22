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
 * Provider of label descriptor information from the gateway.
 */
class WC_VRPayment_Provider_Label_Description extends WC_VRPayment_Provider_Abstract {

	/**
	 * Construct.
	 */
	protected function __construct() {
		parent::__construct( 'wc_vrpayment_label_descriptions' );
	}

	/**
	 * Returns the label descriptor by the given code.
	 *
	 * @param int $id id.
	 * @return \VRPayment\Sdk\Model\LabelDescriptor
	 */
	public function find( $id ) { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::find( $id );
	}

	/**
	 * Returns a list of label descriptors.
	 *
	 * @return \VRPayment\Sdk\Model\LabelDescriptor[]
	 */
	public function get_all() { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get_all();
	}

	/**
	 * Fetch data.
	 *
	 * @return array|\VRPayment\Sdk\Model\LabelDescriptor[]
	 * @throws \VRPayment\Sdk\ApiException ApiException.
	 * @throws \VRPayment\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \VRPayment\Sdk\VersioningException VersioningException.
	 */
	protected function fetch_data() {
		$label_description_service = new \VRPayment\Sdk\Service\LabelDescriptionService( WC_VRPayment_Helper::instance()->get_api_client() );
		return $label_description_service->all();
	}

	/**
	 * Get Id.
	 *
	 * @param mixed $entry entry.
	 * @return int|string
	 */
	protected function get_id( $entry ) {
		/* @var \VRPayment\Sdk\Model\LabelDescriptor $entry */ //phpcs:ignore
		return $entry->getId();
	}
}
