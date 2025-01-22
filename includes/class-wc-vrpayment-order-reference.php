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
 * Class WC_VRPayment_Order_Reference.
 * This class handles the database setup and migration.
 *
 * @class WC_VRPayment_Order_Reference
 */
class WC_VRPayment_Order_Reference {
	const VRPAYMENT_ORDER_ID = 'order_id';
	const VRPAYMENT_ORDER_NUMBER = 'order_number';
}
