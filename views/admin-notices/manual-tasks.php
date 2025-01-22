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
	<?php
	if ( 1 === $number_of_manual_tasks ) {
		esc_html_e( 'There is a manual task that needs your attention.', 'woo-vrpayment' );
	} else {
		/* translators: %s are replaced with int */
		echo esc_html( sprintf( _n( 'There is %s manual task that needs your attention.', 'There are %s manual tasks that need your attention', $number_of_manual_tasks, 'woo-vrpayment' ), $number_of_manual_tasks ) );
	}
	?>
		</p>
	<p>
		<a href="<?php echo esc_url( $manual_taks_url ); ?>" target="_blank"><?php esc_html_e( 'View', 'woo-vrpayment' ); ?></a>
	</p>
</div>
