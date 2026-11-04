<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Support.php';
require dirname(__DIR__) . '/src/SandboxProviders.php';
require dirname(__DIR__) . '/src/StripeConnectSandbox.php';

use BrendigoPay\SandboxProviders;
use BrendigoPay\StripeConnectSandbox;

function check_provider(bool $ok, string $label): void
{
    if (!$ok) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    echo 'PASS: ' . $label . "\n";
}

$payload = '{"event":"ORDER_COMPLETED","order_id":"188b7f0b-9671-4e77-99d2-a999cde34c9a"}';
$ms = (string) (time() * 1000);
$signature = 'v1=' . hash_hmac('sha256', 'v1.' . $ms . '.' . $payload, 'wsk_sandbox_secret');
check_provider(SandboxProviders::verifyRevolut($payload, $ms, $signature, 'wsk_sandbox_secret'), 'valid Revolut HMAC signature');
check_provider(!SandboxProviders::verifyRevolut($payload . ' ', $ms, $signature, 'wsk_sandbox_secret'), 'reject modified Revolut body');
check_provider(!SandboxProviders::verifyRevolut($payload, (string) ((time() - 900) * 1000), $signature, 'wsk_sandbox_secret'), 'reject stale Revolut timestamp');
check_provider(SandboxProviders::verifyRevolut($payload, $ms, 'v1=' . str_repeat('0', 64) . ',' . $signature, 'wsk_sandbox_secret'), 'Revolut signing secret rotation support');

$txn = ['id' => str_repeat('a', 32), 'provider_order_id' => 'ABC1234567', 'amount_minor' => 3290, 'currency' => 'EUR'];
$order = [
    'id' => 'ABC1234567', 'status' => 'COMPLETED',
    'purchase_units' => [[
        'reference_id' => str_repeat('a', 32),
        'payments' => ['captures' => [['status' => 'COMPLETED', 'amount' => ['value' => '32.90', 'currency_code' => 'EUR']]]],
    ]],
];
check_provider(SandboxProviders::paypalPaid($order, $txn), 'PayPal completed captured order matches reference and EUR amount');
$bad = $order;
$bad['purchase_units'][0]['payments']['captures'][0]['amount']['value'] = '33.90';
check_provider(!SandboxProviders::paypalPaid($bad, $txn), 'reject PayPal mismatched amount');
$bad = $order;
$bad['id'] = 'DIFFERENTID';
check_provider(!SandboxProviders::paypalPaid($bad, $txn), 'reject PayPal wrong order ID');
$bad = $order;
$bad['status'] = 'APPROVED';
check_provider(!SandboxProviders::paypalPaid($bad, $txn), 'PayPal approval is not successful capture');

$revolutTxn = ['provider_order_id' => '4c89e92c-0bd3-415c-97d7-f84e761dc05d', 'amount_minor' => 3290];
$revolutOrder = ['id' => $revolutTxn['provider_order_id'], 'state' => 'completed', 'amount' => 3290, 'currency' => 'EUR'];
check_provider(SandboxProviders::revolutPaid($revolutOrder, $revolutTxn), 'verified Revolut order status and EUR amount');
$revolutOrder['state'] = 'pending';
check_provider(!SandboxProviders::revolutPaid($revolutOrder, $revolutTxn), 'reject pending Revolut order');
check_provider(SandboxProviders::isCheckoutUrl('https://www.sandbox.paypal.com/checkoutnow?token=ABC1234567', 'paypal'), 'PayPal sandbox URL allowlist');
check_provider(!SandboxProviders::isCheckoutUrl('https://www.sandbox.paypal.com.evil.example/', 'paypal'), 'reject lookalike checkout domain');
check_provider(!SandboxProviders::isCheckoutUrl('http://checkout.revolut.com/', 'revolut'), 'reject non-TLS checkout URL');
check_provider(!SandboxProviders::isCheckoutUrl('https://checkout.stripe.com/', 'revolut'), 'prevent cross-provider redirect');

try {
    SandboxProviders::request('https://attacker.example/steal', 'GET', []);
    throw new RuntimeException('Allowed untrusted provider host');
} catch (RuntimeException $exception) {
    check_provider($exception->getMessage() === 'Unapproved sandbox endpoint', 'reject API SSRF host');
}

$url = StripeConnectSandbox::authorizationUrl('ca_' . str_repeat('a', 20), 'https://pay.brendigo.com/settings/stripe-connect/callback', str_repeat('a', 48));
$query = [];
parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
check_provider(parse_url($url, PHP_URL_HOST) === 'connect.stripe.com'
    && ($query['response_type'] ?? '') === 'code'
    && ($query['scope'] ?? '') === 'read_write'
    && ($query['state'] ?? '') === str_repeat('a', 48), 'Stripe Connect OAuth state and redirect construction');
check_provider(StripeConnectSandbox::validAccount('acct_' . str_repeat('A', 16)), 'valid Connect account identifier');
check_provider(!StripeConnectSandbox::validAccount('acct_../../bad'), 'reject malformed Connect account');
try {
    StripeConnectSandbox::authorizationUrl('ca_' . str_repeat('a', 20), 'https://evil.example/', 'bad');
    throw new RuntimeException('Stripe Connect accepted invalid OAuth state');
} catch (RuntimeException $exception) {
    check_provider($exception->getMessage() === 'Invalid Connect sandbox request', 'reject malformed OAuth state');
}
check_provider(!StripeConnectSandbox::ready(), 'Stripe Connect disabled without operator environment credentials');

echo "All sandbox adapter offline tests passed.\n";
