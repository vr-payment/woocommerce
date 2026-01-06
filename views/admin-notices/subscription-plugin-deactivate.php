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

?>
<div class="error notice notice-error">
    <p>
        <?php esc_html_e( 'VR Payment Subscription plugin has been deactivated because subscriptions are now handled directly by the VR Payment plugin. You can safely remove the old plugin', 'woo-vrpayment' ); ?>
    </p>
</div>
