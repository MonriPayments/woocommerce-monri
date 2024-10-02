=== Monri Payments Gateway for WooCommerce ===
Contributors: monripayments
Tags: monri, credit card, payment, woocommerce
Requires at least: 5.3
Tested up to: 6.6
Requires PHP: 7.3
Stable tag: 3.2.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Accept payments using Monri WebPay and WSPay.

== Description ==

Monri's online payments enable you to quickly and easily charge debit and credit cards at all online sales points with maximum security.

== Installation ==

You will first need to register with Monri in order to use this plugin on your site. Additional fees apply.
Please complete the [inquiry form](https://monri.com/contact/), and we will contact you regarding setup and any information you will need.

If you used older Monri plugin, it is best to remove it first before using this new version.
Old settings will be migrated but make sure to recheck them and test new integration.

== Documentation ==

You can find additional information regarding Monri payments on WooCommerce at
[Monri's official documentation](https://ipg.monri.com/en/documentation/ecomm-plugins-woocommerce)

You can find additional information regarding Privacy policy of Monri payments on WooCommerce at
[Monri's privacy policy page](https://ipg.monri.com/en/privacy-policy).

== Screenshots ==

1. Payment on checkout using Monri WebPay Form
2. Payment on checkout using saved WSPay tokens
3. Payment on checkout using Monri Components with additional installments fee
4. Admin settings showing different options for configuring the Monri payment module

== Development ==

You can find more details about the development of this plugin at:
https://github.com/MonriPayments/woocommerce-monri

== Changelog ==

= 3.2.2 - 2024-09-02 =
* TOC validation improvements in the old checkout
* Namespace error bugfix in the new checkout
* API logging improved
* Tested with the latest WooCommerce and WordPress

= 3.2.1 - 2024-08-27 =
* Validation improvements in old WooCommerce checkout
* Increased the number of maximum installments to 36

= 3.2.0 - 2024-07-26 =
* Refund, capture, void from administration, APIs implemented for all 3 payment methods
* Components avoid new initialization when possible
* Multiple small code improvements
* Tested with latest WooCommerce and WordPress

= 3.1.1 - 2024-05-06 =
* Small code changes required by WP Plugins, no functionality changes

= 3.1.0 - 2024-04-19 =
* New components implementation - customer never leaves checkout, 3D secure check is done in lightbox
* Webpay implementation sends installments data to redirect form - number of installments is preselected
* Preventing direct access to files
* Improvements in data validation, reducing input which is being processed - better plugin performance
* Code refactor and cleanup, adding response code to failed order note
* Adding transaction ID to orders created with components implementation - easier to find order in Monri administration
* Translation improvements

= 3.0.2 - 2024-04-09 =
* Show transaction info on Thank You page, required by some banks. (currently WsPay only)
* Callback resolves order id correctly in test mode
* Correct file extension for Bosnian translations

= 3.0.1 - 2024-03-29 =
* Improvements in callback validation - adding response code check together with status check

= 3.0.0 - 2024-02-29 =
* WooCommerce blocks support (new checkout support)
* high-performance order storage (HPOS) support
* automatic success/cancel/callback url handling, no need to set on Monri side anymore
* major code cleanup and refactoring to follow WordPress/WooCommerce standards with multiple bugfixes
* translation improvements
* tested on latest PHP versions
* settings migration from old plugin
* WordPress Plugins release
