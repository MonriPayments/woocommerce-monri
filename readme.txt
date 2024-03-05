=== Monri Payments Gateway for WooCommerce ===
Contributors: monripayments
Tags: monri, credit card, payment, woocommerce
Requires at least: 5.3
Tested up to: 6.4.2
Requires PHP: 7.3
Stable tag: 3.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Accept payments using Monri WebPay and WSPay.

== Description ==

Monri's online payments enable you to quickly and easily charge debit and credit cards at all online sales points with
maximum security.

== Installation ==

You will first need to register with Monri in order to use this plugin on your site. Additional fees apply. Please
complete the [inquiry form](https://monri.com/contact/), and we will contact you regarding setup and any information
you will need.

If you used older Monri plugin, it is best to remove it first before using this new version. Old settings will be
migrated but make sure to recheck them and test new integration.

== Documentation ==

You can find additional information regarding Monri payments on WooCommerce at
[Monri's official documentation](https://ipg.monri.com/en/documentation/ecomm-plugins-woocommerce)

== Screenshots ==

1. Payment on checkout using Monri WebPay Form
2. Payment on checkout using saved WSPay tokens
3. Payment on checkout using Monri Components with additional installments fee
4. Admin settings showing different options for configuring the Monri payment module

== Development ==

You can find more details about the development of this plugin here:
https://github.com/MonriPayments/woocommerce-monri

== Changelog ==

= 3.0.0 - 2024-02-29 =
* WooCommerce blocks support (new checkout support)
* high-performance order storage (HPOS) support
* automatic success/cancel/callback url handling, no need to set on Monri side anymore
* major code cleanup and refactoring to follow WordPress/WooCommerce standards with multiple bugfixes
* translation improvements
* tested on latest PHP versions
* settings migration from old plugin
* WordPress Plugins release
