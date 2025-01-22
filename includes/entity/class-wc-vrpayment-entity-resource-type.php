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
 * Defines the different resource types
 */
interface WC_VRPayment_Entity_Resource_Type {
	const VRPAYMENT_STRING = 'string';
	const VRPAYMENT_DATETIME = 'datetime';
	const VRPAYMENT_INTEGER = 'integer';
	const VRPAYMENT_BOOLEAN = 'boolean';
	const VRPAYMENT_OBJECT = 'object';
	const VRPAYMENT_DECIMAL = 'decimal';
}
