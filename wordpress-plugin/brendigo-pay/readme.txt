=== Brendigo Pay ===
Contributors: brendigo
Tags: payments, woocommerce, payment links, qr code, checkout
Requires at least: 6.4
Requires PHP: 8.1
Stable tag: 0.8.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Brendigo Pay WordPress/WooCommerce connector for paid Start (1 site), Business (5 sites) and Pro (25 sites) subscriptions. SANDBOX only; no real money.

== Description ==
Connect your WordPress site to the separate Brendigo Pay JSON-based development platform.
* Standalone WordPress: [brendigo_pay id="24_CHARACTER_ID"] or four Gutenberg blocks (button, link, QR, payment card).
* WooCommerce: selectable Stripe TEST / PayPal Sandbox / Revolut Sandbox checkout.
* WooCommerce Classic and Checkout Blocks bridge, HPOS CRUD compatibility declaration.
* Merchant-scoped API token and server-to-server payment verification.

IMPORTANT: This is NOT a production-ready payment gateway. Real sandbox integrations and WooCommerce versions must still be tested. Never use live payment credentials.

== Installation ==
1. Upload the plugin ZIP in WordPress > Plugins > Add New > Upload Plugin.
2. On the platform, register for Start / Business / Pro and wait for operator-confirmed payment.
3. Go to Web-stranice in the merchant panel and add this WordPress HTTPS domain. Copy the one-time license key.
4. Under Brendigo Pay settings, enter the platform HTTPS base URL and save.
5. Enter the one-time license key in "Aktivacija licencije" and activate. A site-scoped test API token is exchanged and stored encrypted in WordPress options.
6. Run Test connection.
7. Add the Gutenberg block or enable the WooCommerce sandbox gateway.
8. Configure sandbox payment provider credentials on the Brendigo Pay platform.
9. Manage license count, rotation and revocation under Web-stranice in your merchant account.

== Security ==
Test API token is encrypted with WordPress salt-derived AES-256-GCM, or use BRENDIGO_PAY_API_KEY in wp-config.php.
The platform uses test/sandbox hosted checkout and verifies provider payment status before Woo order completion.

== Changelog ==
= 0.8.5 =
* Encrypted domain-scoped license heartbeat via POST /api/v1/sites/sync.
* Manual sync and hourly WP-Cron retry; WordPress admin shows plan, expiry, domain quota and last sync.
* WooCommerce Classic/Blocks fail closed if the last valid license check is stale or rejected.
* Platform admin suspension immediately disables API access; installation versions/last seen visible in admin panel.
* Production payments still unavailable; sandbox-only release.

= 0.8.5 =
* Three paid tiers only: Start 1 WordPress website, Business 5 websites, Pro 25 websites. No free package.
* Site-origin-bound license activation and platform-generated site-specific sandbox API tokens.
* Site registration and license management in the merchant panel; restricted operator-only paid entitlement assignment.
* WordPress settings gain license activation UI; responsive administration improvements.
* Website limits enforced server-side with a locked JSON store; API tokens denied after plan expiry.
* No live payments or automated subscription billing; plans require independent verification and activation by operator.

= 0.8.5 =
* HOTFIX: Fixed fatal error when WooCommerce Checkout Blocks loads the payment integration.
* Reuse the inherited WooCommerce protected settings property instead of redeclaring a private typed property.
* Gracefully handle unexpected shortcode attributes and invalid WooCommerce settings payloads.
* Added PHP runtime compatibility tests that instantiate the Blocks payment integration and exercise plugin hooks.
* Sandbox only; no live payments or certified 1:1 visual parity.

= 0.8.0 =
* Refined block editor, payment button icons, QR captions and scoped responsive CSS.
* Hardened platform authentication rate limits, API response bounds and corrected stale UI/dead code.
* Structural security/dead-code audit with documented remaining risks; not a production certification.
* SANDBOX-only. Live payment methods and full WordPress/WooCommerce pixel parity remain unverified.

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
