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
 * Class WC_VRPayment_Autoloader.
 * This is the autoloader for VRPayment classes.
 *
 * @class WC_VRPayment_Autoloader
 */
class WC_VRPayment_Autoloader {

	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor.
	 */
	public function __construct() {
		spl_autoload_register(
			array(
				$this,
				'autoload',
			)
		);
		$this->include_path = WC_VRPAYMENT_ABSPATH . 'includes/';
	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param  string $class_file class.
	 * @return string
	 */
	private function get_file_name_from_class( $class_file ) {
		$class = preg_replace( '/(?<!^)[A-Z]/', '-$0', $class_file );
		return 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
	}

	/**
	 * Include a class file.
	 *
	 * @param  string $path path.
	 * @return bool successful or not
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once $path;
			return true;
		}
		return false;
	}

	/**
	 * Auto-load WC VRPayment classes on demand to reduce memory consumption.
	 *
	 * @param string $class_file class.
	 */
	public function autoload( $class_file ) {
		$class = strtolower( $class_file );

		if ( 0 !== strpos( $class, 'wc_vrpayment' ) ) {
			return;
		}

		$file = $this->get_file_name_from_class( $class );
		$path = '';

		if ( strpos( $class, 'wc_vrpayment_service' ) === 0 ) {
			$path = $this->include_path . 'service/';
		} elseif ( strpos( $class, 'wc_vrpayment_entity' ) === 0 ) {
			$path = $this->include_path . 'entity/';
		} elseif ( strpos( $class, 'wc_vrpayment_provider' ) === 0 ) {
			$path = $this->include_path . 'provider/';
		} elseif ( strpos( $class, 'wc_vrpayment_webhook' ) === 0 ) {
			if ( strpos( $class, 'strategy' ) !== false ) {
				$path = $this->include_path . 'webhook/strategies/';
			} else {
				$path = $this->include_path . 'webhook/';
			}
		} elseif ( strpos( $class, 'wc_vrpayment_exception' ) === 0 ) {
			$path = $this->include_path . 'exception/';
		} elseif ( strpos( $class, 'wc_vrpayment_admin' ) === 0 ) {
			$path = $this->include_path . 'admin/';
		}

		if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

new WC_VRPayment_Autoloader();
