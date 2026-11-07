#!/usr/bin/env python3
"""Brendigo Pay sandbox-only PSP safety checks; not a provider certification."""
from pathlib import Path

r = Path(__file__).resolve().parents[1]
providers = (r / 'platform/src/SandboxProviders.php').read_text()
stripe = (r / 'platform/src/StripeTest.php').read_text()
connect = (r / 'platform/src/StripeConnectSandbox.php').read_text()
ui = (r / 'platform/src/Ui.php').read_text()
wp = (r / 'wordpress-plugin/brendigo-pay/views/admin-settings.php').read_text()

assert 'Only Stripe test keys are supported' in stripe and "sk_test_" in stripe
assert "sk_test_" in connect
assert "Unapproved sandbox endpoint" in providers
assert "'api.sandbox.paypal.com'" in providers
assert "'sandbox-merchant.revolut.com'" in providers
assert "Revolut-Api-Version: 2026-08-17" in providers
assert "verifyRevolut(" in providers
assert "paypalVerifyWebhook(" in providers
assert "paypalPaid(" in providers
assert "revolutPaid(" in providers
assert "Produkcija (nije dostupna)" in wp
assert "Konfiguriran · Sandbox" in ui
assert "card_number" not in ui and "cvc" not in ui
# Never silently use live hosts through the sandbox-only provider transport.
assert "https://api.paypal.com/" not in providers
assert "https://merchant.revolut.com/api/" not in providers
print("PASS: sandbox PSP allowlist, test-only Stripe secrets and production mode disabled")
