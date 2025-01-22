=== VRPay ===
Contributors: VR Payment GmbH
Tags: woocommerce VRPay, woocommerce, VRPay, payment, e-commerce, webshop, psp, invoice, packing slips, pdf, customer invoice, processing
Requires at least: 4.7
Tested up to: 6.7
Stable tag: 3.3.3
License: Apache-2.0
License URI: http://www.apache.org/licenses/LICENSE-2.0

Accept payments in WooCommerce with VRPay.

== Description ==

Website: [https://www.vr-payment.de/](https://www.vr-payment.de/)

The plugin offers an easy and convenient way to accept credit cards and all
other payment methods listed below fast and securely. The payment forms will be fully integrated in your checkout
and for credit cards there is no redirection to a payment page needed anymore. The pages are by default mobile optimized but
the look and feel can be changed according the merchants needs.

This plugin will add support for all VRPay payments methods and connect the VRPay servers to your WooCommerce webshop.
To use this extension, a VRPay account is required. Sign up on [VRPay](https://gateway.vr-payment.de/user/login).

== Documentation ==

Additional documentation for this plugin is available [here](https://gateway.vr-payment.de/doc/woocommerce/3.3.3/docs/en/documentation.html).

== Support ==

Support queries can be issued on the [VRPay support site](https://www.vr-payment.de/hotline).

== Privacy Policy ==

Enquiries about our privacy policy can be made on the [VRPay privacy policies site](https://en.vrpayment.com/legal/privacy-policy).

== Terms of use ==

Enquiries about our terms of use can be made on the [VRPay terms of use site](https://en.vrpayment.com/legal/agb).

== Installation ==

= Minimum Requirements =

* PHP version 5.6 or greater
* WordPress 4.7 up to 6.6
* WooCommerce 3.0.0 up to 8.9.1

= Automatic installation =

1. Install the plugin via Plugins -> New plugin. Search for 'VRPay'.
2. Activate the 'VRPay' plugin through the 'Plugins' menu in WordPress
3. Set your VRPay credentials at WooCommerce -> Settings -> VRPay (or use the *Settings* link in the Plugins overview)
4. You're done, the active payment methods should be visible in the checkout of your webshop.

= Manual installation =

1. Unpack the downloaded package.
2. Upload the directory to the `/wp-content/plugins/` directory
3. Activate the 'VRPay' plugin through the 'Plugins' menu in WordPress
4. Set your credentials at WooCommerce -> Settings -> VRPay (or use the *Settings* link in the Plugins overview)
5. You're done, the active payment methods should be visible in the checkout of your webshop.


== Changelog ==


= 3.3.3 - Jan 15th 2025 =
- [Improvement] Improve payment method loading speed at checkout
- [Bugfix] Fix for missing transaction box in order
- [Bugfix]  Version bump for missing files in previous release
- [Tested Against] PHP 8.2
- [Tested Against] Wordpress 6.7
- [Tested Against] Woocommerce 9.4.2
- [Tested Against] PHP SDK 4.6.0
