<?php

declare(strict_types=1);

namespace BrendigoPay;

use RuntimeException;

/** Sandbox-only adapters. Never accept caller-controlled hosts or live credentials. */
final class SandboxProviders
{
    public static function request(string $url, string $method, array $headers, ?string $body = null): array
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || !in_array($host, ['api.sandbox.paypal.com', 'sandbox-merchant.revolut.com'], true)
            || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('Unapproved sandbox endpoint');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL is required');
        }
        $handle = curl_init($url);
        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
        ];
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($handle, $options);
        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        if (!is_string($response)) {
            throw new RuntimeException('Sandbox API unavailable');
        }
        $json = json_decode($response, true);
        if ($status < 200 || $status > 299 || !is_array($json)) {
            throw new RuntimeException('Sandbox API rejected request (HTTP ' . $status . ')');
        }
        return $json;
    }

    public static function paypalToken(string $clientId, string $secret): string
    {
        $result = self::request('https://api.sandbox.paypal.com/v1/oauth2/token', 'POST', [
            'Authorization: Basic ' . base64_encode($clientId . ':' . $secret),
            'Content-Type: application/x-www-form-urlencoded',
        ], 'grant_type=client_credentials');
        if (!isset($result['access_token']) || !is_string($result['access_token'])) {
            throw new RuntimeException('PayPal sandbox access token missing');
        }
        return $result['access_token'];
    }

    public static function paypalCreate(string $token, array $args, string $idempotency): array
    {
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $args['transaction_id'],
                'custom_id' => $args['transaction_id'],
                'description' => substr($args['description'], 0, 127),
                'amount' => [
                    'currency_code' => 'EUR',
                    'value' => number_format($args['amount'] / 100, 2, '.', ''),
                ],
            ]],
            'payment_source' => ['paypal' => ['experience_context' => [
                'return_url' => $args['success_url'],
                'cancel_url' => $args['cancel_url'],
                'user_action' => 'PAY_NOW',
            ]]],
        ];
        $result = self::request('https://api.sandbox.paypal.com/v2/checkout/orders', 'POST', [
            'Authorization: Bearer ' . $token,
            'PayPal-Request-Id: ' . $idempotency,
            'Content-Type: application/json',
        ], json_encode($payload, JSON_THROW_ON_ERROR));
        foreach ($result['links'] ?? [] as $link) {
            if (($link['rel'] ?? '') === 'payer-action' || ($link['rel'] ?? '') === 'approve') {
                $url = $link['href'] ?? '';
                if (is_string($url) && self::isCheckoutUrl($url, 'paypal')) {
                    return ['id' => (string) ($result['id'] ?? ''), 'url' => $url];
                }
            }
        }
        throw new RuntimeException('PayPal sandbox payer action URL missing');
    }

    public static function paypalCapture(string $token, string $orderId, string $idempotency): array
    {
        if (!preg_match('/^[A-Z0-9]{10,30}$/D', $orderId)) {
            throw new RuntimeException('Invalid PayPal order ID');
        }
        return self::request('https://api.sandbox.paypal.com/v2/checkout/orders/' . $orderId . '/capture', 'POST', [
            'Authorization: Bearer ' . $token,
            'PayPal-Request-Id: ' . $idempotency,
            'Content-Type: application/json',
        ], '{}');
    }

    public static function paypalVerifyWebhook(string $token, string $webhookId, array $headers, array $event): bool
    {
        foreach (['transmission_id', 'transmission_time', 'cert_url', 'auth_algo', 'transmission_sig'] as $key) {
            if (empty($headers[$key]) || !is_string($headers[$key])) {
                return false;
            }
        }
        if (!preg_match('/^[A-Z0-9_-]{4,100}$/D', $webhookId)) {
            return false;
        }
        $result = self::request('https://api.sandbox.paypal.com/v1/notifications/verify-webhook-signature', 'POST', [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ], json_encode($headers + ['webhook_id' => $webhookId, 'webhook_event' => $event], JSON_THROW_ON_ERROR));
        return ($result['verification_status'] ?? '') === 'SUCCESS';
    }

    public static function paypalPaid(array $order, array $transaction): bool
    {
        if (($order['status'] ?? '') !== 'COMPLETED' || ($order['id'] ?? '') !== ($transaction['provider_order_id'] ?? '')) {
            return false;
        }
        foreach ($order['purchase_units'] ?? [] as $purchase) {
            if (($purchase['reference_id'] ?? '') !== $transaction['id']) {
                continue;
            }
            foreach ($purchase['payments']['captures'] ?? [] as $capture) {
                if (($capture['status'] ?? '') !== 'COMPLETED') {
                    continue;
                }
                $money = $capture['amount'] ?? [];
                if (($money['currency_code'] ?? '') === 'EUR'
                    && money_to_minor((string) ($money['value'] ?? '')) === $transaction['amount_minor']) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function revolutCreate(string $secret, array $args, string $idempotency): array
    {
        $payload = [
            'amount' => $args['amount'],
            'currency' => 'EUR',
            'description' => substr($args['description'], 0, 255),
            'redirect_url' => $args['success_url'],
            'merchant_order_data' => ['reference' => $args['transaction_id']],
        ];
        $result = self::request('https://sandbox-merchant.revolut.com/api/orders', 'POST', [
            'Authorization: Bearer ' . $secret,
            'Revolut-Api-Version: 2026-08-17',
            'Idempotency-Key: ' . $idempotency,
            'Content-Type: application/json',
        ], json_encode($payload, JSON_THROW_ON_ERROR));
        if (!is_string($result['id'] ?? null) || !self::isCheckoutUrl((string) ($result['checkout_url'] ?? ''), 'revolut')) {
            throw new RuntimeException('Revolut sandbox order URL missing');
        }
        return ['id' => $result['id'], 'url' => $result['checkout_url']];
    }

    public static function revolutRetrieve(string $secret, string $orderId): array
    {
        if (!preg_match('/^[a-f0-9-]{36}$/Di', $orderId)) {
            throw new RuntimeException('Invalid Revolut order ID');
        }
        return self::request('https://sandbox-merchant.revolut.com/api/orders/' . $orderId, 'GET', [
            'Authorization: Bearer ' . $secret,
            'Revolut-Api-Version: 2026-08-17',
        ]);
    }

    public static function revolutPaid(array $order, array $txn): bool
    {
        return ($order['id'] ?? '') === ($txn['provider_order_id'] ?? '')
            && strtolower((string) ($order['state'] ?? '')) === 'completed'
            && (int) ($order['amount'] ?? -1) === $txn['amount_minor']
            && strtoupper((string) ($order['currency'] ?? '')) === 'EUR';
    }

    public static function isCheckoutUrl(string $url, string $provider): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme !== 'https' || !is_string($host) || !is_string(parse_url($url, PHP_URL_PATH))) {
            return false;
        }
        return match ($provider) {
            'paypal' => in_array(strtolower($host), ['www.sandbox.paypal.com', 'sandbox.paypal.com'], true),
            'revolut' => in_array(strtolower($host), ['checkout.revolut.com', 'sandbox-checkout.revolut.com', 'checkout.sandbox.revolut.com'], true),
            'stripe' => strtolower($host) === 'checkout.stripe.com',
            default => false,
        };
    }

    public static function verifyRevolut(string $payload, string $timestamp, string $signatures, string $secret, ?int $now = null): bool
    {
        if (!ctype_digit($timestamp) || !preg_match('/^[0-9]{12,15}$/D', $timestamp)) {
            return false;
        }
        $seconds = (int) floor((int) $timestamp / 1000);
        if (abs(($now ?? time()) - $seconds) > 300) {
            return false;
        }
        $expected = hash_hmac('sha256', 'v1.' . $timestamp . '.' . $payload, $secret);
        foreach (explode(',', $signatures) as $signature) {
            $signature = trim($signature);
            if (preg_match('/^v1=([a-f0-9]{64})$/D', $signature, $m) && hash_equals($expected, $m[1])) {
                return true;
            }
        }
        return false;
    }
}
