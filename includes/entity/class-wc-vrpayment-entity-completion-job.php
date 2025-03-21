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
 * Class WC_VRPayment_Entity_Completion_Job.
 * This entity holds data about a transaction on the gateway.
 *
 * @class WC_VRPayment_Entity_Completion_Job
 * @method int get_id()
 * @method int get_completion_id()
 * @method void set_completion_id(int $id)
 * @method string get_state()
 * @method void set_state(string $state)
 * @method int get_space_id()
 * @method void set_space_id(int $id)
 * @method int get_transaction_id()
 * @method void set_transaction_id(int $id)
 * @method int get_order_id()
 * @method void set_order_id(int $id)
 * @method float get_amount()
 * @method void set_amount(float $amount)
 * @method object get_items()
 * @method void set_items(object $items)
 * @method boolean get_restock()
 * @method void set_restock(boolean $items)
 * @method void set_failure_reason(map[string,string] $reasons)
 */
class WC_VRPayment_Entity_Completion_Job extends WC_VRPayment_Entity_Abstract {
	const VRPAYMENT_STATE_CREATED = 'created';
	const VRPAYMENT_STATE_ITEMS_UPDATED = 'item';
	const VRPAYMENT_STATE_SENT = 'sent';
	const VRPAYMENT_STATE_DONE = 'done';

	/**
	 * Get field definitions.
	 *
	 * @return string
	 */
	protected static function get_field_definition() {
		return array(
			'completion_id' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_INTEGER,
			'state' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_STRING,
			'space_id' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_INTEGER,
			'transaction_id' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_INTEGER,
			'order_id' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_INTEGER,
			'amount' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_DECIMAL,
			'items' => WC_VRPayment_Entity_Resource_Type::VRPAYMENT_OBJECT,
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
		return 'vrpayment_completion_job';
	}

	/**
	 * Returns the translated failure reason.
	 *
	 * @param string $language language.
	 * @return string
	 */
	public function get_failure_reason( $language = null ) {
		$value = $this->get_value( 'failure_reason' );
		if ( empty( $value ) ) {
			return null;
		}

		return WC_VRPayment_Helper::instance()->translate( $value, $language );
	}

	/**
	 * Load by completion
	 *
	 * @param mixed $space_id space id.
	 * @param mixed $completion_id completion_id id.
	 */
	public static function load_by_completion( $space_id, $completion_id ) {
		global $wpdb;
		// phpcs:ignore
		$result = $wpdb->get_row(
			// phpcs:ignore
			$wpdb->prepare(
				// phpcs:ignore
				'SELECT * FROM ' . $wpdb->prefix . self::get_table_name() . ' WHERE space_id = %d AND completion_id = %d',
				$space_id,
				$completion_id
			),
			ARRAY_A
		);
		if ( null !== $result ) {
			return new self( $result );
		}
		return new self();
	}

	/**
	 * Count running completion for transaction.
	 *
	 * @param mixed $space_id space id.
	 * @param mixed $transaction_id transaction id.
	 */
	public static function count_running_completion_for_transaction( $space_id, $transaction_id ) {
		global $wpdb;
		// phpcs:ignore
		$query = $wpdb->prepare(
			// phpcs:ignore
			'SELECT COUNT(*) FROM ' . $wpdb->prefix . self::get_table_name() . ' WHERE space_id = %d AND transaction_id = %d AND state != %s',
			$space_id,
			$transaction_id,
			self::VRPAYMENT_STATE_DONE
		);
		// phpcs:ignore
		$result = $wpdb->get_var( $query );
		return $result;
	}

	/**
	 * Load running completion for transaction.
	 *
	 * @param mixed $space_id space id.
	 * @param mixed $transaction_id transaction id.
	 */
	public static function load_running_completion_for_transaction( $space_id, $transaction_id ) {
		global $wpdb;
		// phpcs:ignore
		$result = $wpdb->get_row(
			// phpcs:ignore
			$wpdb->prepare(
				// phpcs:ignore
				'SELECT * FROM ' . $wpdb->prefix . self::get_table_name() . ' WHERE space_id = %d AND transaction_id = %d AND state != %s',
				// phpcs:ignore
				$space_id,
				$transaction_id,
				self::VRPAYMENT_STATE_DONE
			),
			ARRAY_A
		);
		if ( null !== $result ) {
			return new self( $result );
		}
		return new self();
	}

	/**
	 * Load not sent job IDs.
	 */
	public static function load_not_sent_job_ids() {
		global $wpdb;
		// Returns empty array.

		$time = new DateTime();
		$time->sub( new DateInterval( 'PT10M' ) );
		$db_results = $wpdb->get_results( //phpcs:ignore
			$wpdb->prepare(
				// phpcs:ignore
				'SELECT id FROM ' . $wpdb->prefix . self::get_table_name() . ' WHERE (state = %s OR state = %s ) AND updated_at < %s',
				// phpcs:ignore
				self::VRPAYMENT_STATE_CREATED,
				// phpcs:ignore
				self::VRPAYMENT_STATE_ITEMS_UPDATED,
				// phpcs:ignore
				$time->format( 'Y-m-d H:i:s' )
			),
			ARRAY_A
		);
		$result = array();
		if ( is_array( $db_results ) ) {
			foreach ( $db_results as $object_values ) {
				$result[] = $object_values['id'];
			}
		}
		return $result;
	}
}
