<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class BP_Pay_API
{
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
