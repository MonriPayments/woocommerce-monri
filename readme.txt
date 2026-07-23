=== Monri Payments ===
Contributors: monripayments
Tags: monri, woocommerce, payment gateway, credit card payments, online payments, croatia, wspay, keks pay, apple pay, google pay, installments
Requires at least: 5.3
Tested up to: 7.0
Requires PHP: 7.3
Stable tag: 3.8.5
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Description: Monri Payments Gateway for WooCommerce

Accept credit card payments, WSPay, Keks Pay, Apple Pay and Google Pay in WooCommerce with Monri Payments.

== Description ==

Monri's online payments enable you to quickly and easily charge debit and credit cards at all online sales points with maximum security.

Monri is a trusted payment gateway used by merchants across Croatia, Bosnia and Herzegovina (BiH), and the European Union. The plugin supports multiple payment experiences including Monri WebPay, WSPay, card tokenization, installments, and modern checkout integrations.

Supported payment methods and features include:

* Credit and debit card payments
* Monri WebPay Redirect
* Monri WebPay Components
* Monri WebPay Lightbox
* WSPay integration
* Saved cards and tokenization
* Keks Pay
* Apple Pay
* Google Pay
* Installment payments
* WooCommerce Blocks support
* HPOS support
* Refunds, captures and voids from WooCommerce administration

Customers can complete payments securely while merchants benefit from advanced fraud protection, 3-D Secure authentication, and a payment platform designed for WooCommerce.

== Installation ==

You will first need to register with Monri to use this plugin on your site. Additional fees apply.
Please complete the [inquiry form](https://monri.com/contact/), and we will reach out to you regarding setup and any information you will need.

If you used the older Monri plugin, it is best to remove it first before using this new version.
Old settings will be migrated but make sure to recheck them and test new integration.

== Documentation ==

You can find additional information regarding Monri payments on WooCommerce at
[Monri's official documentation](https://ipg.monri.com/en/documentation/ecomm-plugins-woocommerce)

You can find additional information regarding Privacy policy of Monri payments on WooCommerce at
[Monri's privacy policy page](https://ipg.monri.com/en/privacy-policy).

== FAQ ==

= What is Monri Payments? =

Monri Payments is an online payment platform that enables merchants to accept credit card and alternative payment methods through WooCommerce.

= Is Monri a Croatian payment gateway? =

Yes. Monri is widely used by merchants in Croatia and throughout the region, while also supporting international and EU online payments.

= Does this plugin support WSPay? =

Yes. The plugin includes support for WSPay payment processing and WSPay tokenization features where available.

= Does WooCommerce support Keks Pay? =

Yes. Recent versions of the plugin include support for Keks Pay through Monri Payments.

= Does the plugin support Apple Pay and Google Pay? =

Yes. Apple Pay and Google Pay are available through Monri Payments where enabled for your merchant account.

= Can customers save their cards for future purchases? =

Yes. The plugin supports card tokenization and saved payment methods for eligible payment flows.

= Does the plugin support installment payments? =

Yes. Merchants can offer installment payments when supported by their acquiring bank and Monri configuration.

= Does the plugin support WooCommerce Blocks and HPOS? =

Yes. The plugin supports WooCommerce Blocks (Checkout Block) and High-Performance Order Storage (HPOS).

= How do I start using Monri Payments? =

You must first open a merchant account with Monri. Complete the inquiry form on Monri's website and the Monri team will guide you through the setup process.

== Screenshots ==

1. Payment on checkout using Monri WebPay Form
2. Payment on checkout using saved WSPay tokens
3. Payment on checkout using Monri Components with additional installments fee
4. Admin settings showing different options for configuring the Monri payment module

== Development ==

You can find more details about the development of this plugin at:
https://github.com/MonriPayments/woocommerce-monri

== Changelog ==

= 3.8.4 - 2026-7-03 =
* Fixed coding standards according to stricter new wp.org rules.

= 3.8.3 - 2026-6-26 =
* Improved payment processing on custom thank you page

= 3.8.2 - 2026-3-5 =
* Added new 3DS parameters to Monri WebPay Redirect and Monri WebPay Components

= 3.8.1 - 2025-11-3 =
* Added extra logs for sync order status on additional payment methods

= 3.8.0 - 2025-11-3 =
* Added new Monri PayCek payment method on old and new checkout
* Added alpha version of Monri Google Pay payment method on old and new checkout
* Added alpha version of Monri Apple Pay payment method on old and new checkout
* Added alpha version of Monri Keks Pay payment method on old and new checkout
* Temporarily disabled settings for new payment methods
* Added logic for refunds for new payment methods which support it
* Removed supported payment methods from Monri WebPay. New methods must be agreed with Monri support

= 3.7.1 - 2025-07-10 =
* Updated NPM dependencies

= 3.7.0 - 2025-07-8 =
* Added Monri tokenization for Monri WebPay Components

= 3.6.2 - 2025-06-24 =
* Added masked credit card number to transaction info
* Translation updates

= 3.6.1 - 2025-06-2 =
* Added customizable number of maximum installments
* Updated NPM dependencies

= 3.6.0 - 2025-05-26 =
* Added Monri tokenization for Monri WebPay Redirect
* Added Monri tokenization for Monri WebPay Lightbox
* Fixed issue where Monri WebPay Lightbox would not work properly on old checkout if installments were disabled
* Fixed issue in admin settings where additional payment methods would show for Monri WebPay Components

= 3.5.0 - 2025-03-19 =
* Monri Lightbox

= 3.4.1 - 2025-01-24 =
* Added additional payment methods for Monri Webpay
* Added callback url for Monri WSPay in admin settings

= 3.4.0 - 2025-01-14 =
* Added Monri WSPay iFrame

= 3.3.0 - 2025-01-08 =
* Added callback for Monri WSPay
* Improved error messages for admin

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
