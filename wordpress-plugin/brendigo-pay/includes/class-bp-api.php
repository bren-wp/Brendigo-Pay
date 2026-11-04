<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class BP_Pay_API
{
    /** Exchange an origin-scoped paid license for a site-scoped sandbox API credential. */
    public static function activate_license(string $license, string $installationId): array|WP_Error
    {
        $base = BP_Pay_Settings::api_base();
        if ($base === '') {
            return new WP_Error('bp_no_platform', __('Najprije spremite URL Brendigo Pay platforme.', 'brendigo-pay'));
        }
        $siteParts = wp_parse_url(home_url('/'));
        if (!is_array($siteParts) || !isset($siteParts['scheme'], $siteParts['host'])) {
            return new WP_Error('bp_bad_origin', __('Neispravna WordPress domena.', 'brendigo-pay'));
        }
        $origin = strtolower((string) $siteParts['scheme']) . '://' . strtolower((string) $siteParts['host'])
            . (isset($siteParts['port']) ? ':' . (int) $siteParts['port'] : '');
        $result = wp_safe_remote_request($base . '/api/v1/license/activate', [
            'method' => 'POST',
            'timeout' => 25,
            'redirection' => 0,
            'reject_unsafe_urls' => true,
            'limit_response_size' => 16384,
            'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'license_key' => $license, 'site_url' => $origin, 'installation_id' => $installationId,
            ]),
        ]);
        if (is_wp_error($result)) { return $result; }
        $response = json_decode(wp_remote_retrieve_body($result), true);
        if (wp_remote_retrieve_response_code($result) !== 200 || !is_array($response)
            || !preg_match('/^bp_test_[a-f0-9]{64}$/D', (string) ($response['api_key'] ?? ''))
            || ($response['origin'] ?? '') !== $origin || ($response['mode'] ?? '') !== 'test'
            || !preg_match('/^[a-f0-9]{24}$/D', (string) ($response['site_id'] ?? ''))) {
            return new WP_Error('bp_activation_failed', __('Aktivacija nije uspjela. Provjerite licenciju, domenu i aktivni paket.', 'brendigo-pay'));
        }
        return $response;
    }

    public static function request(string $method, string $path, ?array $data = null): array|WP_Error
    {
        $base = BP_Pay_Settings::api_base();
        $key = BP_Pay_Settings::api_key();
        if ($base === '' || $key === '') {
            return new WP_Error('bp_missing_config', __('Nisu konfigurirani API URL i ključ.', 'brendigo-pay'));
        }
        if (!str_starts_with($path, '/api/v1/')) {
            return new WP_Error('bp_bad_path', __('Neispravan API put.', 'brendigo-pay'));
        }
        $url = $base . $path;
        $opts = [
            'method' => $method,
            'timeout' => 25,
            'redirection' => 0,
            'limit_response_size' => 131072,
            'reject_unsafe_urls' => true,
            'headers' => [
                'Authorization' => 'Bearer ' . $key,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];
        if ($data !== null) {
            $opts['body'] = wp_json_encode($data);
        }
        $result = wp_safe_remote_request($url, $opts);
        if (is_wp_error($result)) {
            return $result;
        }
        $code = wp_remote_retrieve_response_code($result);
        $json = json_decode(wp_remote_retrieve_body($result), true);
        if ($code < 200 || $code >= 300 || !is_array($json)) {
            return new WP_Error('bp_api_error', __('Brendigo Pay API nije prihvatio zahtjev.', 'brendigo-pay'));
        }
        return $json;
    }
}
