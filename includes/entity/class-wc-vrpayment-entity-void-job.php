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
 * This entity holds data about a transaction on the gateway.
 *
 * @method int get_id()
 * @method int get_void_id()
 * @method void set_void_id(int $id)
 * @method string get_state()
 * @method void set_state(string $state)
 * @method int get_space_id()
 * @method void set_space_id(int $id)
 * @method int get_transaction_id()
 * @method void set_transaction_id(int $id)
 * @method int get_order_id()
 * @method void set_order_id(int $id)
 * @method boolean get_restock()
 * @method void set_restock(boolean $items)
 * @method void set_failure_reason(map[string,string] $reasons)
 */
class WC_VRPayment_Entity_Void_Job extends WC_VRPayment_Entity_Abstract {
	const VRPAYMENT_STATE_CREATED = 'created';
	const VRPAYMENT_STATE_SENT = 'sent';
	const VRPAYMENT_STATE_DONE = 'done';

	/**
	 * Get field definition.
	 *
	 * @return array
	 */
	protected static function get_field_definition() {
		return array(
			'void_id' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_INTEGER,
			'state' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_STRING,
			'space_id' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_INTEGER,
			'transaction_id' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_INTEGER,
			'order_id' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_INTEGER,
			'restock' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_BOOLEAN,
			'failure_reason' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_OBJECT,

		);
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	protected static function get_table_name() {
		return 'vrpayment_void_job';
	}


	/**
	 * Get failure reason.
	 *
	 * @param mixed $language language.
	 * @return string|null
	 */
	public function get_failure_reason( $language = null ) {
		$value = $this->get_value( 'failure_reason' );
		if ( empty( $value ) ) {
			return null;
		}

		return WC_VRPayment_Helper::instance()->translate( $value, $language );
	}

	/**
	 * Load by void.
	 *
	 * @param mixed $space_id space id.
	 * @param mixed $void_id void id.
	 * @return WC_VRPayment_Entity_Void_Job
	 */
	public static function load_by_void( $space_id, $void_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::get_table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$result = $wpdb->get_row( //phpcs:ignore
			$wpdb->prepare(
				"SELECT * FROM $table WHERE space_id = %d AND void_id = %d",
				$space_id,
				$void_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.
		if ( null !== $result ) {
			return new self( $result );
		}
		return new self();
	}

	/**
	 * Count running void for transaction.
	 *
	 * @param mixed $space_id space id.
	 * @param mixed $transaction_id transaction id.
	 * @return string|null
	 */
	public static function count_running_void_for_transaction( $space_id, $transaction_id ) {
		global $wpdb;

		$table = $wpdb->prefix . self::get_table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE space_id = %d AND transaction_id = %d AND state != %s",
				$space_id,
				$transaction_id,
				self::VRPAYMENT_STATE_DONE
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.
		return $result;
	}

	/**
	 * Load running void for transaction.
	 *
	 * @param mixed $space_id space id.
	 * @param mixed $transaction_id transaction id.
	 * @return WC_VRPayment_Entity_Void_Job
	 */
	public static function load_running_void_for_transaction( $space_id, $transaction_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::get_table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE space_id = %d AND transaction_id = %d AND state != %s",
				$space_id,
				$transaction_id,
				self::VRPAYMENT_STATE_DONE
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.
		if ( null !== $result ) {
			return new self( $result );
		}
		return new self();
	}

	/**
	 * Load not sent job ids
	 *
	 * @return array
	 */
	public static function load_not_sent_job_ids() {
		global $wpdb;
		// Returns empty array.

		$time = new DateTime();
		$time->sub( new DateInterval( 'PT10M' ) );
		$table = $wpdb->prefix . self::get_table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$db_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id FROM $table WHERE state = %s AND updated_at < %s",
				self::VRPAYMENT_STATE_CREATED,
				$time->format( 'Y-m-d H:i:s' )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.
		$result = array();
		if ( is_array( $db_results ) ) {
			foreach ( $db_results as $object_values ) {
				$result[] = $object_values['id'];
			}
		}
		return $result;
	}
}
