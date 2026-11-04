<?php

declare(strict_types=1);

namespace BrendigoPay;

use RuntimeException;

/** Optional Stripe Standard Connect OAuth sandbox. Requires operator's own Connect platform. */
final class StripeConnectSandbox
{
    public static function ready(): bool
    {
        return self::clientId() !== '' && self::secret() !== '' && self::webhookSecret() !== '';
    }

    public static function clientId(): string
    {
        $id = (string) (getenv('BP_STRIPE_CONNECT_CLIENT_ID') ?: '');
        return preg_match('/^ca_[A-Za-z0-9]{10,}$/D', $id) ? $id : '';
    }

    public static function secret(): string
    {
        $key = (string) (getenv('BP_STRIPE_CONNECT_SECRET') ?: '');
        return preg_match('/^sk_test_[A-Za-z0-9]{10,}$/D', $key) ? $key : '';
    }

    public static function webhookSecret(): string
    {
        $secret = (string) (getenv('BP_STRIPE_CONNECT_WEBHOOK_SECRET') ?: '');
        return preg_match('/^whsec_[A-Za-z0-9]{10,}$/D', $secret) ? $secret : '';
    }

    public static function authorizationUrl(string $clientId, string $callback, string $state): string
    {
        if (!preg_match('/^ca_[A-Za-z0-9]{10,}$/D', $clientId)
            || !preg_match('/^[a-f0-9]{48}$/D', $state)
            || !str_starts_with($callback, 'https://') && !str_starts_with($callback, 'http://127.0.0.1:')) {
            throw new RuntimeException('Invalid Connect sandbox request');
        }
        return 'https://connect.stripe.com/oauth/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'scope' => 'read_write',
            'redirect_uri' => $callback,
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private static function stripeOauthCall(string $endpoint, string $platformSecret, array $fields): array
    {
        if (!in_array($endpoint, ['token', 'deauthorize'], true) || !str_starts_with($platformSecret, 'sk_test_')
            || !function_exists('curl_init')) {
            throw new RuntimeException('Connect sandbox configuration unavailable');
        }
        $h = curl_init('https://connect.stripe.com/oauth/' . $endpoint);
        curl_setopt_array($h, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields, '', '&', PHP_QUERY_RFC3986),
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $platformSecret . ':',
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $response = curl_exec($h);
        $status = (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE);
        curl_close($h);
        $json = is_string($response) ? json_decode($response, true) : null;
        if ($status !== 200 || !is_array($json)) {
            throw new RuntimeException('Stripe Connect sandbox OAuth rejected the request');
        }
        return $json;
    }

    public static function exchange(string $code): string
    {
        if (!preg_match('/^ac_[A-Za-z0-9_]{8,256}$/D', $code) || !self::ready()) {
            throw new RuntimeException('Invalid Connect authorization code');
        }
        $json = self::stripeOauthCall('token', self::secret(), ['code' => $code, 'grant_type' => 'authorization_code']);
        $account = $json['stripe_user_id'] ?? null;
        if (!is_string($account) || !self::validAccount($account) || ($json['livemode'] ?? null) !== false
            || ($json['scope'] ?? '') !== 'read_write') {
            throw new RuntimeException('Connect account is not a test-mode read_write account');
        }
        return $account;
    }

    public static function deauthorize(string $accountId): void
    {
        if (!self::ready() || !self::validAccount($accountId)) {
            throw new RuntimeException('Invalid Connect account');
        }
        $result = self::stripeOauthCall('deauthorize', self::secret(), [
            'client_id' => self::clientId(), 'stripe_user_id' => $accountId,
        ]);
        if (($result['stripe_user_id'] ?? '') !== $accountId) {
            throw new RuntimeException('Connect revocation not confirmed');
        }
    }

    public static function validAccount(string $accountId): bool
    {
        return preg_match('/^acct_[A-Za-z0-9]{8,64}$/D', $accountId) === 1;
    }
}
