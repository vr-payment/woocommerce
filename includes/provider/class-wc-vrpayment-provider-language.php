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
 * Provider of language information from the gateway.
 */
class WC_VRPayment_Provider_Language extends WC_VRPayment_Provider_Abstract {

	/**
	 * Construct.
	 */
	protected function __construct() {
		parent::__construct( 'wc_vrpayment_languages' );
	}

	/**
	 * Returns the language by the given code.
	 *
	 * @param string $code code.
	 * @return \VRPayment\Sdk\Model\RestLanguage
	 */
	public function find( $code ) { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::find( $code );
	}

	/**
	 * Returns the primary language in the given group.
	 *
	 * @param string $code code.
	 * @return \VRPayment\Sdk\Model\RestLanguage
	 */
	public function find_primary( $code ) {
		$code = substr( $code, 0, 2 );
		foreach ( $this->get_all() as $language ) {
			if ( $language->getIso2Code() == $code && $language->getPrimaryOfGroup() ) {
				return $language;
			}
		}

		return false;
	}

	/**
	 * Find by iso code.
	 *
	 * @param mixed $iso iso.
	 * @return false|\VRPayment\Sdk\Model\RestLanguage
	 */
	public function find_by_iso_code( $iso ) {
		foreach ( $this->get_all() as $language ) {
			if ( $language->getIso2Code() == $iso || $language->getIso3Code() == $iso ) {
				return $language;
			}
		}
		return false;
	}

	/**
	 * Returns a list of language.
	 *
	 * @return \VRPayment\Sdk\Model\RestLanguage[]
	 */
	public function get_all() { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get_all();
	}

	/**
	 * Fetch data.
	 *
	 * @return array|\VRPayment\Sdk\Model\RestLanguage[]
	 * @throws \VRPayment\Sdk\ApiException ApiException.
	 * @throws \VRPayment\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \VRPayment\Sdk\VersioningException VersioningException.
	 */
	protected function fetch_data() {
		$language_service = new \VRPayment\Sdk\Service\LanguageService( WC_VRPayment_Helper::instance()->get_api_client() );
		return $language_service->all();
	}

	/**
	 * Get id.
	 *
	 * @param mixed $entry entry.
	 * @return string
	 */
	protected function get_id( $entry ) {
		/* @var \VRPayment\Sdk\Model\RestLanguage $entry */ //phpcs:ignore
		return $entry->getIetfCode();
	}
}
