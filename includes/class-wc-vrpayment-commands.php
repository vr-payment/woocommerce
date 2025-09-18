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

if ( class_exists( 'WP_CLI' ) && ! class_exists( 'WC_VRPayment_Commands' ) ) {

    /**
     * Class WC_VRPayment_Commands.
     * This class contains custom commands for VR Payment.
     *
     * @class WC_VRPayment_Commands
     */
    class WC_VRPayment_Commands {

        /**
         * Register commands.
         */
        public static function init() {
            WP_CLI::add_command(
                'vrpayment webhooks install',
                array(
                    __CLASS__,
                    'webhooks_install'
                )
            );
            WP_CLI::add_command(
                'vrpayment payment-methods sync',
                array(
                    __CLASS__,
                    'payment_methods_sync'
                )
            );
        }

        /**
         * Create webhook URL and webhook listeners in the portal for VR Payment.
         *
         * ## EXAMPLE
         *
         *     $ wp vrpayment webhooks install
         *
         * @param array $args WP-CLI positional arguments.
         * @param array $assoc_args WP-CLI associative arguments.
         */
        public static function webhooks_install( $args, $assoc_args ) {
            try {
                WC_VRPayment_Helper::instance()->reset_api_client();
                WC_VRPayment_Service_Webhook::instance()->install();
                WP_CLI::success( "Webhooks installed." );
            } catch ( \Exception $e ) {
                WooCommerce_VRPayment::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
                WP_CLI::error( "Failed to install webhooks: " . $e->getMessage() );
            }
        }

        /**
         * Synchronizes payment methods in the VR Payment from the portal.
         *
         * ## EXAMPLE
         *
         *     $ wp vrpayment payment-methods sync
         *
         * @param array $args WP-CLI positional arguments.
         * @param array $assoc_args WP-CLI associative arguments.
         */
        public static function payment_methods_sync( $args, $assoc_args ) {
            try {
                WC_VRPayment_Helper::instance()->reset_api_client();
                WC_VRPayment_Service_Method_Configuration::instance()->synchronize();
                WC_VRPayment_Helper::instance()->delete_provider_transients();
                WP_CLI::success( "Payment methods synchronized." );
            } catch ( \Exception $e ) {
                WooCommerce_VRPayment::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
                WP_CLI::error( "Failed to synchronize payment methods: " . $e->getMessage() );
            }
        }
    }
}

WC_VRPayment_Commands::init();