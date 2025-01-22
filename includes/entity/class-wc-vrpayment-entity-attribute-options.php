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
 * Class WC_VRPayment_Entity_Attribute_Options.
 * This entity holds data about a the product attribute options.
 *
 * @class WC_VRPayment_Entity_Attribute_Options
 * @method int get_id()
 * @method int get_attribute_id()
 * @method void set_attribute_id(int $id)
 * @method boolean get_send()
 * @method void set_send(boolean $send)
 */
class WC_VRPayment_Entity_Attribute_Options extends WC_VRPayment_Entity_Abstract {
	/**
	 * Get field definition.
	 */
	protected static function get_field_definition() {
		return array(
			'attribute_id' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_INTEGER,
			'send' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_BOOLEAN,
		);
	}

	/**
	 * Get base fields.
	 */
	protected static function get_base_fields() {
		return array(
			'id' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_INTEGER,
		);
	}

	/**
	 * Get table name.
	 */
	protected static function get_table_name() {
		return 'vrpayment_attribute_options';
	}

	/**
	 * Prepare base fields for storage.
	 *
	 * @param array $data_array data array.
	 * @param array $type_array type array.
	 */
	protected function prepare_base_fields_for_storage( &$data_array, &$type_array ) {}

	/**
	 * Load attribute by ID.
	 *
	 * @param mixed $attribute_id attribute id.
	 */
	public static function load_by_attribute_id( $attribute_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::get_table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE attribute_id = %d",
				$attribute_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.
		if ( null !== $result ) {
			return new self( $result );
		}
		return new self();
	}
}
