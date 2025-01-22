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
 * Class WC_VRPayment_Cron.
 * This class handles the cron jobs
 *
 * @class WC_VRPayment_Cron
 */
class WC_VRPayment_Cron {

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action(
			'cron_schedules',
			array(
				__CLASS__,
				'add_custom_cron_schedule',
			),
			5
		);
	}

	/**
	 * Add cron schedule.
	 *
	 * @param  array $schedules schedules.
	 * @return array
	 */
	public static function add_custom_cron_schedule( $schedules ) {
		$schedules['five_minutes'] = array(
			'interval' => 300,
			'display'  => esc_html__( 'Every Five Minutes' ),
		);
		return $schedules;
	}

	/**
	 * Activate the cron.
	 */
	public static function activate() {
		if ( ! wp_next_scheduled( 'vrpayment_five_minutes_cron' ) ) {
			wp_schedule_event( time(), 'five_minutes', 'vrpayment_five_minutes_cron' );
		}
	}

	/**
	 * Deactivate the cron.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'vrpayment_five_minutes_cron' );
	}
}
WC_VRPayment_Cron::init();
