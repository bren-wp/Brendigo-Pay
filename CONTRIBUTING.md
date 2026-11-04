# Contributing to Brendigo Pay

Author and project branding: **Brendigo** · https://brendigo.com · https://brendigopay.com.

The source is a **SANDBOX prototype**. Do not submit live keys or test with real money. Changes to payment status, multi-merchant isolation, WordPress permissions or webhook signature validation require regression tests.

Run locally: `bash tests/run-all.sh`. For WordPress, test in a disposable local WordPress + WooCommerce installation with HPOS and Checkout Blocks enabled. Include before/after screenshots at matching viewports for UI changes. Do not claim pixel-perfect parity unless measured against the reference images.

For WordPress.org contributions, the registered WordPress.org handle must be the listed `Contributors: brendigo`. Plugin code remains GPL-2.0-or-later; preserve third-party copyright notices and licenses.
