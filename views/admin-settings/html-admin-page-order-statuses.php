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

<h2 class="vrpayment-order-statuses-heading wc-shipping-zones-heading">
	<span><?php esc_html_e( 'Order statuses', 'woo-vrpayment' ); ?></span>
</h2>

<table class="vrpayment-order-statuses wc-shipping-classes widefat">
	<thead>
		<tr>
			<?php foreach ( $order_statuses_columns as $status_key => $heading ) : ?>
				<th class="<?php echo esc_attr( $status_key ); ?>"><?php echo esc_html( $heading ); ?></th>
			<?php endforeach; ?>
			<th />
		</tr>
	</thead>
	<tbody class="vrpayment-order-statuses-rows wc-shipping-class-rows wc-shipping-tables-tbody"></tbody>
</table>

<script type="text/html" id="tmpl-vrpayment-order-statuses-row-blank">
	<tr>
		<td class="vrpayment-order-statuses-blank-state wc-shipping-classes-blank-state" colspan="<?php echo absint( count( $order_statuses_columns ) + 1 ); ?>"><p><?php esc_html_e( 'No custom status have been created.', 'woo-vrpayment' ); ?></p></td>
	</tr>
</script>

<!-- 1. Placeholder becomes the "label" in view class div -->
<!-- 1. Add labelFor or some kind of attribute for semantic HTML-->

<script type="text/html" id="tmpl-vrpayment-order-statuses-configure">
<div class="vrpayment-order-statuses-modal wc-backbone-modal wc-shipping-class-modal">
		<div class="wc-backbone-modal-content" data-id="{{ data.key }}">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php esc_html_e( 'Add custom status', 'woo-vrpayment' ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce' ); ?></span>
					</button>
				</header>
				<article>
				<form action="" method="post">
                    <input type="hidden" name="key" value="{{{ data.key }}}" />
					<?php
					foreach ( $order_statuses_columns as $status_key => $heading ) {
						echo '<div class="vrpayment-order-statuses-modal-input wc-shipping-class-modal-input ' . esc_attr( $status_key ) . '">';
						switch ( $status_key ) {
							case 'vrpayment-order-status-key':
								?>
								<div class="view">
									<?php echo esc_html( $heading ); ?> *
								</div>
								<div class="edit">
									<label style="display: flex; align-items: center;">
										<span style="background-color: #f1f1f1; padding: 12px; border: 1px solid #8c8f94; border-radius: 4px 0 0 4px; border-right: 0;">
											<?php echo esc_html( 'wc-' ); ?>
										</span>
										<input type="text" name="key" data-attribute="key" value="{{ data.key }}"
											style="flex: 1; padding: 12px; border-radius: 0 4px 4px 0;" 
											placeholder="<?php esc_attr_e( 'e.g. awaiting', 'woo-vrpayment' ); ?>" />
									</label>
                                    <small id="charCount" style="color: gray;"></small>
								</div>
								<div class="vrpayment-order-statuses-modal-help-text wc-shipping-class-modal-help-text"><?php esc_html_e( 'Give your custom status a name for easy identification', 'woo-vrpayment' ); ?></div>
								<?php
								break;
						}
						echo '</div>';
					}
					?>
				</form>
				</article>
				<footer>
					<div class="inner">
						<button id="btn-ok" disabled class="button button-primary button-large disabled">
							<div class="wc-backbone-modal-action-{{ data.action === 'create' ? 'active' : 'inactive' }}"><?php esc_html_e( 'Create', 'woocommerce' ); ?></div>
							<div class="wc-backbone-modal-action-{{ data.action === 'edit' ? 'active' : 'inactive' }}"><?php esc_html_e( 'Save', 'woocommerce' ); ?></div>
						</button>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>

<script type="text/html" id="tmpl-vrpayment-order-statuses-row">
	<tr data-id="{{ data.key }}">
		<?php
		foreach ( $order_statuses_columns as $status_key => $heading ) {
			echo '<td class="' . esc_attr( $status_key ) . '">';
			switch ( $status_key ) {
				case 'vrpayment-order-status-key':
					?>
					<div class="view">{{ data.key }}</div>
					<?php
					break;
				case 'vrpayment-order-status-label':
					?>
					<div class="view">{{ data.label }}</div>
					<?php
					break;
				case 'vrpayment-order-status-type':
					?>
					<div class="view">{{ data.type }}</div>
					<?php
					break;
			}
			echo '</td>';
		}
		?>
		<td class="vrpayment-order-statuses-actions wc-shipping-zone-actions">
			<div class="actions-container" data-type="{{ data.type }}">
				<a href="#" class="vrpayment-order-status-delete wc-shipping-class-delete wc-shipping-zone-actions"><?php esc_html_e( 'Delete', 'woocommerce' ); ?></a>
			</div>
		</td>
	</tr>
</script>

