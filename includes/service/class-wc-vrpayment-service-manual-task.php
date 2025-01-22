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
 * This service provides methods to handle manual tasks.
 */
class WC_VRPayment_Service_Manual_Task extends WC_VRPayment_Service_Abstract {
	const VRPAYMENT_CONFIG_KEY = 'wc_vrpayment_manual_task';

	/**
	 * Returns the number of open manual tasks.
	 *
	 * @return int
	 */
	public function get_number_of_manual_tasks() {
		return get_option( self::VRPAYMENT_CONFIG_KEY, 0 );
	}

	/**
	 * Updates the number of open manual tasks.
	 *
	 * @return int
	 */
	public function update() {
		$number_of_manual_tasks = 0;
		$manual_task_service = new \VRPayment\Sdk\Service\ManualTaskService( WC_VRPayment_Helper::instance()->get_api_client() );

		$space_id = get_option( WooCommerce_VRPayment::VRPAYMENT_CK_SPACE_ID );
		if ( ! empty( $space_id ) ) {
			$number_of_manual_tasks = $manual_task_service->count(
				$space_id,
				$this->create_entity_filter( 'state', \VRPayment\Sdk\Model\ManualTaskState::OPEN )
			);
			update_option( self::VRPAYMENT_CONFIG_KEY, $number_of_manual_tasks );
		}

		return $number_of_manual_tasks;
	}
}
