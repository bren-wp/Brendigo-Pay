<?php

declare(strict_types=1);

namespace BrendigoPay;

use RuntimeException;

final class StripeTest
{
    /** Checkout is deliberately limited to Stripe sandbox/test keys in this starter. */
    public static function createSession(string $testSecret, array $args, string $idempotencyKey, string $connectedAccount = ''): array
    {
        if (!str_starts_with($testSecret, 'sk_test_')) {
            throw new RuntimeException('Only Stripe test keys are supported in this release');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is required for Stripe');
        }
        $fields = [
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $args['amount'],
                    'product_data' => ['name' => $args['description']],
                ],
                'quantity' => 1,
            ]],
            'client_reference_id' => $args['reference'],
            'success_url' => $args['success_url'],
            'cancel_url' => $args['cancel_url'],
            'metadata' => ['bp_transaction' => $args['transaction_id']],
        ];
        $headers = [
            'Authorization: Bearer ' . $testSecret,
            'Idempotency-Key: ' . $idempotencyKey,
        ];
        if ($connectedAccount !== '') {
            if (!preg_match('/^acct_[A-Za-z0-9]{8,64}$/D', $connectedAccount)) {
                throw new RuntimeException('Invalid connected account');
            }
            $headers[] = 'Stripe-Account: ' . $connectedAccount;
        }
        $handle = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        if (!is_string($response)) {
            throw new RuntimeException('Stripe API connection failed');
        }
        $data = json_decode($response, true);
        if ($status !== 200 || !is_array($data) || empty($data['id']) || empty($data['url']) || !str_starts_with($data['url'], 'https://checkout.stripe.com/')) {
            throw new RuntimeException('Stripe did not create a valid test checkout session');
        }
        return ['id' => (string) $data['id'], 'url' => (string) $data['url']];
    }
}
