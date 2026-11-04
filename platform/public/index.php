<?php

declare(strict_types=1);

use BrendigoPay\JsonStore;
use BrendigoPay\StripeTest;
use BrendigoPay\StripeConnectSandbox;
use BrendigoPay\SandboxProviders;
use BrendigoPay\MobileApi;
use function BrendigoPay\e;
use function BrendigoPay\minor_format;
use function BrendigoPay\money_to_minor;
use function BrendigoPay\random_id;
use function BrendigoPay\safe_origin;
use function BrendigoPay\token_decrypt;
use function BrendigoPay\token_encrypt;
use function BrendigoPay\verify_stripe_signature;

require dirname(__DIR__) . '/src/JsonStore.php';
require dirname(__DIR__) . '/src/JsonSessionHandler.php';
require dirname(__DIR__) . '/src/Support.php';
require dirname(__DIR__) . '/src/Plans.php';
require dirname(__DIR__) . '/src/SiteLicenses.php';
require dirname(__DIR__) . '/src/StripeTest.php';
require dirname(__DIR__) . '/src/SandboxProviders.php';
require dirname(__DIR__) . '/src/StripeConnectSandbox.php';
require dirname(__DIR__) . '/src/Ui.php';
require dirname(__DIR__) . '/src/MobileApi.php';

$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) {
    http_response_code(503);
    exit('Brendigo Pay nije konfiguriran. Pokrenite php bin/setup.php.');
}
$config = require $configFile;
$store = new JsonStore($config['data_dir']);
$plans = new \BrendigoPay\Plans($store);
$licenses = new \BrendigoPay\SiteLicenses($store, $plans, (bool) $config['is_local']);
$mobileApi = new MobileApi($store, $plans, (string) $config['base_url']);
$base = $config['base_url'];
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$isLocal = (bool) $config['is_local'];

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow'); // Development/test release only.
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; base-uri 'none'; object-src 'none'; frame-ancestors 'none'; form-action 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:");
if (!$isLocal) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

if (!str_starts_with($path, '/api/') && !str_starts_with($path, '/webhooks/')) {
    session_name('bp_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !$isLocal,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    $sessionDir = $config['data_dir'] . '/sessions';
    ini_set('session.sid_bits_per_character', '4');
    session_set_save_handler(new \BrendigoPay\JsonSessionHandler($store, $sessionDir), true);
    session_start();
}

function csrf(): string
{
    $_SESSION['csrf'] ??= random_id();
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function check_csrf(): void
{
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Zahtjev je istekao. Osvježite stranicu.');
    }
}

function render(string $title, string $content): never
{
    global $path, $config;
    $user = current_user();
    $marketing = in_array($path, ['/', '/pricing', '/plugin', '/docs', '/login', '/register'], true);
    $merchant = $user !== null && (str_starts_with($path, '/dashboard') || $path === '/settings' || $path === '/sites' || $path === '/admin');
    $bodyClass = $merchant ? 'bp-body-merchant' : ($marketing ? 'bp-body-marketing' : 'bp-body-checkout');
    $nav = $user === null
        ? '<a href="/#mogucnosti">Mogućnosti</a><a href="/pricing">Cijene</a><a href="/plugin">Plugin</a><a href="/docs">Dokumentacija</a><a href="/login">Prijava</a><a class="btn" href="/register">Započni →</a>'
        : '<a href="/dashboard">Pregled</a><a href="/settings">Postavke</a><form method="post" action="/logout">' . csrf() . '<button class="nav-button" type="submit">Odjava</button></form>';
    if (str_starts_with($path, '/p/')) {
        $nav = '<span class="bp-checkout-nav-label">♢ Sigurna testna naplata</span><a href="/docs">Pomoć</a>';
    }
    $sidebar = '';
    if ($merchant) {
        $tabs = [
            ['Pregled', '/dashboard', '⌂', $path === '/dashboard' && ($_GET['tab'] ?? 'overview') === 'overview'],
            ['Platni linkovi', '/dashboard?tab=links', '↗', ($_GET['tab'] ?? '') === 'links'],
            ['Transakcije', '/dashboard?tab=transactions', '⇄', ($_GET['tab'] ?? '') === 'transactions'],
            ['Integracije', '/dashboard?tab=integrations', '◇', ($_GET['tab'] ?? '') === 'integrations'],
            ['Web-stranice', '/sites', '▣', $path === '/sites'],
            ['Postavke', '/settings', '⚙', $path === '/settings'],
        ];
        if (in_array($user['id'], (array) ($config['admin_user_ids'] ?? []), true)) {
            $tabs[] = ['Admin', '/admin', '♢', $path === '/admin'];
        }
        $sideLinks = '';
        foreach ($tabs as [$label, $url, $icon, $active]) {
            $sideLinks .= '<a href="' . e($url) . '" class="' . ($active ? 'active' : '') . '"><span aria-hidden="true">' . e($icon) . '</span>' . e($label) . '</a>';
        }
        $sidebar = '<aside class="bp-merchant-sidebar" aria-label="Izbornik trgovca">' . bp_brand() . '<nav class="bp-sidebar-menu">' . $sideLinks . '</nav><div class="bp-sidebar-bottom"><b>Brendigo Pay Sandbox</b><p>Testna plaćanja i povezivanje vlastitih računa.</p></div></aside>';
    }
    echo '<!doctype html><html lang="hr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . e($title) . ' | Brendigo Pay</title><meta name="theme-color" content="#1e2bb8"><link rel="icon" type="image/svg+xml" href="/assets/favicon.svg"><link rel="apple-touch-icon" href="/assets/apple-touch-icon.png"><meta name="application-name" content="Brendigo Pay"><link rel="stylesheet" href="/assets/app.css"><script src="/assets/app.js" defer></script></head><body class="' . $bodyClass . '"><a class="bp-skip-link" href="#main-content">Preskoči na sadržaj</a>';
    echo '<header class="top"><div class="container top-inner"><div class="bp-header-left"><a href="/" aria-label="Brendigo Pay početna">' . bp_brand() . '</a><span class="bp-tagline">PAMETNA PLAĆANJA<br>ZA MODERAN BIZNIS</span></div><button class="bp-mobile-toggle" data-toggle-menu aria-expanded="false" aria-controls="bp-mobile-navigation" aria-label="Otvori izbornik">☰</button><nav id="bp-mobile-navigation" aria-label="Glavna navigacija">' . $nav . '</nav></div></header>';
    if ($merchant) {
        echo '<div class="bp-merchant-shell">' . $sidebar . '<main class="bp-merchant-main" id="main-content">' . $content . '</main></div>';
    } else {
        echo '<main class="container ' . ($marketing ? 'bp-marketing-main' : '') . '" id="main-content">' . $content . '</main>';
    }
    echo '<footer class="container footer"><span>© ' . date('Y') . ' Brendigo Pay · <a href="https://brendigo.com" target="_blank" rel="noopener">Brendigo</a></span><nav aria-label="Poveznice podnožja"><a href="/">Naslovnica</a><a href="/pricing">Cijene</a><a href="/plugin">WordPress</a><a href="/docs">Upute</a><a href="https://brendigopay.com" target="_blank" rel="noopener">BrendigoPay.com</a></nav><span>v0.8.2 • SANDBOX • Bez stvarnih plaćanja</span></footer></body></html>';
    exit;
}

function message(string $text, string $type = 'warn'): string
{
    return '<div class="message ' . e($type) . '">' . e($text) . '</div>';
}

function current_user(): ?array
{
    global $store;
    $id = $_SESSION['user_id'] ?? null;
    return is_string($id) ? $store->read('users', $id) : null;
}

function login_required(): array
{
    $user = current_user();
    if ($user === null) {
        header('Location: /login', true, 303);
        exit;
    }
    return $user;
}

function redirect(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

function audit_event(string $userId, string $action, array $details = []): void
{
    global $store;
    // Never record card details, API credentials, session contents or provider secrets.
    $store->write('audit', random_id(), [
        'user_id' => $userId,
        'action' => $action,
        'details' => $details,
        'created_at' => gmdate('c'),
    ]);
}

function spreadsheet_cell(string $value): string
{
    // Excel / LibreOffice formula injection protection for exported user-supplied descriptions.
    if (preg_match('/^[\s\x00-\x1F]*[=+@-]/u', $value) === 1) {
        return "'" . $value;
    }
    return $value;
}

function fail_api(int $status, string $reason): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['error' => $reason], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_response(array $value, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}

function json_request(): array
{
    if (strlen((string) ($_SERVER['CONTENT_LENGTH'] ?? '0')) > 65536) {
        fail_api(413, 'Request too large');
    }
    $body = file_get_contents('php://input', false, null, 0, 65537);
    if (!is_string($body) || strlen($body) > 65536) {
        fail_api(413, 'Request too large');
    }
    $data = json_decode($body, true);
    if (!is_array($data)) {
        fail_api(400, 'Expected JSON object');
    }
    return $data;
}

function authenticate_api(): array
{
    global $store;
    $authorization = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if (!preg_match('/^Bearer (bp_test_[a-f0-9]{64})$/D', $authorization, $m)) {
        fail_api(401, 'Invalid API credential');
    }
    $token = $store->read('tokens', hash('sha256', $m[1]));
    if ($token === null || !empty($token['revoked'])) {
        fail_api(401, 'Invalid API credential');
    }
    global $plans, $store;
    if ($plans->current((string) ($token['user_id'] ?? '')) === null) {
        fail_api(403, 'Paid plan inactive or expired');
    }
    if (empty($token['site_id'])) {
        fail_api(403, 'Legacy API token must be replaced with a site license');
    }
    $site = $store->read('sites', (string) $token['site_id']);
    if ($site === null || ($site['status'] ?? '') !== 'active'
        || ($site['user_id'] ?? '') !== $token['user_id']
        || ($site['origin'] ?? '') !== ($token['origin'] ?? '')) {
        fail_api(403, 'Website license inactive');
    }
    return $token;
}

function valid_checkout_url(string $url, string $origin): bool
{
    global $isLocal;
    return strlen($url) <= 1500 && safe_origin($url, $isLocal) === $origin;
}

/** Direct merchant credentials are isolated from Stripe Connect shared platform credentials. */
function direct_stripe_settings(string $userId): ?array
{
    global $store, $config;
    $settings = $store->read('stripe', $userId);
    if ($settings === null) {
        return null;
    }
    try {
        return [
            'key' => token_decrypt($settings['key'], $config['app_key']),
            'webhook_secret' => token_decrypt($settings['webhook_secret'], $config['app_key']),
        ];
    } catch (Throwable $exception) {
        error_log('Brendigo Pay cannot decrypt direct Stripe credentials');
        return null;
    }
}

function test_settings(string $userId): ?array
{
    global $store;
    $connected = $store->read('stripe_connect', $userId);
    if ($connected !== null && StripeConnectSandbox::ready()
        && StripeConnectSandbox::validAccount((string) ($connected['account_id'] ?? ''))) {
        return [
            'key' => StripeConnectSandbox::secret(),
            'webhook_secret' => StripeConnectSandbox::webhookSecret(),
            'connected_account_id' => $connected['account_id'],
        ];
    }
    return direct_stripe_settings($userId);
}

/** Merchant-specific sandbox credentials, never shared with another account. */
function sandbox_settings(string $userId, string $provider): ?array
{
    global $store, $config;
    if (!in_array($provider, ['paypal', 'revolut'], true)) {
        return null;
    }
    $record = $store->read($provider, $userId);
    if ($record === null) {
        return null;
    }
    try {
        if ($provider === 'paypal') {
            return [
                'client_id' => token_decrypt($record['client_id'], $config['app_key']),
                'secret' => token_decrypt($record['secret'], $config['app_key']),
                'webhook_id' => $record['webhook_id'] ?? '',
            ];
        }
        return [
            'secret' => token_decrypt($record['secret'], $config['app_key']),
            'webhook_secret' => token_decrypt($record['webhook_secret'], $config['app_key']),
        ];
    } catch (Throwable $exception) {
        error_log('Brendigo Pay cannot decrypt provider settings: ' . $provider);
        return null;
    }
}

function configured_providers(string $userId): array
{
    global $store;
    return [
        'stripe' => test_settings($userId) !== null,
        'paypal' => $store->read('paypal', $userId) !== null,
        'revolut' => $store->read('revolut', $userId) !== null,
    ];
}

function provider_form_sections(string $base, array $user): string
{
    global $store;
    $id = $user['id'];
    $paypal = $store->read('paypal', $id) !== null ? 'Konfiguriran' : 'Nije konfiguriran';
    $revolut = $store->read('revolut', $id) !== null ? 'Konfiguriran' : 'Nije konfiguriran';
    $connected = $store->read('stripe_connect', $id);
    if (is_array($connected) && StripeConnectSandbox::ready()) {
        $connectUi = '<p>Stripe testni račun povezan: <code>' . e($connected['account_id']) . '</code></p><form method="post" action="/settings/stripe-connect/disconnect">' . csrf() . '<button class="btn secondary" type="submit">Odspoji Stripe Connect TEST</button></form>';
    } elseif (StripeConnectSandbox::ready()) {
        $connectUi = '<p>Koristite službenu Stripe OAuth autorizaciju Standard računa. Nije potrebno slati vlastiti API tajni ključ platformi.</p><form method="post" action="/settings/stripe-connect/start">' . csrf() . '<button class="btn" type="submit">Poveži Stripe TEST račun</button></form>';
    } else {
        $connectUi = '<p>Opcionalno: administrator platforme prvo mora postaviti BP_STRIPE_CONNECT_CLIENT_ID, BP_STRIPE_CONNECT_SECRET (sk_test) i BP_STRIPE_CONNECT_WEBHOOK_SECRET kao zaštićene varijable okruženja. Stripe Connect nije aktivan bez te konfiguracije.</p>';
    }
    return '<section class="card stack"><h2>Stripe Connect OAuth (TEST)</h2>' . $connectUi . '</section>'
        . '<section class="card stack"><h2>PayPal Sandbox</h2><p>Merchant REST app: Client ID i Secret iz PayPal Developer Sandbox okruženja. Webhook endpoint: <code>' . e($base . '/webhooks/paypal/' . $id) . '</code>. Pretplatite se na PAYMENT.CAPTURE.COMPLETED i unesite Webhook ID.</p><p>Status: <strong>' . $paypal . '</strong></p><form method="post" action="/settings/paypal">' . csrf() . '<label>Sandbox Client ID<input name="client_id" required autocomplete="off" maxlength="256"></label><label>Sandbox Secret<input name="secret" type="password" required autocomplete="off" maxlength="512"></label><label>Webhook ID<input name="webhook_id" autocomplete="off" maxlength="100"></label><button class="btn" type="submit">Spremi PayPal Sandbox</button></form></section>'
        . '<section class="card stack"><h2>Revolut Sandbox</h2><p>Merchant Sandbox ključ i Webhook signing secret. Webhook: <code>' . e($base . '/webhooks/revolut/' . $id) . '</code>. Merchant API: 2026-08-17.</p><p>Status: <strong>' . $revolut . '</strong></p><form method="post" action="/settings/revolut">' . csrf() . '<label>Sandbox Merchant Secret<input name="secret" type="password" required autocomplete="off" maxlength="512"></label><label>Webhook signing secret<input name="webhook_secret" type="password" required autocomplete="off" maxlength="256"></label><button class="btn" type="submit">Spremi Revolut Sandbox</button></form></section>';
}

function start_checkout(string $userId, int $amount, string $description, string $reference, string $success, string $cancel, string $uniqueReference = '', string $provider = 'stripe'): array
{
    global $store, $base;
    if (!in_array($provider, ['stripe', 'paypal', 'revolut'], true)) {
        throw new RuntimeException('Unsupported payment provider');
    }
    $settings = $provider === 'stripe' ? test_settings($userId) : sandbox_settings($userId, $provider);
    if ($settings === null) {
        throw new RuntimeException('Sandbox provider is not configured');
    }
    // Existing Stripe deterministic IDs remain stable across the v0.2 -> v0.3 upgrade.
    $idempotencyData = $userId . ':' . $uniqueReference . ':' . $reference . ($provider === 'stripe' ? '' : ':' . $provider);
    $lockKey = 'checkout_' . hash('sha256', $idempotencyData);
    return $store->locked($lockKey, static function () use ($store, $base, $userId, $amount, $description, $reference, $success, $cancel, $uniqueReference, $settings, $provider, $idempotencyData): array {
        $existingId = $uniqueReference !== '' ? hash('sha256', $idempotencyData) : random_id();
        $txn = $store->read('transactions', $existingId);
        if ($txn !== null && ($txn['amount_minor'] !== $amount || $txn['reference'] !== $reference || ($txn['provider'] ?? 'stripe') !== $provider)) {
            throw new RuntimeException('Checkout reference already exists with different details');
        }
        if ($txn !== null && !empty($txn['checkout_url'])) {
            return $txn;
        }
        $txn ??= [
            'id' => $existingId,
            'user_id' => $userId,
            'reference' => $reference,
            'description' => $description,
            'amount_minor' => $amount,
            'currency' => 'EUR',
            'provider' => $provider,
            'stripe_connect_account_id' => $provider === 'stripe' ? ($settings['connected_account_id'] ?? '') : '',
            'success_url' => $success,
            'status' => 'pending',
            'created_at' => gmdate('c'),
        ];
        $store->write('transactions', $txn['id'], $txn);
        $args = [
            'amount' => $amount,
            'description' => $description,
            'reference' => $reference,
            'success_url' => $success,
            'cancel_url' => $cancel,
            'transaction_id' => $txn['id'],
        ];
        if ($provider === 'stripe') {
            $session = StripeTest::createSession($settings['key'], $args, 'bp_' . $txn['id'], (string) ($settings['connected_account_id'] ?? ''));
            $txn['session_id'] = $session['id'];
        } elseif ($provider === 'paypal') {
            $args['success_url'] = $base . '/callbacks/paypal/' . $txn['id'];
            $token = SandboxProviders::paypalToken($settings['client_id'], $settings['secret']);
            $session = SandboxProviders::paypalCreate($token, $args, 'bp_' . $txn['id']);
            $txn['provider_order_id'] = $session['id'];
        } else {
            $session = SandboxProviders::revolutCreate($settings['secret'], $args, 'bp_' . $txn['id']);
            $txn['provider_order_id'] = $session['id'];
        }
        $txn['checkout_url'] = $session['url'];
        $store->write('transactions', $txn['id'], $txn);
        if (isset($txn['provider_order_id'])) {
            $store->write('provider_orders', hash('sha256', $provider . ':' . $txn['provider_order_id']), [
                'transaction_id' => $txn['id'], 'provider' => $provider, 'user_id' => $userId,
            ]);
        }
        return $txn;
    });
}

// Stripe Connect OAuth for existing Standard accounts, sandbox only.
if ($path === '/settings/stripe-connect/start' && $method === 'POST') {
    $user = login_required();
    check_csrf();
    if (!StripeConnectSandbox::ready() || $store->read('stripe_connect', $user['id']) !== null) {
        http_response_code(409);
        render('Stripe Connect', message('Stripe Connect TEST nije konfiguriran ili račun već postoji.'));
    }
    $state = random_id(24);
    $_SESSION['stripe_oauth_state'] = [
        'hash' => hash('sha256', $state), 'user_id' => $user['id'], 'issued_at' => time(),
    ];
    redirect(StripeConnectSandbox::authorizationUrl(StripeConnectSandbox::clientId(), $base . '/settings/stripe-connect/callback', $state));
}

if ($path === '/settings/stripe-connect/callback' && $method === 'GET') {
    $user = login_required();
    $record = $_SESSION['stripe_oauth_state'] ?? null;
    unset($_SESSION['stripe_oauth_state']); // OAuth authorization code must be single use.
    $state = (string) ($_GET['state'] ?? '');
    if (!is_array($record) || !preg_match('/^[a-f0-9]{48}$/D', $state)
        || !hash_equals((string) ($record['hash'] ?? ''), hash('sha256', $state))
        || ($record['user_id'] ?? null) !== $user['id']
        || (time() - (int) ($record['issued_at'] ?? 0)) > 600) {
        http_response_code(419);
        render('Stripe Connect', message('Nevaljana ili istekla Stripe OAuth autorizacija.'));
    }
    if (!empty($_GET['error'])) {
        render('Stripe Connect', message('Stripe autorizacija nije dovršena.'));
    }
    try {
        $accountId = StripeConnectSandbox::exchange((string) ($_GET['code'] ?? ''));
        $hash = hash('sha256', $accountId);
        $store->locked('stripe_connect_' . $hash, static function () use ($store, $user, $accountId, $hash): void {
            $existing = $store->read('stripe_connect_accounts', $hash);
            if ($existing !== null && ($existing['user_id'] ?? '') !== $user['id']) {
                throw new RuntimeException('Stripe account already linked to another user');
            }
            if ($store->read('stripe_connect', $user['id']) !== null) {
                throw new RuntimeException('Merchant already linked to Stripe Connect');
            }
            $store->write('stripe_connect', $user['id'], ['account_id' => $accountId, 'linked_at' => gmdate('c')]);
            $store->write('stripe_connect_accounts', $hash, ['user_id' => $user['id'], 'account_id' => $accountId]);
        });
        audit_event($user['id'], 'stripe_connect_test_linked', ['account_id' => $accountId]);
        redirect('/settings');
    } catch (Throwable $exception) {
        error_log('Brendigo Pay Stripe Connect sandbox OAuth: ' . get_class($exception));
        http_response_code(503);
        render('Stripe Connect', message('Povezivanje nije potvrđeno. Provjerite Stripe Connect TEST konfiguraciju.'));
    }
}

if ($path === '/settings/stripe-connect/disconnect' && $method === 'POST') {
    $user = login_required();
    check_csrf();
    $connected = $store->read('stripe_connect', $user['id']);
    if ($connected === null || !StripeConnectSandbox::ready()) {
        http_response_code(404);
        render('Stripe Connect', message('Stripe Connect TEST račun nije povezan.'));
    }
    try {
        $accountId = $connected['account_id'];
        $hash = hash('sha256', $accountId);
        $store->locked('stripe_connect_' . $hash, static function () use ($store, $user, $hash, $accountId): void {
            $connected = $store->read('stripe_connect', $user['id']);
            if ($connected === null || ($connected['account_id'] ?? '') !== $accountId) {
                return;
            }
            StripeConnectSandbox::deauthorize($accountId);
            $store->delete('stripe_connect', $user['id']);
            $store->delete('stripe_connect_accounts', $hash);
        });
        audit_event($user['id'], 'stripe_connect_test_disconnected', ['account_id' => $accountId]);
        redirect('/settings');
    } catch (Throwable $exception) {
        error_log('Brendigo Pay Stripe Connect sandbox deauthorize: ' . get_class($exception));
        http_response_code(503);
        render('Stripe Connect', message('Odspajanje nije potvrđeno kod Stripea. Provjerite Stripe Connect nadzornu ploču.'));
    }
}

// Stripe Connect account events are signed by the platform Connect webhook secret.
if ($path === '/webhooks/stripe-connect' && $method === 'POST') {
    if (!StripeConnectSandbox::ready()) {
        http_response_code(404);
        exit;
    }
    $body = file_get_contents('php://input', false, null, 0, 1000001) ?: '';
    if (strlen($body) > 1000000 || !verify_stripe_signature($body, (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''), StripeConnectSandbox::webhookSecret())) {
        http_response_code(400);
        exit('Invalid Connect signature');
    }
    $event = json_decode($body, true);
    $accountId = (string) ($event['account'] ?? '');
    if (!StripeConnectSandbox::validAccount($accountId)) {
        http_response_code(400);
        exit('Missing connected account');
    }
    if (!in_array((string) ($event['type'] ?? ''), ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
        http_response_code(200);
        exit('ok');
    }
    $session = $event['data']['object'] ?? [];
    $id = (string) ($session['metadata']['bp_transaction'] ?? '');
    if (!preg_match('/^[a-f0-9]{32,64}$/D', $id)) {
        http_response_code(400);
        exit('Invalid transaction metadata');
    }
    $map = $store->read('stripe_connect_accounts', hash('sha256', $accountId));
    if ($map === null) {
        http_response_code(404);
        exit('Unknown connected account');
    }
    try {
        $result = $store->locked('payment_' . $id, static function () use ($store, $id, $map, $accountId, $session): bool {
        $txn = $store->read('transactions', $id);
        if ($txn === null || ($txn['user_id'] ?? '') !== $map['user_id'] || ($txn['stripe_connect_account_id'] ?? '') !== $accountId
            || ($txn['provider'] ?? '') !== 'stripe') {
            return false;
        }
        if (empty($txn['session_id'])) {
            throw new RuntimeException('Connect checkout has not persisted session yet');
        }
        if (($txn['session_id'] ?? '') !== ($session['id'] ?? '') || $txn['amount_minor'] !== ($session['amount_total'] ?? null)
            || strtolower((string) ($session['currency'] ?? '')) !== 'eur' || ($session['payment_status'] ?? '') !== 'paid') {
            return false;
        }
        if (($txn['status'] ?? '') !== 'paid') {
            $txn['status'] = 'paid';
            $txn['paid_at'] = gmdate('c');
            $store->write('transactions', $txn['id'], $txn);
        }
        return true;
    });
    } catch (Throwable $exception) {
        error_log('Brendigo Pay Connect webhook persistence issue: ' . get_class($exception));
        http_response_code(503);
        exit('Try again');
    }
    http_response_code($result ? 200 : 409);
    exit($result ? 'ok' : 'Transaction mismatch');
}

// PayPal return is NOT an indication of payment: capture and inspect the server-side result.
if ($method === 'GET' && preg_match('#^/callbacks/paypal/([a-f0-9]{32,64})$#D', $path, $m)) {
    $id = $m[1];
    $txn = $store->read('transactions', $id);
    if ($txn === null || ($txn['provider'] ?? '') !== 'paypal' || !hash_equals((string) ($txn['provider_order_id'] ?? ''), (string) ($_GET['token'] ?? ''))) {
        http_response_code(404);
        render('Nije pronađeno', message('Transakcija nije pronađena.'));
    }
    try {
        $state = $store->locked('payment_' . $id, static function () use ($store, $id): string {
            $txn = $store->read('transactions', $id);
            if ($txn === null) {
                return 'invalid';
            }
            if (($txn['status'] ?? '') === 'paid') {
                return 'paid';
            }
            if (($txn['status'] ?? '') !== 'pending') {
                return 'invalid';
            }
            $settings = sandbox_settings($txn['user_id'], 'paypal');
            if ($settings === null) {
                throw new RuntimeException('PayPal credentials missing');
            }
            $token = SandboxProviders::paypalToken($settings['client_id'], $settings['secret']);
            $capture = SandboxProviders::paypalCapture($token, $txn['provider_order_id'], 'bp_capture_' . $id);
            if (!SandboxProviders::paypalPaid($capture, $txn)) {
                return 'pending';
            }
            $txn['status'] = 'paid';
            $txn['paid_at'] = gmdate('c');
            $store->write('transactions', $id, $txn);
            return 'paid';
        });
        if ($state === 'paid' && is_string($txn['success_url'] ?? null) && $txn['success_url'] !== '') {
            redirect($txn['success_url']);
        }
        render('PayPal Sandbox', '<section class="pay-card"><h1>' . ($state === 'paid' ? 'Testno plaćanje potvrđeno' : 'Potvrda plaćanja je u tijeku') . '</h1>' . message('Status je dobiven od PayPal Sandbox API-ja, a ne iz URL-a.', 'info') . '</section>');
    } catch (Throwable $exception) {
        error_log('Brendigo Pay PayPal sandbox capture: ' . get_class($exception));
        http_response_code(503);
        render('Plaćanje nije potvrđeno', message('Trenutačno nije moguće potvrditi PayPal Sandbox plaćanje. Provjerite status u nadzornoj ploči.'));
    }
}

// Verify Revolut HMAC, then independently retrieve the order to prevent forged statuses.
if ($method === 'POST' && preg_match('#^/webhooks/revolut/([a-f0-9]{32})$#D', $path, $m)) {
    $settings = sandbox_settings($m[1], 'revolut');
    if ($settings === null) {
        http_response_code(404);
        exit;
    }
    $payload = file_get_contents('php://input', false, null, 0, 1000001) ?: '';
    if (strlen($payload) > 1000000 || !SandboxProviders::verifyRevolut($payload, (string) ($_SERVER['HTTP_REVOLUT_REQUEST_TIMESTAMP'] ?? ''), (string) ($_SERVER['HTTP_REVOLUT_SIGNATURE'] ?? ''), $settings['webhook_secret'])) {
        http_response_code(400);
        exit('Invalid Revolut signature');
    }
    $event = json_decode($payload, true);
    if (!is_array($event) || !isset($event['event'], $event['order_id'])) {
        http_response_code(400);
        exit('Invalid event');
    }
    if ($event['event'] !== 'ORDER_COMPLETED') {
        http_response_code(204);
        exit;
    }
    $orderId = (string) $event['order_id'];
    $mapping = $store->read('provider_orders', hash('sha256', 'revolut:' . $orderId));
    if ($mapping === null || ($mapping['user_id'] ?? '') !== $m[1]) {
        http_response_code(503); // Retry if event arrives before checkout persistence.
        exit('Order not available yet');
    }
    try {
        $result = $store->locked('payment_' . $mapping['transaction_id'], static function () use ($store, $mapping, $orderId, $settings): bool {
            $txn = $store->read('transactions', $mapping['transaction_id']);
            if ($txn === null || ($txn['provider'] ?? '') !== 'revolut' || ($txn['provider_order_id'] ?? '') !== $orderId) {
                return false;
            }
            if (($txn['status'] ?? '') === 'paid') {
                return true;
            }
            $order = SandboxProviders::revolutRetrieve($settings['secret'], $orderId);
            if (!SandboxProviders::revolutPaid($order, $txn)) {
                return false;
            }
            $txn['status'] = 'paid';
            $txn['paid_at'] = gmdate('c');
            $store->write('transactions', $txn['id'], $txn);
            return true;
        });
    } catch (Throwable $exception) {
        error_log('Brendigo Pay Revolut sandbox confirmation: ' . get_class($exception));
        http_response_code(503);
        exit('Provider unavailable');
    }
    http_response_code($result ? 204 : 409);
    exit;
}

// PayPal signed webhook, verified remotely with PayPal before reading order status.
if ($method === 'POST' && preg_match('#^/webhooks/paypal/([a-f0-9]{32})$#D', $path, $m)) {
    $settings = sandbox_settings($m[1], 'paypal');
    if ($settings === null || ($settings['webhook_id'] ?? '') === '') {
        http_response_code(404);
        exit;
    }
    $body = file_get_contents('php://input', false, null, 0, 1000001) ?: '';
    $event = strlen($body) <= 1000000 ? json_decode($body, true) : null;
    if (!is_array($event) || empty($event['id'])) {
        http_response_code(400);
        exit('Invalid event');
    }
    $headers = [
        'transmission_id' => (string) ($_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ?? ''),
        'transmission_time' => (string) ($_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] ?? ''),
        'cert_url' => (string) ($_SERVER['HTTP_PAYPAL_CERT_URL'] ?? ''),
        'auth_algo' => (string) ($_SERVER['HTTP_PAYPAL_AUTH_ALGO'] ?? ''),
        'transmission_sig' => (string) ($_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ?? ''),
    ];
    try {
        $token = SandboxProviders::paypalToken($settings['client_id'], $settings['secret']);
        if (!SandboxProviders::paypalVerifyWebhook($token, $settings['webhook_id'], $headers, $event)) {
            http_response_code(400);
            exit('Invalid PayPal signature');
        }
        if (($event['event_type'] ?? '') !== 'PAYMENT.CAPTURE.COMPLETED') {
            http_response_code(204);
            exit;
        }
        $orderId = (string) ($event['resource']['supplementary_data']['related_ids']['order_id'] ?? '');
        $mapping = $orderId !== '' ? $store->read('provider_orders', hash('sha256', 'paypal:' . $orderId)) : null;
        if ($mapping === null || ($mapping['user_id'] ?? '') !== $m[1]) {
            http_response_code(503);
            exit('Order mapping unavailable');
        }
        $confirmed = $store->locked('payment_' . $mapping['transaction_id'], static function () use ($store, $mapping, $orderId, $token): bool {
            $txn = $store->read('transactions', $mapping['transaction_id']);
            if ($txn === null || ($txn['provider'] ?? '') !== 'paypal' || ($txn['provider_order_id'] ?? '') !== $orderId) {
                return false;
            }
            if ($txn['status'] === 'paid') {
                return true;
            }
            $order = SandboxProviders::request('https://api.sandbox.paypal.com/v2/checkout/orders/' . $orderId, 'GET', ['Authorization: Bearer ' . $token]);
            if (!SandboxProviders::paypalPaid($order, $txn)) {
                return false;
            }
            $txn['status'] = 'paid';
            $txn['paid_at'] = gmdate('c');
            $store->write('transactions', $txn['id'], $txn);
            return true;
        });
        http_response_code($confirmed ? 204 : 409);
        exit;
    } catch (Throwable $exception) {
        error_log('Brendigo Pay PayPal webhook verification: ' . get_class($exception));
        http_response_code(503);
        exit('Sandbox provider unavailable');
    }
}

// Stripe webhook: Stripe is the only authority allowed to mark a transaction paid.
if ($method === 'POST' && preg_match('#^/webhooks/stripe/([a-f0-9]{32})$#D', $path, $m)) {
    $settings = direct_stripe_settings($m[1]);
    if ($settings === null) {
        http_response_code(404);
        exit;
    }
    $payload = file_get_contents('php://input', false, null, 0, 1000001) ?: '';
    if (strlen($payload) > 1000000 || !verify_stripe_signature($payload, (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''), $settings['webhook_secret'])) {
        http_response_code(400);
        exit('Invalid signature');
    }
    $event = json_decode($payload, true);
    if (!is_array($event) || !isset($event['id'], $event['type'])) {
        http_response_code(400);
        exit('Invalid payload');
    }
    $session = $event['data']['object'] ?? [];
    if (in_array($event['type'], ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
        $txnId = $session['metadata']['bp_transaction'] ?? '';
        if (is_string($txnId) && preg_match('/^[a-f0-9]{32,64}$/D', $txnId)) {
            $result = $store->locked('payment_' . $txnId, static function () use ($store, $txnId, $m, $session): string {
                $txn = $store->read('transactions', $txnId);
                if ($txn === null || $txn['user_id'] !== $m[1] || ($txn['provider'] ?? 'stripe') !== 'stripe'
                    || !empty($txn['stripe_connect_account_id'])) {
                    return 'unknown';
                }
                if (empty($txn['session_id'])) {
                    return 'retry';
                }
                if ($txn['session_id'] !== ($session['id'] ?? '')
                    || $txn['amount_minor'] !== ($session['amount_total'] ?? null)
                    || strtolower((string) ($session['currency'] ?? '')) !== 'eur'
                    || ($session['payment_status'] ?? '') !== 'paid') {
                    return 'mismatch';
                }
                if ($txn['status'] !== 'paid') {
                    $txn['status'] = 'paid';
                    $txn['paid_at'] = gmdate('c');
                    $store->write('transactions', $txnId, $txn);
                }
                return 'ok';
            });
            if ($result === 'retry') {
                http_response_code(503); // Ask Stripe to retry a webhook racing checkout creation.
                exit('Try again');
            }
        }
    }
    http_response_code(200);
    exit('ok');
}

// Separate merchant-only Android access. Mobile bearer tokens cannot authenticate WP plugin routes.
if ($path === '/api/v1/mobile/login' && $method === 'POST') {
    $input = json_request();
    try {
        $result = $mobileApi->login($input, (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    } catch (RuntimeException $e) {
        fail_api(429, 'Too many login attempts. Try again later.');
    }
    if ($result === null) {
        fail_api(401, 'Invalid credentials');
    }
    json_response($result);
}
if (str_starts_with($path, '/api/v1/mobile/')) {
    $auth = $mobileApi->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
    if ($auth === null) {
        fail_api(401, 'Mobile session invalid or expired');
    }
    [$mobileMerchant, $mobileTokenHash] = $auth;
    if ($path === '/api/v1/mobile/overview' && $method === 'GET') {
        json_response($mobileApi->overview($mobileMerchant));
    }
    if ($path === '/api/v1/mobile/logout' && $method === 'POST') {
        $mobileApi->logout($mobileTokenHash);
        json_response(['ok' => true]);
    }
    if ($path === '/api/v1/mobile/links' && $method === 'POST') {
        try {
            $created = $mobileApi->createLink($mobileMerchant, json_request());
            audit_event($mobileMerchant['id'], 'payment_link_created', [
                'link_id' => $created['id'], 'via' => 'mobile',
            ]);
            json_response($created, 201);
        } catch (RuntimeException $e) {
            fail_api(422, 'Invalid link data, inactive paid plan or website license');
        }
    }
    fail_api(404, 'Mobile endpoint not found');
}

// Read-only discovery endpoint, not a payment confirmation.
if ($path === '/api/v1/health' && $method === 'GET') {
    json_response(['service' => 'Brendigo Pay', 'state' => 'starter-test']);
}

// Origin-bound license exchange: WordPress sends a one-time key and persistent installation ID.
if ($path === '/api/v1/license/activate' && $method === 'POST') {
    $input = json_request();
    $ip = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $result = $store->locked('license_rate_' . $ip, static function () use ($store, $ip): bool {
        $rate = $store->read('rates', $ip) ?? ['start' => time(), 'count' => 0];
        if (time() - (int) $rate['start'] > 900) { $rate = ['start' => time(), 'count' => 0]; }
        if ((int) $rate['count'] >= 30) { return false; }
        ++$rate['count'];
        $store->write('rates', $ip, $rate);
        return true;
    });
    if (!$result) { fail_api(429, 'Too many activation attempts'); }
    try {
        $activation = $licenses->activate(
            (string) ($input['license_key'] ?? ''),
            (string) ($input['site_url'] ?? ''),
            (string) ($input['installation_id'] ?? '')
        );
        $site = $store->read('sites', $activation['site_id']);
        audit_event($site['user_id'], 'site_license_activated', ['site_id' => $site['id']]);
        json_response($activation);
    } catch (RuntimeException $e) {
        fail_api(403, 'License activation failed. Check the domain, package and key.');
    }
}
/**
 * Publicly reachable only with a valid domain-scoped API token and an active paid
 * entitlement. These records show installation connectivity, not payment success.
 */
if ($path === '/api/v1/sites/status' && $method === 'GET') {
    $token = authenticate_api();
    $site = $store->read('sites', $token['site_id']);
    $plan = $plans->activeOrFail($token['user_id']);
    json_response([
        'site_id' => $token['site_id'],
        'origin' => $token['origin'],
        'status' => $site['status'],
        'plan' => $plan['tier'],
        'expires_at' => $plan['expires_at'],
        'website_limit' => $plans->siteLimit($token['user_id']),
        'websites_used' => $plans->siteCount($token['user_id']),
        'last_seen_at' => $site['last_seen_at'] ?? null,
        'plugin_version' => $site['plugin_version'] ?? null,
        'wordpress_version' => $site['wordpress_version'] ?? null,
        'woocommerce_version' => $site['woocommerce_version'] ?? null,
        'mode' => 'test',
    ]);
}

// An explicit WordPress server-to-server heartbeat; no unauthenticated write path.
if ($path === '/api/v1/sites/sync' && $method === 'POST') {
    $token = authenticate_api();
    $input = json_request();
    foreach (['site_url', 'installation_id', 'plugin_version', 'wordpress_version', 'woocommerce_version'] as $field) {
        if (!isset($input[$field]) || !is_string($input[$field])) {
            fail_api(422, 'Invalid installation status');
        }
    }
    $origin = $licenses->origin($input['site_url']);
    $installationId = $input['installation_id'];
    if ($origin === null || !hash_equals($token['origin'], $origin)
        || !preg_match('/^[a-f0-9]{32}$/D', $installationId)) {
        fail_api(403, 'Installation or origin mismatch');
    }
    $versions = [
        'plugin_version' => $input['plugin_version'],
        'wordpress_version' => $input['wordpress_version'],
        'woocommerce_version' => $input['woocommerce_version'],
    ];
    foreach ($versions as $key => $version) {
        if (strlen($version) > 32 || ($version !== '' &&
            !preg_match('/^[0-9]{1,3}(?:\.[0-9]{1,3}){0,3}(?:[-+][a-zA-Z0-9.-]{1,20})?$/D', $version))) {
            fail_api(422, 'Invalid ' . $key);
        }
    }
    if ($versions['plugin_version'] === '' || $versions['wordpress_version'] === '') {
        fail_api(422, 'Missing installed version');
    }
    $status = $store->locked('merchant_sites_' . $token['user_id'], static function () use ($store, $token, $installationId, $versions): ?array {
        $site = $store->read('sites', $token['site_id']);
        if ($site === null || ($site['status'] ?? '') !== 'active'
            || ($site['user_id'] ?? '') !== $token['user_id']
            || ($site['origin'] ?? '') !== $token['origin']) {
            return null;
        }
        $license = $store->read('licenses', (string) $site['license_hash']);
        $authorization = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        $apiKey = str_starts_with($authorization, 'Bearer ') ? substr($authorization, 7) : '';
        if ($license === null || ($license['revoked'] ?? true) === true
            || !hash_equals((string) ($license['installation_hash'] ?? ''), hash('sha256', $installationId))
            || !hash_equals((string) ($license['active_token_hash'] ?? ''), hash('sha256', $apiKey))) {
            return null;
        }
        $site['last_seen_at'] = gmdate('c');
        foreach ($versions as $field => $value) {
            $site[$field] = $value === '' ? null : $value;
        }
        $store->write('sites', $token['site_id'], $site);
        return $site;
    });
    if ($status === null) { fail_api(403, 'Installation credential revoked'); }
    $plan = $plans->activeOrFail($token['user_id']);
    json_response([
        'site_id' => $token['site_id'],
        'origin' => $token['origin'],
        'status' => 'active',
        'plan' => $plan['tier'],
        'expires_at' => $plan['expires_at'],
        'website_limit' => $plans->siteLimit($token['user_id']),
        'websites_used' => $plans->siteCount($token['user_id']),
        'last_seen_at' => $status['last_seen_at'],
        'mode' => 'test',
    ]);
}

if ($path === '/api/v1/me' && $method === 'GET') {
    $token = authenticate_api();
    $user = $store->read('users', $token['user_id']);
    if ($user === null) {
        fail_api(401, 'Account unavailable');
    }
    json_response([
        'merchant_name' => $user['name'],
        'origin' => $token['origin'],
        'site_id' => $token['site_id'],
        'plan' => $plans->current($token['user_id'])['tier'],
        'website_limit' => $plans->siteLimit($token['user_id']),
        'mode' => 'test',
        'stripe_configured' => test_settings($user['id']) !== null,
        'providers' => configured_providers($user['id']),
    ]);
}

if (preg_match('#^/api/v1/links/([a-f0-9]{24})$#D', $path, $m) && $method === 'GET') {
    $link = $store->read('links', $m[1]);
    if ($link === null || ($link['active'] ?? true) !== true) {
        fail_api(404, 'Not found');
    }
    $token = authenticate_api();
    if (($link['user_id'] ?? '') !== $token['user_id'] ||
        ($link['site_id'] ?? '') !== $token['site_id']) {
        fail_api(404, 'Not found');
    }
    json_response(['id' => $link['id'], 'title' => $link['title'], 'description' => (string)($link['description'] ?? ''), 'amount_minor' => $link['amount_minor'], 'currency' => 'EUR', 'url' => $base . '/p/' . $link['id']]);
}

// Scoped read-only link picker for WordPress Gutenberg; never returns another merchant's records.
if ($path === '/api/v1/links' && $method === 'GET') {
    $token = authenticate_api();
    $links = array_values(array_filter($store->all('links'), static function (array $link) use ($token): bool {
        return ($link['user_id'] ?? '') === $token['user_id']
            && ($link['site_id'] ?? '') === $token['site_id']
            && ($link['active'] ?? true) === true;
    }));
    usort($links, static fn(array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
    $items = [];
    foreach (array_slice($links, 0, 100) as $link) {
        $items[] = [
            'id' => $link['id'],
            'title' => $link['title'],
            'description' => (string) ($link['description'] ?? ''),
            'amount_minor' => (int) $link['amount_minor'],
            'currency' => 'EUR',
            'url' => $base . '/p/' . $link['id'],
        ];
    }
    json_response(['items' => $items, 'limit' => 100, 'total' => count($links)]);
}

if ($path === '/api/v1/links' && $method === 'POST') {
    $token = authenticate_api();
    $input = json_request();
    $title = trim((string) ($input['title'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $amount = money_to_minor((string) ($input['amount'] ?? ''));
    if (strlen($title) < 2 || strlen($title) > 160 || strlen($description) > 500 || $amount === null) {
        fail_api(422, 'Invalid title or EUR amount');
    }
    $link = ['id' => random_id(12), 'user_id' => $token['user_id'], 'site_id' => $token['site_id'], 'title' => $title, 'description' => $description, 'amount_minor' => $amount, 'currency' => 'EUR', 'active' => true, 'created_at' => gmdate('c')];
    $store->write('links', $link['id'], $link);
    audit_event($token['user_id'], 'payment_link_created', ['link_id' => $link['id'], 'via' => 'api']);
    json_response(['id' => $link['id'], 'url' => $base . '/p/' . $link['id']], 201);
}

if ($path === '/api/v1/checkout' && $method === 'POST') {
    $token = authenticate_api();
    $input = json_request();
    $provider = strtolower(trim((string) ($input['provider'] ?? 'stripe')));
    if (!in_array($provider, ['stripe', 'paypal', 'revolut'], true)) {
        fail_api(422, 'Unsupported sandbox payment provider');
    }
    $amount = money_to_minor((string) ($input['amount'] ?? ''));
    $reference = trim((string) ($input['reference'] ?? ''));
    $title = trim((string) ($input['description'] ?? ''));
    $success = (string) ($input['success_url'] ?? '');
    $cancel = (string) ($input['cancel_url'] ?? '');
    if ($amount === null || strlen($reference) < 1 || strlen($reference) > 100
        || strlen($title) < 2 || strlen($title) > 160
        || !valid_checkout_url($success, $token['origin']) || !valid_checkout_url($cancel, $token['origin'])) {
        fail_api(422, 'Invalid checkout payload or domain');
    }
    try {
        $txn = start_checkout($token['user_id'], $amount, $title, $reference, $success, $cancel, $token['origin'], $provider);
        $txn['site_id'] = $token['site_id'];
        $store->write('transactions', $txn['id'], $txn);
        json_response(['transaction_id' => $txn['id'], 'status' => $txn['status'], 'checkout_url' => $txn['checkout_url'], 'provider' => $provider], 201);
    } catch (Throwable $e) {
        error_log('Brendigo Pay sandbox checkout error (' . $provider . '): ' . get_class($e));
        fail_api(503, 'Sandbox checkout unavailable. Check provider configuration.');
    }
}

if ($method === 'GET' && preg_match('#^/api/v1/payments/([a-f0-9]{32,64})$#D', $path, $m)) {
    $token = authenticate_api();
    $txn = $store->read('transactions', $m[1]);
    if ($txn === null || $txn['user_id'] !== $token['user_id'] ||
        ($txn['site_id'] ?? '') !== $token['site_id'] || !str_starts_with($txn['reference'], 'wp_')) {
        fail_api(404, 'Not found');
    }
    json_response(['id' => $txn['id'], 'status' => $txn['status'], 'amount_minor' => $txn['amount_minor'], 'currency' => $txn['currency'], 'reference' => $txn['reference'], 'provider' => $txn['provider'] ?? 'stripe']);
}

// Public links: display only configured sandbox payment providers.
if ($method === 'GET' && preg_match('#^/p/([a-f0-9]{24})$#D', $path, $m)) {
    $link = $store->read('links', $m[1]);
    if ($link === null || ($link['active'] ?? true) !== true
        || $plans->current((string) ($link['user_id'] ?? '')) === null
        || (!empty($link['site_id']) && (($store->read('sites', $link['site_id'])['status'] ?? '') !== 'active'))) {
        http_response_code(404);
        render('Nije pronađeno', message('Naplatna poveznica nije pronađena ili je isključena.'));
    }
    $available = configured_providers($link['user_id']);
    $notice = isset($_GET['returned']) ? message('Povratak s platne stranice nije dokaz uplate. Status se potvrđuje isključivo poslužiteljskom provjerom.', 'info') : '';
    $merchant = $store->read('users', (string) $link['user_id']);
    $merchantName = (string) ($merchant['name'] ?? 'Trgovac');
    render('Naplatna poveznica', bp_public_payment($link, $available, $base, $notice, $merchantName));
}

if ($method === 'POST' && preg_match('#^/p/([a-f0-9]{24})/checkout$#D', $path, $m)) {
    check_csrf();
    $link = $store->read('links', $m[1]);
    if ($link === null || ($link['active'] ?? true) !== true
        || $plans->current((string) ($link['user_id'] ?? '')) === null
        || (!empty($link['site_id']) && (($store->read('sites', $link['site_id'])['status'] ?? '') !== 'active'))) {
        http_response_code(404);
        exit;
    }
    $provider = strtolower(trim((string) ($_POST['provider'] ?? 'stripe')));
    if (!in_array($provider, ['stripe', 'paypal', 'revolut'], true)) {
        http_response_code(422);
        render('Nepodržan način plaćanja', message('Odaberite podržanog pružatelja.'));
    }
    $available = configured_providers((string) $link['user_id']);
    if (empty($available[$provider])) {
        http_response_code(422);
        render('Nepodržan način plaćanja', message('Odabrani pružatelj nije konfiguriran za ovu naplatnu poveznicu.'));
    }
    try {
        $return = $base . '/p/' . $link['id'] . '?returned=1';
        $txn = start_checkout($link['user_id'], $link['amount_minor'], $link['title'], 'link_' . $link['id'], $return, $base . '/p/' . $link['id'], '', $provider);
        header('Location: ' . $txn['checkout_url'], true, 303);
        exit;
    } catch (Throwable $e) {
        error_log('Brendigo Pay public sandbox checkout error (' . $provider . '): ' . get_class($e));
        http_response_code(503);
        render('Naplata nije dostupna', message('Testna naplata nije dostupna. Trgovac treba provjeriti postavke pružatelja.'));
    }
}

if ($path === '/' && $method === 'GET') {
    render('Pametna naplata za WordPress i WooCommerce', bp_site_home());
}

if ($path === '/pricing' && $method === 'GET') {
    render('Cijene', bp_site_pricing());
}

if ($path === '/plugin' && $method === 'GET') {
    render('WordPress i WooCommerce dodatak', bp_site_plugin());
}

if ($path === '/docs' && $method === 'GET') {
    render('Dokumentacija', bp_site_docs());
}

if (in_array($path, ['/login', '/register'], true) && $method === 'GET') {
    $register = $path === '/register';
    $fields = $register ? '<label>Naziv tvrtke ili trgovca<input name="name" required maxlength="100" autocomplete="organization"></label>' : '';
    if ($register) {
        $chosen = (string) ($_GET['plan'] ?? 'business');
        $chosen = isset(\BrendigoPay\Plans::TIERS[$chosen]) ? $chosen : 'business';
        $fields .= '<label>Paket (svi su plaćeni; prijava nije uplata)<select name="requested_tier" required>'
            . '<option value="start"' . ($chosen === 'start' ? ' selected' : '') . '>Start — 19,90 € / mj — 1 web-stranica</option>'
            . '<option value="business"' . ($chosen === 'business' ? ' selected' : '') . '>Business — 49,90 € / mj — 5 web-stranica</option>'
            . '<option value="pro"' . ($chosen === 'pro' ? ' selected' : '') . '>Pro — 99,90 € / mj — 25 web-stranica</option>'
            . '</select></label><label>Web-stranica (neobvezno)<input name="website" type="url" maxlength="255" placeholder="https://moja-trgovina.hr"></label>';
    }
    $fields .= '<label>E-adresa<input type="email" name="email" required maxlength="254" autocomplete="email"></label><label>Lozinka<input type="password" name="password" minlength="12" required autocomplete="' . ($register ? 'new-password' : 'current-password') . '"></label>';
    render($register ? 'Registracija' : 'Prijava', '<section class="auth card"><span class="eyebrow">BRENDIGO PAY</span><h1>' . ($register ? 'Izradi račun' : 'Dobro došli') . '</h1><form method="post">' . csrf() . $fields . '<button class="btn" type="submit">' . ($register ? 'Registriraj se' : 'Prijavi se') . '</button></form><p class="muted">' . ($register ? 'Već imate račun? <a href="/login">Prijava</a>' : 'Novi korisnik? <a href="/register">Registracija</a>') . '</p></section>');
}

if ($path === '/register' && $method === 'POST') {
    check_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $requestedTier = (string) ($_POST['requested_tier'] ?? '');
    $website = trim((string) ($_POST['website'] ?? ''));
    if (!isset(\BrendigoPay\Plans::TIERS[$requestedTier]) || strlen($website) > 255
        || ($website !== '' && $licenses->origin($website) === null)) {
        render('Registracija', message('Odaberite jedan od tri plaćena paketa i valjanu HTTPS domenu.'));
    }
    if (strlen($name) < 2 || strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 254 || strlen($password) < 12 || strlen($password) > 256) {
        render('Registracija', message('Provjerite naziv, e-adresu i lozinku (najmanje 12 znakova).'));
    }
    $id = $store->locked('registration', static function () use ($store, $name, $email, $password, $requestedTier, $website): ?string {
        $hash = hash('sha256', $email);
        if ($store->read('emails', $hash) !== null) {
            return null;
        }
        $id = random_id();
        $store->write('users', $id, ['id' => $id, 'name' => $name, 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_ARGON2ID), 'requested_tier' => $requestedTier, 'requested_site' => $website, 'created_at' => gmdate('c')]);
        $store->write('emails', $hash, ['user_id' => $id]);
        return $id;
    });
    if ($id === null) {
        render('Registracija', message('E-adresa je već registrirana.'));
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    audit_event($id, 'account_registered');
    redirect('/sites');
}

if ($path === '/login' && $method === 'POST') {
    check_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $rateId = hash('sha256', $ip . ':' . $email);
    // Two independent limits: 8 attempts per account/IP and 30 per IP/15 min.
    // Prevent rotating email addresses to bypass the original per-account limiter.
    $ipRateId = hash('sha256', 'ip:' . $ip);
    $ok = $store->locked('auth_ip_' . $ipRateId, static function () use ($store, $email, $rateId, $ipRateId): ?string {
        $ipRecord = $store->read('rates', $ipRateId) ?? ['count' => 0, 'start' => time()];
        if (time() - (int) ($ipRecord['start'] ?? 0) > 900) {
            $ipRecord = ['count' => 0, 'start' => time()];
        }
        if ((int) $ipRecord['count'] >= 30) {
            return null;
        }
        $authenticated = $store->locked('auth_' . $rateId, static function () use ($store, $email, $rateId): ?string {
            $record = $store->read('rates', $rateId) ?? ['count' => 0, 'start' => time()];
            if (time() - (int) ($record['start'] ?? 0) > 900) {
                $record = ['count' => 0, 'start' => time()];
            }
            if ((int) $record['count'] >= 8) {
                return null;
            }
            $index = $store->read('emails', hash('sha256', $email));
            $user = $index !== null ? $store->read('users', $index['user_id']) : null;
            $valid = $user !== null && password_verify((string) ($_POST['password'] ?? ''), $user['password_hash']);
            if (!$valid) {
                $record['count']++;
                $store->write('rates', $rateId, $record);
                return null;
            }
            $store->write('rates', $rateId, ['count' => 0, 'start' => time()]);
            return $user['id'];
        });
        $store->write('rates', $ipRateId, [
            'count' => $authenticated === null ? (int) $ipRecord['count'] + 1 : 0,
            'start' => $authenticated === null ? (int) $ipRecord['start'] : time(),
        ]);
        return $authenticated;
    });
    if ($ok === null) {
        render('Prijava', message('Neuspješna prijava ili privremeno ograničenje pokušaja.'));
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = $ok;
    audit_event($ok, 'signed_in');
    redirect('/dashboard');
}

if ($path === '/logout' && $method === 'POST') {
    check_csrf();
    $_SESSION = [];
    session_destroy();
    redirect('/');
}

if ($path === '/dashboard' && $method === 'GET') {
    $user = login_required();
    $links = array_values(array_filter($store->all('links'), static fn (array $item) => $item['user_id'] === $user['id']));
    usort($links, static fn (array $a, array $b) => strcmp($b['created_at'], $a['created_at']));
    $transactions = array_values(array_filter($store->all('transactions'), static fn (array $item) => $item['user_id'] === $user['id']));
    render('Nadzorna ploča', bp_merchant_ui($user, $links, $transactions, $base, (string) ($_GET['tab'] ?? 'overview')));
}

if ($method === 'GET' && preg_match('#^/dashboard/qr/([a-f0-9]{24})$#D', $path, $m)) {
    $user = login_required();
    $link = $store->read('links', $m[1]);
    if ($link === null || $link['user_id'] !== $user['id']) {
        http_response_code(404);
        render('QR kod nije pronađen', message('Nepostojeća naplatna poveznica.'));
    }
    $url = $base . '/p/' . $link['id'];
    $stateNote = ($link['active'] ?? true) === true ? '' : message('Ova poveznica je isključena i javno nedostupna.');
    render('QR kod', '<section class="pay-card"><span class="eyebrow">QR KOD ZA NAPLATU</span><h1>' . e($link['title']) . '</h1><p class="price">' . minor_format($link['amount_minor']) . '</p><div class="qr-code" data-qr="' . e($url) . '"></div><p><a href="' . e($url) . '">' . e($url) . '</a></p><button type="button" class="btn" data-qr-download>Preuzmi QR (PNG)</button><p class="muted">QR otvara testnu naplatnu stranicu.</p>' . $stateNote . '</section><script defer src="/assets/qr-bundle.js"></script>');
}

if ($path === '/dashboard/links' && $method === 'POST') {
    $user = login_required();
    check_csrf();
    if ($plans->current($user['id']) === null) {
        http_response_code(403);
        render('Paket nije aktivan', message('Prije korištenja potrebno je aktivirati plaćeni paket. Pogledajte Web-stranice.'));
    }
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $amount = money_to_minor((string) ($_POST['amount'] ?? ''));
    if (strlen($title) < 2 || strlen($title) > 160 || strlen($description) > 500 || $amount === null) {
        render('Pogreška', message('Opis mora imati 2–160 znakova, a iznos mora biti valjan i veći od nule.'));
    }
    $siteIds = array_values(array_map(
        static fn (array $site): string => $site['id'],
        array_filter($store->all('sites'), static fn (array $site): bool =>
            ($site['user_id'] ?? '') === $user['id'] && ($site['status'] ?? '') === 'active')
    ));
    $selectedSite = (string) ($_POST['site_id'] ?? '');
    if ($selectedSite === '' && count($siteIds) === 1) { $selectedSite = $siteIds[0]; }
    if (!in_array($selectedSite, $siteIds, true)) {
        http_response_code(422);
        render('Nedostaje web-stranica', message('Najprije povežite WordPress domenu pa odaberite kojoj stranici pripada poveznica.'));
    }
    $link = ['id' => random_id(12), 'user_id' => $user['id'], 'site_id' => $selectedSite, 'title' => $title, 'description' => $description, 'amount_minor' => $amount, 'currency' => 'EUR', 'active' => true, 'created_at' => gmdate('c')];
    $store->write('links', $link['id'], $link);
    audit_event($user['id'], 'payment_link_created', ['link_id' => $link['id'], 'via' => 'dashboard']);
    redirect('/dashboard');
}

if ($method === 'POST' && preg_match('#^/dashboard/links/([a-f0-9]{24})/toggle$#D', $path, $m)) {
    $user = login_required();
    check_csrf();
    $state = $store->locked('link_' . $m[1], static function () use ($store, $m, $user): ?bool {
        $link = $store->read('links', $m[1]);
        if ($link === null || $link['user_id'] !== $user['id']) {
            return null;
        }
        $link['active'] = !(($link['active'] ?? true) === true);
        $link['updated_at'] = gmdate('c');
        $store->write('links', $m[1], $link);
        return $link['active'];
    });
    if ($state === null) {
        http_response_code(404);
        render('Pogreška', message('Poveznica ne postoji.'));
    }
    audit_event($user['id'], $state ? 'payment_link_enabled' : 'payment_link_disabled', ['link_id' => $m[1]]);
    redirect('/dashboard');
}

if ($path === '/dashboard/transactions.csv' && $method === 'GET') {
    $user = login_required();
    $transactions = array_filter($store->all('transactions'), static fn (array $item): bool => $item['user_id'] === $user['id']);
    usort($transactions, static fn (array $a, array $b): int => strcmp($b['created_at'], $a['created_at']));
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="brendigo-pay-transactions.csv"');
    header('Cache-Control: private, no-store');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Transaction ID', 'Created UTC', 'Description', 'Amount EUR', 'Status', 'Reference'], ',', '"', '');
    foreach ($transactions as $transaction) {
        fputcsv($output, [
            $transaction['id'],
            $transaction['created_at'],
            spreadsheet_cell((string) $transaction['description']),
            number_format($transaction['amount_minor'] / 100, 2, '.', ''),
            $transaction['status'],
            spreadsheet_cell((string) $transaction['reference']),
        ], ',', '"', '');
    }
    fclose($output);
    exit;
}

if ($path === '/dashboard/audit' && $method === 'GET') {
    $user = login_required();
    $entries = array_values(array_filter($store->all('audit'), static fn (array $item): bool => $item['user_id'] === $user['id']));
    usort($entries, static fn (array $a, array $b): int => strcmp($b['created_at'], $a['created_at']));
    $rows = '';
    foreach (array_slice($entries, 0, 100) as $entry) {
        $rows .= '<tr><td>' . e($entry['created_at']) . '</td><td>' . e($entry['action']) . '</td><td><code>' . e(json_encode($entry['details'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) . '</code></td></tr>';
    }
    render('Aktivnosti', '<div class="page-heading"><h1>Aktivnosti računa</h1><p><a href="/dashboard">Natrag na nadzornu ploču</a></p></div><section class="card"><div class="table-wrap"><table><thead><tr><th>Vrijeme (UTC)</th><th>Radnja</th><th>Detalji</th></tr></thead><tbody>' . ($rows ?: '<tr><td colspan="3">Nema aktivnosti.</td></tr>') . '</tbody></table></div></section>');
}


/** A paid subscription is provisioned only after manual operator verification. */
if ($path === '/sites' && $method === 'GET') {
    $user = login_required();
    $activePlan = $plans->current($user['id']);
    $requested = $user['requested_tier'] ?? 'business';
    $tier = $activePlan['tier'] ?? null;
    $limit = $tier !== null ? $plans->siteLimit($user['id']) : 0;
    $sites = array_values(array_filter($store->all('sites'), static fn (array $s): bool =>
        ($s['user_id'] ?? '') === $user['id'] && ($s['status'] ?? '') !== 'deleted'));
    $rows = '';
    foreach ($sites as $site) {
        $rows .= '<tr><td><strong>' . e($site['origin']) . '</strong></td><td>' .
            ($site['status'] === 'active' ? '<span class="pill on">Povezano (TEST)</span>' : '<span class="pill off">Čeka aktivaciju</span>') .
            '</td><td>' . e((string) ($site['activated_at'] ?? '—')) . '</td><td>' .
            e((string) ($site['last_seen_at'] ?? 'Nije sinkronizirano')) . '</td><td><form class="inline-form" method="post" action="/sites/' . e($site['id']) . '/rotate">' .
            csrf() . '<button type="submit" class="btn secondary small">Obnovi licenciju</button></form><form class="inline-form" method="post" action="/sites/' . e($site['id']) . '/remove">' .
            csrf() . '<button type="submit" class="btn secondary small">Ukloni</button></form></td></tr>';
    }
    $messageText = $_SESSION['site_notice'] ?? null;
    unset($_SESSION['site_notice']);
    $once = $_SESSION['new_license'] ?? null;
    unset($_SESSION['new_license']);
    $secretBox = is_array($once) && isset($once['license'], $once['origin'])
        ? '<div class="message info bp-license-once" role="status"><b>Nova licencija za ' . e($once['origin']) .
        ' — prikazuje se samo jednom:</b><pre>' . e($once['license']) . '</pre><p>Kopirajte ključ u WordPress → Brendigo Pay → Aktivacija licencije.</p></div>' : '';
    $planLabel = $tier !== null ? \BrendigoPay\Plans::TIERS[$tier]['label'] : 'Nije aktiviran';
    $intro = '<div class="page-heading"><span class="eyebrow">WEB-STRANICE • LICENCIJE • PAID PLANS</span><h1>Moje web-stranice</h1><p>Za svaku instalaciju potreban je aktivan plaćeni paket i licencija vezana uz domenu. Testna naplata; bez live transakcija.</p></div>'
        . '<div class="bp-license-summary"><article class="bp-surface"><small>Aktivni paket</small><strong>' . e($planLabel) . '</strong><span>' .
            ($activePlan ? 'Vrijedi do ' . e($activePlan['expires_at']) : 'Zatraženi paket: ' . e(\BrendigoPay\Plans::TIERS[$requested]['label'] ?? 'Business') . ' — čeka provjeru uplate') .
        '</span></article><article class="bp-surface"><small>Iskorištene domene</small><strong>' . count($sites) . ' / ' . $limit . '</strong><span>Start: 1 • Business: 5 • Pro: 25</span></article></div>';
    $form = $activePlan
        ? '<section class="bp-surface bp-site-registration"><div><h2>Registriraj novu web-stranicu</h2><p>Unesite HTTPS domenu na kojoj je instaliran WordPress dodatak. Domena se može registrirati samo jedanput.</p></div><form method="post" action="/sites" class="bp-site-form">' . csrf() .
        '<label>URL WordPress stranice<input type="url" name="origin" required maxlength="255" placeholder="https://moja-trgovina.hr" autocomplete="url"></label>' .
        '<button type="submit" class="btn" ' . (count($sites) >= $limit ? 'disabled' : '') . '>Kreiraj licenciju</button></form></section>'
        : '<section class="bp-surface"><h2>Aktivacija paketa je na čekanju</h2><p>Registracija računa ne pokreće pretplatu niti daje besplatne licence. Administrator mora provjeriti stvarnu uplatu i potvrditi jedan od tri paketa prije povezivanja WordPressa.</p><a href="/pricing" class="btn secondary">Pogledaj pakete</a></section>';
    render('Web-stranice i licence', $intro . ($messageText ? message($messageText) : '') . $secretBox . $form .
        '<section class="bp-surface bp-data-card"><div class="bp-section-top"><h2>Registrirane domene</h2><small>' . count($sites) . ' web-stranica</small></div><div class="table-wrap"><table><thead><tr><th>Domena</th><th>Status</th><th>Aktivacija (UTC)</th><th>Posljednji kontakt (UTC)</th><th>Upravljanje</th></tr></thead><tbody>' .
        ($rows ?: '<tr><td colspan="5">Još nema registriranih stranica.</td></tr>') . '</tbody></table></div></section>');
}

if ($path === '/sites' && $method === 'POST') {
    $user = login_required();
    check_csrf();
    try {
        $created = $licenses->issue($user['id'], (string) ($_POST['origin'] ?? ''));
        $_SESSION['new_license'] = ['license' => $created['license'], 'origin' => $created['site']['origin']];
        audit_event($user['id'], 'site_registered', ['site_id' => $created['site']['id']]);
    } catch (RuntimeException $e) {
        $_SESSION['site_notice'] = 'Nije moguće dodati domenu: paket je neaktivan, limit je iskorišten ili domena već postoji.';
    }
    redirect('/sites');
}
if ($method === 'POST' && preg_match('#^/sites/([a-f0-9]{24})/(rotate|remove)$#D', $path, $match)) {
    $user = login_required();
    check_csrf();
    try {
        if ($match[2] === 'rotate') {
            $updated = $licenses->rotate($match[1], $user['id']);
            $_SESSION['new_license'] = ['license' => $updated['license'], 'origin' => $updated['site']['origin']];
        } else {
            $licenses->delete($match[1], $user['id']);
        }
        audit_event($user['id'], 'site_' . $match[2], ['site_id' => $match[1]]);
    } catch (RuntimeException $e) {
        $_SESSION['site_notice'] = 'Nije moguće izvršiti traženu radnju za ovu domenu.';
    }
    redirect('/sites');
}
if ($path === '/admin' && $method === 'GET') {
    $operator = login_required();
    if (!in_array($operator['id'], (array) ($config['admin_user_ids'] ?? []), true)) {
        http_response_code(403);
        render('Pristup odbijen', message('Administratorski pristup nije dozvoljen.'));
    }
    $rows = '';
    foreach ($store->all('users') as $merchant) {
        $entitlement = $plans->current($merchant['id']);
        $requested = $merchant['requested_tier'] ?? 'business';
        $rows .= '<tr><td><b>' . e($merchant['name']) . '</b><small> ' . e($merchant['email']) . '</small><br><code>' . e($merchant['id']) . '</code></td>' .
            '<td>' . e((string) $requested) . '</td><td>' . ($entitlement ? e($entitlement['tier']) . ' do ' . e($entitlement['expires_at']) : 'Neaktivan') .
            '</td><td>' . $plans->siteCount($merchant['id']) . '</td>' .
            '<td><form method="post" action="/admin/plans" class="bp-admin-grant">' . csrf() .
            '<input type="hidden" name="merchant_id" value="' . e($merchant['id']) . '">' .
            '<select name="tier" aria-label="Paket za trgovca"><option value="start">Start (1)</option><option value="business">Business (5)</option><option value="pro">Pro (25)</option></select>' .
            '<input type="date" name="expires" required min="' . gmdate('Y-m-d', time() + 86400) . '" aria-label="Datum isteka">' .
            '<label><input type="checkbox" name="payment_verified" value="1" required> Potvrđena uplata</label>' .
            '<button class="btn secondary small" type="submit">Aktiviraj plaćeni plan</button></form>' .
            ($entitlement ? '<form method="post" action="/admin/plans/suspend" class="bp-admin-grant">' . csrf() .
                '<input type="hidden" name="merchant_id" value="' . e($merchant['id']) . '">' .
                '<button class="btn secondary small" type="submit">Suspendiraj paket</button></form>' : '') . '</td></tr>';
    }
    $sites = $store->all('sites');
    $siteRows = '';
    foreach ($sites as $site) {
        if (($site['status'] ?? '') === 'deleted') { continue; }
        $siteRows .= '<tr><td>' . e($site['origin']) . '</td><td><code>' . e($site['user_id']) . '</code></td><td>' .
            e((string) ($site['status'] ?? 'pending')) . '</td><td>' .
            e((string) ($site['last_seen_at'] ?? 'Nije sinkronizirano')) . '</td><td>' .
            e((string) ($site['plugin_version'] ?? '—')) . '</td></tr>';
    }
    $flash = $_SESSION['admin_notice'] ?? null;
    unset($_SESSION['admin_notice']);
    render('Administrator', '<div class="page-heading"><span class="eyebrow">BRENDIGO PAY • ADMIN PANEL</span><h1>Paketi i licence</h1><p>Administratorske ovlasti zadaju se u privatnoj konfiguraciji. Samo potvrđena uplata omogućuje aktivaciju paketa.</p></div>' .
        ($flash ? message($flash) : '') .
        '<section class="bp-surface bp-data-card"><h2>Korisnici i pretplate</h2><div class="table-wrap"><table><thead><tr><th>Korisnik</th><th>Zahtjev</th><th>Aktivni paket</th><th>Domene</th><th>Upravljačka radnja</th></tr></thead><tbody>' .
        ($rows ?: '<tr><td colspan="5">Nema korisnika.</td></tr>') . '</tbody></table></div></section>' .
        '<section class="bp-surface bp-data-card"><h2>Instalacije dodatka</h2><div class="table-wrap"><table><thead><tr><th>Domena</th><th>Trgovac</th><th>Status</th><th>Posljednji kontakt (UTC)</th><th>Verzija dodatka</th></tr></thead><tbody>' .
        ($siteRows ?: '<tr><td colspan="5">Nema instalacija.</td></tr>') . '</tbody></table></div></section>');
}
if ($path === '/admin/plans' && $method === 'POST') {
    $operator = login_required();
    if (!in_array($operator['id'], (array) ($config['admin_user_ids'] ?? []), true)) {
        http_response_code(403);
        render('Pristup odbijen', message('Administratorska radnja nije dozvoljena.'));
    }
    check_csrf();
    if (($_POST['payment_verified'] ?? '') !== '1') {
        http_response_code(422);
        render('Pogreška', message('Najprije nezavisno provjerite naplatu.'));
    }
    $merchantId = (string) ($_POST['merchant_id'] ?? '');
    $tier = (string) ($_POST['tier'] ?? '');
    $expiry = (string) ($_POST['expires'] ?? '');
    try {
        if (!preg_match('/^[a-f0-9]{32}$/D', $merchantId)) {
            throw new RuntimeException('Invalid merchant');
        }
        $plans->grant($merchantId, $tier, $expiry . ' 23:59:59 UTC', $operator['id']);
        audit_event($operator['id'], 'manual_paid_plan_granted', [
            'merchant_id' => $merchantId, 'tier' => $tier, 'expires' => $expiry,
        ]);
        $_SESSION['admin_notice'] = 'Plaćeni paket aktiviran nakon administratorske potvrde.';
    } catch (RuntimeException $e) {
        $_SESSION['admin_notice'] = 'Aktivacija nije uspjela. Provjerite korisnika, datum i maksimalan broj domena.';
    }
    redirect('/admin');
}

if ($path === '/admin/plans/suspend' && $method === 'POST') {
    $operator = login_required();
    if (!in_array($operator['id'], (array) ($config['admin_user_ids'] ?? []), true)) {
        http_response_code(403);
        render('Pristup odbijen', message('Administratorska radnja nije dozvoljena.'));
    }
    check_csrf();
    $merchantId = (string) ($_POST['merchant_id'] ?? '');
    try {
        if (!preg_match('/^[a-f0-9]{32}$/D', $merchantId)) {
            throw new RuntimeException('Invalid merchant');
        }
        $plans->suspend($merchantId, $operator['id']);
        audit_event($operator['id'], 'paid_plan_suspended', ['merchant_id' => $merchantId]);
        $_SESSION['admin_notice'] = 'Paket suspendiran. Pristup licenciranim API funkcijama odmah je blokiran.';
    } catch (RuntimeException $e) {
        $_SESSION['admin_notice'] = 'Suspenzija nije uspjela.';
    }
    redirect('/admin');
}

if ($path === '/settings' && $method === 'GET') {
    $user = login_required();
    $hasStripe = $store->read('stripe', $user['id']) !== null;
    $tokens = array_values(array_filter($store->allWithIds('tokens'), static fn (array $item) => $item['record']['user_id'] === $user['id']));
    $items = '';
    foreach ($tokens as $item) {
        $token = $item['record'];
        $revoked = ($token['revoked'] ?? false) === true;
        $items .= '<li><code>' . e($token['origin']) . '</code> — ' . e($token['created_at'])
            . ($revoked ? ' <span class="pill off">Opozvan</span>'
                : ' <span class="pill on">Aktivan</span><form method="post" class="inline-form" action="/settings/tokens/' . e($item['id']) . '/revoke">'
                . csrf() . '<button class="btn secondary small" type="submit">Opozovi ključ</button></form>') . '</li>';
    }
    $once = $_SESSION['new_api_key'] ?? null;
    unset($_SESSION['new_api_key']);
    $keyBox = is_string($once) ? '<div class="message info"><strong>API ključ prikazan je samo jednom:</strong><pre>' . e($once) . '</pre>Odmah ga spremite u WordPress dodatak.</div>' : '';
    render('Postavke', '<div class="page-heading"><span class="eyebrow">POSTAVKE</span><h1>Povezivanje i integracije</h1></div>' . message('Podržani su samo testni/sandbox načini rada za Stripe, PayPal i Revolut. Ne unosite produkcijske ključeve.', 'info') . '<section class="card stack"><h2>Stripe testna integracija</h2><p>Status: <strong>' . ($hasStripe ? 'Konfigurirana' : 'Nije konfigurirana') . '</strong></p><p>Stripe Dashboard → Developers → API keys: kopirajte tajni <code>sk_test_...</code> ključ. U Stripe Webhooks dodajte endpoint <code>' . e($base . '/webhooks/stripe/' . $user['id']) . '</code> i događaj <code>checkout.session.completed</code>.</p><form method="post" action="/settings/stripe">' . csrf() . '<label>Stripe tajni TEST ključ<input name="secret" type="password" required autocomplete="off" placeholder="sk_test_..."></label><label>Stripe Webhook signing secret<input name="webhook_secret" type="password" required autocomplete="off" placeholder="whsec_..."></label><button class="btn" type="submit">Spremi šifrirane testne ključeve</button></form></section>' . provider_form_sections($base, $user) . '<section class="card stack"><h2>Web-stranice i licence</h2><p>Svaki plaćeni paket ima ograničen broj domena (Start: 1, Business: 5, Pro: 25). Dodajte web-stranicu na panelu i aktivirajte licenciju u WordPressu.</p><p><a class="btn" href="/sites">Web-stranice i licence →</a></p>' . $keyBox . '<p>API pristup se generira samo tijekom aktivacije licencije iz WordPress dodatka.</p><h3>Registrirane domene</h3><ul>' . ($items ?: '<li>Nema registriranih domena.</li>') . '</ul></section>');
}

if ($method === 'POST' && preg_match('#^/settings/tokens/([a-f0-9]{64})/revoke$#D', $path, $m)) {
    $user = login_required();
    check_csrf();
    $changed = $store->locked('token_' . $m[1], static function () use ($store, $user, $m): bool {
        $token = $store->read('tokens', $m[1]);
        if ($token === null || $token['user_id'] !== $user['id'] || ($token['revoked'] ?? false) === true) {
            return false;
        }
        $token['revoked'] = true;
        $token['revoked_at'] = gmdate('c');
        $store->write('tokens', $m[1], $token);
        return true;
    });
    if (!$changed) {
        http_response_code(404);
        render('Pogreška', message('Ključ ne postoji ili je već opozvan.'));
    }
    audit_event($user['id'], 'api_key_revoked', ['key_hash_prefix' => substr($m[1], 0, 12)]);
    redirect('/settings');
}

if ($path === '/settings/stripe' && $method === 'POST') {
    $user = login_required();
    check_csrf();
    $key = trim((string) ($_POST['secret'] ?? ''));
    $secret = trim((string) ($_POST['webhook_secret'] ?? ''));
    if (!preg_match('/^sk_test_[A-Za-z0-9]{10,}$/D', $key) || !preg_match('/^whsec_[A-Za-z0-9]{10,}$/D', $secret)) {
        render('Pogreška', message('Dopušteni su samo Stripe testni ključ (sk_test_) i webhook tajna (whsec_).'));
    }
    $store->write('stripe', $user['id'], ['key' => token_encrypt($key, $config['app_key']), 'webhook_secret' => token_encrypt($secret, $config['app_key']), 'updated_at' => gmdate('c')]);
    audit_event($user['id'], 'stripe_test_credentials_updated');
    redirect('/settings');
}

if ($path === '/settings/paypal' && $method === 'POST') {
    $user = login_required();
    check_csrf();
    $clientId = trim((string) ($_POST['client_id'] ?? ''));
    $secret = trim((string) ($_POST['secret'] ?? ''));
    $webhookId = trim((string) ($_POST['webhook_id'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9_-]{10,256}$/D', $clientId)
        || !preg_match('/^[A-Za-z0-9_-]{10,512}$/D', $secret)
        || ($webhookId !== '' && !preg_match('/^[A-Za-z0-9_-]{4,100}$/D', $webhookId))) {
        http_response_code(422);
        render('Pogreška', message('Neispravan PayPal Sandbox Client ID, Secret ili Webhook ID.'));
    }
    $store->write('paypal', $user['id'], [
        'client_id' => token_encrypt($clientId, $config['app_key']),
        'secret' => token_encrypt($secret, $config['app_key']),
        'webhook_id' => $webhookId,
        'updated_at' => gmdate('c'),
    ]);
    audit_event($user['id'], 'paypal_sandbox_credentials_updated');
    redirect('/settings');
}

if ($path === '/settings/revolut' && $method === 'POST') {
    $user = login_required();
    check_csrf();
    $secret = trim((string) ($_POST['secret'] ?? ''));
    $webhookSecret = trim((string) ($_POST['webhook_secret'] ?? ''));
    if (!preg_match('/^sk_[A-Za-z0-9_-]{10,512}$/D', $secret)
        || !preg_match('/^[A-Za-z0-9_-]{10,256}$/D', $webhookSecret)) {
        http_response_code(422);
        render('Pogreška', message('Neispravan Revolut Merchant Sandbox ključ ili webhook tajna.'));
    }
    $store->write('revolut', $user['id'], [
        'secret' => token_encrypt($secret, $config['app_key']),
        'webhook_secret' => token_encrypt($webhookSecret, $config['app_key']),
        'updated_at' => gmdate('c'),
    ]);
    audit_event($user['id'], 'revolut_sandbox_credentials_updated');
    redirect('/settings');
}

if ($path === '/settings/token' && $method === 'POST') {
    $user = login_required();
    check_csrf();
    $origin = safe_origin((string) ($_POST['origin'] ?? ''), $isLocal);
    if ($origin === null) {
        render('Pogreška', message('Unesite valjanu HTTPS domenu (HTTP dopušten samo za localhost).'));
    }
    http_response_code(410);
    render('Zastarjeli način aktivacije', message('Ručno stvaranje API tokena zamijenjeno je licencijom: otvorite Web-stranice.'));
}

http_response_code(404);
render('Nije pronađeno', message('Tražena stranica ne postoji.'));
