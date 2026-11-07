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
        update_option('bp_pay_sync_status', ['status' => 'pending', 'last_ok' => 0], false);
    }


    /** WordPress installation origin; no path, credentials or fragment. */
    public static function site_origin(): string
    {
        $parsed = wp_parse_url(home_url('/'));
        if (!is_array($parsed) || !isset($parsed['scheme'], $parsed['host'])) {
            return '';
        }
        $scheme = strtolower((string) $parsed['scheme']);
        $host = strtolower((string) $parsed['host']);
        if (!in_array($scheme, ['https', 'http'], true) || $host === '') {
            return '';
        }
        return $scheme . '://' . $host . (isset($parsed['port']) ? ':' . (int) $parsed['port'] : '');
    }

    /** Stored connectivity state, never payment or customer credentials. */
    public static function sync_status(): array
    {
        $result = get_option('bp_pay_sync_status', []);
        return is_array($result) ? $result : [];
    }

    /** Checkout is fail-closed if license has not been validated recently. */
    public static function license_is_current(): bool
    {
        $status = self::sync_status();
        $settings = get_option(self::OPTION, []);
        return is_array($settings)
            && (string) ($status['status'] ?? '') === 'active'
            && (int) ($status['last_ok'] ?? 0) > time() - 7200
            && (int) ($status['last_ok'] ?? 0) <= time() + 60
            && ($settings['site_id'] ?? '') !== ''
            && hash_equals((string) $settings['site_id'], (string) ($status['site_id'] ?? ''))
            && self::site_origin() !== ''
            && hash_equals(self::site_origin(), (string) ($status['origin'] ?? ''));
    }

    /**
     * Send a bounded, authenticated installation heartbeat. The server verifies
     * subscription, domain, installation identity and token ownership.
     */
    public static function sync_installation(): bool
    {
        $siteOrigin = self::site_origin();
        $installation = get_option('bp_pay_installation_id', '');
        $existing = self::sync_status();
        if ($siteOrigin === '' || !is_string($installation)
            || !preg_match('/^[a-f0-9]{32}$/D', $installation)
            || self::api_key() === '') {
            update_option('bp_pay_sync_status', [
                'status' => 'not_configured', 'last_ok' => 0, 'checked_at' => time(),
            ], false);
            self::record_sync_event('not_configured');
            return false;
        }
        $wpVersion = function_exists('get_bloginfo') ? (string) get_bloginfo('version') : '';
        $wcVersion = defined('WC_VERSION') ? (string) WC_VERSION : '';
        $response = BP_Pay_API::request('POST', '/api/v1/sites/sync', [
            'site_url' => $siteOrigin,
            'installation_id' => $installation,
            'plugin_version' => BP_PAY_VERSION,
            'wordpress_version' => $wpVersion,
            'woocommerce_version' => $wcVersion,
        ]);
        $saved = get_option(self::OPTION, []);
        $saved = is_array($saved) ? $saved : [];
        $ok = is_array($response) && !is_wp_error($response)
            && ($response['mode'] ?? '') === 'test'
            && ($response['status'] ?? '') === 'active'
            && isset($response['site_id'], $response['origin'], $response['plan'], $response['expires_at'])
            && (string) $response['site_id'] === (string) ($saved['site_id'] ?? '')
            && hash_equals($siteOrigin, (string) $response['origin'])
            && in_array((string) $response['plan'], ['start', 'business', 'pro'], true)
            && is_numeric($response['website_limit'] ?? null)
            && is_numeric($response['websites_used'] ?? null);
        if (!$ok) {
            update_option('bp_pay_sync_status', [
                'status' => 'unavailable', 'checked_at' => time(),
                'last_ok' => (int) ($existing['last_ok'] ?? 0),
            ], false);
            self::record_sync_event('unavailable');
            return false;
        }
        update_option('bp_pay_sync_status', [
            'status' => 'active',
            'last_ok' => time(), 'checked_at' => time(),
            'site_id' => (string) $response['site_id'],
            'origin' => $siteOrigin,
            'plan' => (string) $response['plan'],
            'expires_at' => (string) $response['expires_at'],
            'website_limit' => max(0, (int) $response['website_limit']),
            'websites_used' => max(0, (int) $response['websites_used']),
        ], false);
        self::record_sync_event('active');
        return true;
    }

    private static function record_sync_event(string $result): void
    {
        $allowed = ['active', 'unavailable', 'not_configured'];
        if (!in_array($result, $allowed, true)) {
            return;
        }
        $history = get_option('bp_pay_sync_log', []);
        $history = is_array($history) ? array_values($history) : [];
        array_unshift($history, ['timestamp' => time(), 'status' => $result]);
        update_option('bp_pay_sync_log', array_slice($history, 0, 20), false);
    }

    public static function sync_history(): array
    {
        $history = get_option('bp_pay_sync_log', []);
        return is_array($history) ? array_slice($history, 0, 20) : [];
    }

    public static function schedule_sync(): void
    {
        if (!wp_next_scheduled('brendigo_pay_site_sync')) {
            wp_schedule_event(time() + 120, 'hourly', 'brendigo_pay_site_sync');
        }
    }

    public static function unschedule_sync(): void
    {
        wp_clear_scheduled_hook('brendigo_pay_site_sync');
    }

    public static function manual_sync(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Nemate dopuštenje za ovu radnju.', 'brendigo-pay'));
        }
        check_admin_referer('brendigo_pay_sync');
        $ok = self::sync_installation();
        wp_safe_redirect(add_query_arg([
            'page' => 'brendigo-pay',
            'bp_connection' => $ok ? 'success' : 'failure',
        ], admin_url('admin.php')));
        exit;
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
        $partial = false;
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
                        $success = self::sync_installation();
                        if (!$success) {
                            $partial = true; // Activation succeeded, but the live status check did not.
                        }
                    } catch (\Throwable $e) {
                        error_log('Brendigo Pay license activation local storage error.');
                    }
                }
            }
        }
        wp_safe_redirect(add_query_arg(
            ['page' => 'brendigo-pay', 'bp_license_status' => $success ? 'success' : ($partial ? 'partial' : 'failure')],
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
            if (($out['url'] ?? '') !== $candidate) {
                unset($out['token_encrypted'], $out['site_id']);
                update_option('bp_pay_sync_status', ['status' => 'pending', 'last_ok' => 0], false);
            }
            $out['url'] = $candidate;
        } else {
            add_settings_error(self::OPTION, 'invalid_url', __('Unesite HTTPS adresu Brendigo Pay platforme.', 'brendigo-pay'));
        }
        // Only the license activation route can provision a new site-scoped API token.
        if (!empty($input['api_key'])) {
            add_settings_error(self::OPTION, 'deprecated_api_key',
                __('Ručni unos API tokena nije podržan. Aktivirajte licenciju.', 'brendigo-pay'));
        }
        return $out;
    }

    public static function test_connection(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Nemate dopuštenje za ovu radnju.', 'brendigo-pay'));
        }
        check_admin_referer('brendigo_pay_test_connection');
        $ok = self::sync_installation();
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

        // Use native WordPress sidebar items rather than an extra top tab bar.
        // The first child shares the parent slug, avoiding a duplicate dashboard.
        foreach (self::admin_sections() as $slug => $title) {
            add_submenu_page(
                'brendigo-pay',
                $title . ' — Brendigo Pay',
                $title,
                'manage_options',
                $slug,
                [self::class, 'page']
            );
        }
    }

    public static function admin_sections(): array
    {
        return [
            'brendigo-pay' => 'Opće postavke',
            'brendigo-pay-providers' => 'Pružatelji plaćanja',
            'brendigo-pay-links' => 'Platni linkovi',
            'brendigo-pay-qr' => 'QR kodovi',
            'brendigo-pay-security' => 'Sigurnost',
            'brendigo-pay-logs' => 'Dnevnici',
            'brendigo-pay-docs' => 'Dokumentacija',
        ];
    }

    public static function page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $slug = isset($_GET['page']) && is_string($_GET['page'])
            ? sanitize_key(wp_unslash($_GET['page'])) : 'brendigo-pay';
        if (!isset(self::admin_sections()[$slug])) {
            wp_die(esc_html__('Nepoznat odjeljak Brendigo Paya.', 'brendigo-pay'));
        }
        $screenTab = $slug === 'brendigo-pay' ? 'general' : substr($slug, strlen('brendigo-pay-'));
        $screenTitle = self::admin_sections()[$slug];
        $tokenSet = self::api_key() !== '';
        $status = isset($_GET['bp_connection']) ? sanitize_key(wp_unslash($_GET['bp_connection'])) : '';
        require BP_PAY_PATH . 'views/admin-settings.php';
    }
}
