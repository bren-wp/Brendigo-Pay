=== Brendigo Pay ===
Contributors: brendigo
Tags: payments, woocommerce, payment links, qr code, checkout
Requires at least: 6.4
Requires PHP: 8.1
Stable tag: 0.7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sandbox-only Brendigo Pay connector for WordPress and WooCommerce. Does NOT support real money.

== Description ==
Connect your WordPress site to the separate Brendigo Pay JSON-based development platform.
* Standalone WordPress: [brendigo_pay id="24_CHARACTER_ID"] or four Gutenberg blocks (button, link, QR, payment card).
* WooCommerce: selectable Stripe TEST / PayPal Sandbox / Revolut Sandbox checkout.
* WooCommerce Classic and Checkout Blocks bridge, HPOS CRUD compatibility declaration.
* Merchant-scoped API token and server-to-server payment verification.

IMPORTANT: This is NOT a production-ready payment gateway. Real sandbox integrations and WooCommerce versions must still be tested. Never use live payment credentials.

== Installation ==
1. Upload the plugin ZIP in WordPress > Plugins > Add New > Upload Plugin.
2. Under Brendigo Pay settings, enter platform HTTPS base URL and test merchant API key.
3. Run Test connection.
4. Add the Gutenberg payment button or enable the Brendigo Pay gateway in WooCommerce Payments.
5. Configure sandbox credentials on the separate Brendigo Pay platform.

== Security ==
Test API token is encrypted with WordPress salt-derived AES-256-GCM, or use BRENDIGO_PAY_API_KEY in wp-config.php.
The platform uses test/sandbox hosted checkout and verifies provider payment status before Woo order completion.

== Changelog ==
= 0.7.2 =
* Refined Gutenberg service card, provider selection and admin dimensions against user-supplied references.
* Added labeled provider monograms in classic and Blocks checkout, with improved keyboard focus and mobile behavior.
* SANDBOX only; visual parity and live checkout are not certified.

= 0.7.1 =
* Refined WordPress admin layout and focus styles, hash-linked accessible navigation.

= 0.7.0 =
* Development repository migration; standardized Brendigo branding, responsive visual QA and CI lint/tests.
* Sandbox-only. No live payment functionality.

= 0.6.0 =
* Gutenberg payment-link picker populated via merchant-scoped Brendigo Pay API and authorized WordPress REST proxy.
* Improved WordPress admin management list with payment amount and copyable shortcode.
* Refined classic and Checkout Block sandbox payment provider cards and typography.
* No live payments; requires further WordPress.org plugin checks and full WooCommerce E2E validation.

= 0.5.0 =
* Official Brendigo Pay site, refreshed WordPress branding and UI components.
* Responsive design polishing and dedicated icon/favicons.
* Remains sandbox-only: no live transactions and no raw card storage.

= 0.4.0 =
* Brendigo visual redesign for WordPress settings, Gutenberg and WooCommerce classic/Blocks provider selectors.
* QR payment card/block and authenticated provider-side link title/amount retrieval.
* No live payment support; visual parity not certified.

= 0.3.0 =
* WooCommerce classic and Blocks sandbox payment provider selection.
* Allowed redirect hosts for Stripe, PayPal and Revolut; server-side provider status checks.
* Tested offline with expanded mocks. Real WooCommerce integration test still pending.

= 0.2.0 =
* Platform connection test, audit, HPOS declaration, defensive order processing.

= 0.1.0 =
* Initial shortcode, Gutenberg block and Stripe test gateway.

== External Services ==
This plugin communicates with the separately hosted Brendigo Pay platform that you configure in settings. It sends a merchant-scoped API token and payment status requests over HTTPS. The platform then contacts Stripe, PayPal or Revolut sandbox APIs as configured. No live card numbers are sent to the Brendigo Pay platform. See https://brendigopay.com for product documentation (website availability is independent of the plugin).
