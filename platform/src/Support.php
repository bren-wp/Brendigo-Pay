<?php

declare(strict_types=1);

namespace BrendigoPay;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function random_id(int $bytes = 16): string
{
    return bin2hex(random_bytes($bytes));
}

function money_to_minor(string $value): ?int
{
    $value = trim(str_replace(',', '.', $value));
    if (!preg_match('/^(\d{1,7})(?:\.(\d{1,2}))?$/D', $value, $matches)) {
        return null;
    }
    $minor = (int) $matches[1] * 100 + (int) str_pad($matches[2] ?? '0', 2, '0');
    return $minor > 0 && $minor <= 999999999 ? $minor : null;
}

function minor_format(int $amount): string
{
    return number_format($amount / 100, 2, ',', '.') . ' €';
}

function safe_origin(string $url, bool $allowLocal = false): ?string
{
    $p = parse_url($url);
    if ($p === false || !isset($p['scheme'], $p['host']) || isset($p['user']) || isset($p['pass']) || isset($p['fragment'])) {
        return null;
    }
    $scheme = strtolower($p['scheme']);
    $host = strtolower($p['host']);
    if ($scheme !== 'https' && !($allowLocal && $scheme === 'http' && in_array($host, ['localhost', '127.0.0.1'], true))) {
        return null;
    }
    if (!preg_match('/^[a-z0-9.-]+$/D', $host) || strlen($host) > 253) {
        return null;
    }
    $port = isset($p['port']) ? ':' . $p['port'] : '';
    return $scheme . '://' . $host . $port;
}

function token_encrypt(string $plaintext, string $appKey): string
{
    $key = base64_decode($appKey, true);
    if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        throw new \RuntimeException('Invalid APP_KEY');
    }
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    return base64_encode($nonce . sodium_crypto_secretbox($plaintext, $nonce, $key));
}

function token_decrypt(string $ciphertext, string $appKey): string
{
    $data = base64_decode($ciphertext, true);
    $key = base64_decode($appKey, true);
    if ($data === false || $key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES || strlen($data) < 40) {
        throw new \RuntimeException('Invalid encrypted data');
    }
    $nonce = substr($data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $plaintext = sodium_crypto_secretbox_open(substr($data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, $key);
    if ($plaintext === false) {
        throw new \RuntimeException('Cannot decrypt data');
    }
    return $plaintext;
}

function verify_stripe_signature(string $payload, string $header, string $secret, int $now = 0): bool
{
    $now = $now ?: time();
    $timestamp = null;
    $signatures = [];
    foreach (explode(',', $header) as $part) {
        [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($key === 't' && ctype_digit($value)) {
            $timestamp = (int) $value;
        } elseif ($key === 'v1' && preg_match('/^[a-f0-9]{64}$/D', $value)) {
            $signatures[] = $value;
        }
    }
    if ($timestamp === null || abs($now - $timestamp) > 300) {
        return false;
    }
    $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    foreach ($signatures as $signature) {
        if (hash_equals($expected, $signature)) {
            return true;
        }
    }
    return false;
}
