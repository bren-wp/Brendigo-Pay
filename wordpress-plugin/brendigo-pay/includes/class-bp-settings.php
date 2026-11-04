<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class BP_Pay_Settings
{
    private const OPTION = 'brendigo_pay_settings';

    public static function api_base(): string
    {
        $settings = get_option(self::OPTION, []);
        $url = is_array($settings) ? (string) ($settings['url'] ?? '') : '';
        return untrailingslashit($url);
    }

    public static function api_key(): string
    {
        if (defined('BRENDIGO_PAY_API_KEY') && is_string(BRENDIGO_PAY_API_KEY) && BRENDIGO_PAY_API_KEY !== '') {
            return BRENDIGO_PAY_API_KEY;
        }
        $settings = get_option(self::OPTION, []);
        $ciphertext = is_array($settings) ? (string) ($settings['token_encrypted'] ?? '') : '';
        if ($ciphertext === '' || !function_exists('openssl_decrypt')) {
            return '';
        }
        $raw = base64_decode($ciphertext, true);
        if ($raw === false || strlen($raw) < 29) {
            return '';
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $encrypted = substr($raw, 28);
        $key = hash('sha256', wp_salt('auth') . wp_salt('secure_auth'), true);
        $value = openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return is_string($value) ? $value : '';
    }

    private static function encrypt(string $value): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $key = hash('sha256', wp_salt('auth') . wp_salt('secure_auth'), true);
        $raw = openssl_encrypt($value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($raw === false) {
            return '';
        }
        return base64_encode($iv . $tag . $raw);
    }

    /** The license itself is not persisted; only the encrypted installation credential. */
    public static function save_activation(string $token, string $siteId): void
    {
        $settings = get_option(self::OPTION, []);
        $settings = is_array($settings) ? $settings : [];
        $encrypted = self::encrypt($token);
        if ($encrypted === '') {
            throw new \RuntimeException('Cannot encrypt API credential');
        }
        $settings['token_encrypted'] = $encrypted;
        $settings['site_id'] = $siteId;
        update_option(self::OPTION, $settings, false);
    }

    public static function activate_license(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Nemate dopuštenje za aktivaciju.', 'brendigo-pay'));
        }
        check_admin_referer('brendigo_pay_activate_license');
        $license = isset($_POST['bp_license']) && is_string($_POST['bp_license'])
            ? trim(wp_unslash($_POST['bp_license'])) : '';
        $success = false;
        if (preg_match('/^bp_lic_[a-f0-9]{64}$/D', $license)) {
            $installation = get_option('bp_pay_installation_id', '');
            if (!is_string($installation) || !preg_match('/^[a-f0-9]{32}$/D', $installation)) {
                $installation = bin2hex(random_bytes(16));
                add_option('bp_pay_installation_id', $installation, '', false);
                $installation = (string) get_option('bp_pay_installation_id', $installation);
            }
            if (preg_match('/^[a-f0-9]{32}$/D', $installation)) {
                $activation = BP_Pay_API::activate_license($license, $installation);
                if (!is_wp_error($activation)) {
                    try {
                        self::save_activation($activation['api_key'], $activation['site_id']);
                        $success = true;
                    } catch (\Throwable $e) {
                        error_log('Brendigo Pay license activation local storage error.');
                    }
                }
            }
        }
        wp_safe_redirect(add_query_arg(
            ['page' => 'brendigo-pay', 'bp_license_status' => $success ? 'success' : 'failure'],
            admin_url('admin.php')
        ));
        exit;
    }

    public static function register(): void
    {
        register_setting('brendigo_pay_group', self::OPTION, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize'],
            'default' => [],
            'show_in_rest' => false,
        ]);
    }

    public static function sanitize(mixed $input): array
    {
        $existing = get_option(self::OPTION, []);
        $out = is_array($existing) ? $existing : [];
        if (!is_array($input)) {
            return $out;
        }
        $candidate = untrailingslashit(esc_url_raw(trim((string) ($input['url'] ?? ''))));
        $parsed = wp_parse_url($candidate);
        $isLocal = defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'local';
        $validScheme = is_array($parsed) && ($parsed['scheme'] ?? '') === 'https';
        if ($isLocal && is_array($parsed) && ($parsed['scheme'] ?? '') === 'http'
            && in_array(($parsed['host'] ?? ''), ['localhost', '127.0.0.1'], true)) {
            $validScheme = true;
        }
        if ($validScheme && !empty($parsed['host']) && !array_key_exists('user', $parsed) && !array_key_exists('pass', $parsed) && empty($parsed['fragment'])
            && !array_key_exists('query', $parsed) && !array_key_exists('fragment', $parsed)
            && (empty($parsed['path']) || $parsed['path'] === '/')) {
            $out['url'] = $candidate;
        } else {
            add_settings_error(self::OPTION, 'invalid_url', __('Unesite HTTPS adresu Brendigo Pay platforme.', 'brendigo-pay'));
        }
        $secret = trim((string) ($input['api_key'] ?? ''));
        if ($secret !== '') {
            if (preg_match('/^bp_test_[a-f0-9]{64}$/D', $secret)) {
                $out['token_encrypted'] = self::encrypt($secret);
            } else {
                add_settings_error(self::OPTION, 'invalid_key', __('API ključ nije valjan.', 'brendigo-pay'));
            }
        }
        return $out;
    }

    public static function test_connection(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Nemate dopuštenje za ovu radnju.', 'brendigo-pay'));
        }
        check_admin_referer('brendigo_pay_test_connection');
        $response = BP_Pay_API::request('GET', '/api/v1/me');
        $parsed = wp_parse_url(home_url('/'));
        $localOrigin = is_array($parsed) && isset($parsed['scheme'], $parsed['host'])
            ? strtolower($parsed['scheme']) . '://' . strtolower($parsed['host'])
                . (isset($parsed['port']) ? ':' . (int) $parsed['port'] : '')
            : '';
        $ok = !is_wp_error($response) && ($response['mode'] ?? '') === 'test'
            && isset($response['origin'], $response['merchant_name'])
            && (string) $response['origin'] === $localOrigin
            && !empty($response['site_id']) && !empty($response['plan']);
        $args = ['page' => 'brendigo-pay', 'bp_connection' => $ok ? 'success' : 'failure'];
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    public static function menu(): void
    {
        add_menu_page(
            __('Brendigo Pay', 'brendigo-pay'),
            __('Brendigo Pay', 'brendigo-pay'),
            'manage_options',
            'brendigo-pay',
            [self::class, 'page'],
            plugins_url('assets/brand-mark.svg', BP_PAY_FILE),
            56
        );
    }

    public static function page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $tokenSet = self::api_key() !== '';
        $status = isset($_GET['bp_connection']) ? sanitize_key(wp_unslash($_GET['bp_connection'])) : '';
        require BP_PAY_PATH . 'views/admin-settings.php';
    }
}
