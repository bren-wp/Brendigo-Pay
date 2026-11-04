=== Brendigo Pay ===
Contributors: brendigo
Tags: payment links, woocommerce, qr codes, checkout, integrations
Requires at least: 6.4
Requires PHP: 8.1
Stable tag: 0.8.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Brendigo Pay for WordPress and WooCommerce — payment links, QR codes, and merchant-scoped licensing. Sandbox/test edition; real payments unavailable.

== Description ==

Brendigo Pay integrates your WordPress website with your own merchant account on the Brendigo Pay platform.

* WordPress: Gutenberg payment card, button, link and QR blocks, plus a shortcode.
* WooCommerce: Stripe TEST, PayPal Sandbox and Revolut Sandbox choices for Classic and Blocks checkout.
* Merchant account: manage your own links, connected domains and subscription plan.
* License control: each HTTPS website requires an authorized license.
* Synchronized status: site connection, plan validity and last successful sync.
* Responsive admin interface with direct links to product guides and troubleshooting.

No free plan: Start covers 1 website, Business 5 and Pro 25. Registration does not activate billing or payment processing; the operator verifies paid-plan entitlement.

The plugin is intended for testing and integration development. It does not charge live cards. Never enter production processor credentials.

== Installation ==

1. Upload the Brendigo Pay ZIP through Plugins > Add New > Upload Plugin, then Activate.
2. Register an account with the Brendigo Pay platform and select Start, Business or Pro.
3. After the operator activates your paid plan, open Web-stranice and register the HTTPS address of this WordPress website.
4. Copy its license key and open Brendigo Pay > Postavke in WordPress.
5. Set your platform HTTPS address, save, enter your license and select Activate.
6. Click Sinkroniziraj sada and confirm that the domain, plan and connection status match in WordPress and the merchant panel.
7. Configure your own test payment provider account in the merchant web panel.
8. Add a Gutenberg payment block or enable Brendigo Pay Sandbox in WooCommerce > Settings > Payments.

See the full guides at https://brendigopay.com/docs/wordpress and https://brendigopay.com/docs/woocommerce.

== Frequently Asked Questions ==

= Can I log in with the same account on the website and Android? =
Yes. Both clients use the same merchant login, but your WordPress installation has its own separately activated license.

= Why is the payment option not visible in checkout? =
Verify that your paid plan and site license are active, the website was synchronized recently, and a supported sandbox provider is configured.

= Does this plugin receive credit card details? =
No. Payment card entry takes place on the provider's hosted payment page.

= Does Brendigo Pay itself provide bank transfer? =
WooCommerce has a separate built-in Direct Bank Transfer (BACS) method. Bank transfers must be verified against your bank account before fulfilling an order.

= Is this ready to accept real payments? =
No. The current version is a sandbox preview. Live provider and regulatory verification are not complete.

== Changelog ==

= 0.8.6 =
* Linked user-friendly WordPress setup guides, WooCommerce guide and troubleshooting directly from plugin settings.
* Clarified supported test provider flows versus offline bank transfer and planned domestic providers.
* Improved synchronized product documentation and responsive admin help navigation.
* SANDBOX release; no live payments.

= 0.8.5 =
* Domain-scoped license status, manual synchronization and hourly retry.
* WooCommerce Classic/Blocks disable an unverified or stale license.
* Shared Android/web merchant accounts and integrated dashboard status.
* Security hardening and critical WooCommerce Blocks inheritance fix maintained.

= 0.8.0 =
* Refined Gutenberg blocks, QR captions, payment buttons and responsive checkout controls.
* Hardened authentication, provider status validation, and offline test coverage.

== External Services ==

The plugin communicates over HTTPS with the merchant's configured Brendigo Pay platform. That platform connects to the merchant's configured Stripe, PayPal, or Revolut sandbox accounts. No payment card numbers are transferred into the plugin or Brendigo Pay platform.

Product documentation and privacy information: https://brendigopay.com/docs and https://brendigopay.com/privacy.
