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
 * @method int get_external_id()
 * @method void set_external_id(int $id)
 * @method string get_state()
 * @method void set_state(string $state)
 * @method int get_space_id()
 * @method void set_space_id(int $id)
 * @method int get_transaction_id()
 * @method void set_transaction_id(int $id)
 * @method int get_order_id()
 * @method void set_order_id(int $id)
 * @method int get_wc_refund_id()
 * @method void set_wc_refund_id(int $id)
 * @method \VRPayment\Sdk\Model\RefundCreate get_refund()
 * @method void set_refund( \VRPayment\Sdk\Model\RefundCreate  $refund)
 * @method void set_failure_reason(map[string,string] $reasons)
 */
class WC_VRPayment_Entity_Refund_Job extends WC_VRPayment_Entity_Abstract {
	const VRPAYMENT_STATE_CREATED = 'created';
	const VRPAYMENT_STATE_SENT = 'sent';
	const VRPAYMENT_STATE_PENDING = 'pending';
	const VRPAYMENT_STATE_SUCCESS = 'success';
	const VRPAYMENT_STATE_FAILURE = 'failure';

	/**
	 * Get field definition.
	 *
	 * @return array
	 */
	protected static function get_field_definition() {
		return array(
			'external_id' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_STRING,
			'state' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_STRING,
			'space_id' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_INTEGER,
			'transaction_id' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_INTEGER,
			'order_id' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_INTEGER,
			'wc_refund_id' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_INTEGER,
			'refund' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_OBJECT,
			'failure_reason' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_OBJECT,
		);
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	protected static function get_table_name() {
		return 'vrpayment_refund_job';
	}


	/**
	 * Returns the translated failure reason.
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
	 * Load by external Id.
	 *
	 * @param mixed $space_id space id.
	 * @param mixed $external_id external id.
	 * @return WC_VRPayment_Entity_Refund_Job
	 */
	public static function load_by_external_id( $space_id, $external_id ) {
		global $wpdb;

		$table = $wpdb->prefix . self::get_table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE space_id = %d AND external_id = %s",
				$space_id,
				$external_id
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
	 * Count running refund for transaction.
	 *
	 * @param mixed $space_id space id.
	 * @param mixed $transaction_id transaction id.
	 * @return string|null
	 */
	public static function count_running_refund_for_transaction( $space_id, $transaction_id ) {
		global $wpdb;

		$table = $wpdb->prefix . self::get_table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$result = $wpdb->get_var( //phpcs:ignore
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE space_id = %d AND transaction_id = %d AND state != %s AND state != %s",
				$space_id,
				$transaction_id,
				self::VRPAYMENT_STATE_SUCCESS,
				self::VRPAYMENT_STATE_FAILURE
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.
		return $result;
	}

	/**
	 * Load running refund for transaction.
	 *
	 * @param mixed $space_id Space id.
	 * @param mixed $transaction_id transaction id.
	 * @return WC_VRPayment_Entity_Refund_Job
	 */
	public static function load_running_refund_for_transaction( $space_id, $transaction_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::get_table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$result = $wpdb->get_row( //phpcs:ignore
			$wpdb->prepare(
				"SELECT * FROM $table WHERE space_id = %d AND transaction_id = %d AND state != %s AND state != %s",
				$space_id,
				$transaction_id,
				self::VRPAYMENT_STATE_SUCCESS,
				self::VRPAYMENT_STATE_FAILURE
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
	 * Load refunds for order.
	 *
	 * @param mixed $order_id order id.
	 * @return array
	 */
	public static function load_refunds_for_order( $order_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::get_table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$db_results = $wpdb->get_results( //phpcs:ignore
			$wpdb->prepare(
				"SELECT * FROM $table WHERE order_id = %d",
				$order_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.
		$result = array();
		if ( is_array( $db_results ) ) {
			foreach ( $db_results as $object_values ) {
				$result[] = new self( $object_values );
			}
		}
		return $result;
	}

	/**
	 * Load not sent job Ids.
	 *
	 * @return array
	 */
	public static function load_not_sent_job_ids() {
		global $wpdb;
		$time = new DateTime();
		$time->sub( new DateInterval( 'PT10M' ) );
		$table = $wpdb->prefix . self::get_table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Values are escaped in $wpdb->prepare.
		$db_results = $wpdb->get_results( //phpcs:ignore
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
