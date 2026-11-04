<?php
declare(strict_types=1);

namespace BrendigoPay;

use RuntimeException;

/**
 * Separate, merchant-scoped mobile authentication.
 * Mobile tokens cannot be used to activate plugins or access operator endpoints.
 * JSON records and subscription checks are shared with the website/WP plugin.
 */
final class MobileApi
{
    public function __construct(
        private readonly JsonStore $store,
        private readonly Plans $plans,
        private readonly string $baseUrl
    ) {}

    /** One use-case: email/password login with the same IP/account lockout buckets as web login. */
    public function login(array $input, string $remoteIp): ?array
    {
        if (!is_string($input['email'] ?? null) || !is_string($input['password'] ?? null)) {
            return null;
        }
        $email = strtolower(trim($input['email']));
        $password = $input['password'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254
            || strlen($password) < 1 || strlen($password) > 256) {
            return null;
        }
        $rateId = hash('sha256', $remoteIp . ':' . $email);
        $ipRateId = hash('sha256', 'ip:' . $remoteIp);
        $userId = $this->store->locked('auth_ip_' . $ipRateId, function () use ($email, $password, $rateId, $ipRateId): ?string {
            $ipRecord = $this->store->read('rates', $ipRateId) ?? ['count' => 0, 'start' => time()];
            if (time() - (int) ($ipRecord['start'] ?? 0) > 900) {
                $ipRecord = ['count' => 0, 'start' => time()];
            }
            if ((int) $ipRecord['count'] >= 30) {
                throw new RuntimeException('throttled');
            }
            $userId = $this->store->locked('auth_' . $rateId, function () use ($email, $password, $rateId): ?string {
                $rate = $this->store->read('rates', $rateId) ?? ['count' => 0, 'start' => time()];
                if (time() - (int) ($rate['start'] ?? 0) > 900) {
                    $rate = ['count' => 0, 'start' => time()];
                }
                if ((int) $rate['count'] >= 8) {
                    throw new RuntimeException('throttled');
                }
                $index = $this->store->read('emails', hash('sha256', $email));
                $user = $index !== null ? $this->store->read('users', (string) ($index['user_id'] ?? '')) : null;
                $valid = $user !== null && password_verify($password, (string) ($user['password_hash'] ?? ''));
                $this->store->write('rates', $rateId, [
                    'count' => $valid ? 0 : (int) $rate['count'] + 1,
                    'start' => $valid ? time() : (int) $rate['start'],
                ]);
                return $valid ? (string) $user['id'] : null;
            });
            $this->store->write('rates', $ipRateId, [
                'count' => $userId === null ? (int) $ipRecord['count'] + 1 : 0,
                'start' => $userId === null ? (int) $ipRecord['start'] : time(),
            ]);
            return $userId;
        });
        if ($userId === null) {
            return null;
        }
        $secret = 'bp_mobile_' . bin2hex(random_bytes(32));
        $this->store->write('mobile_tokens', hash('sha256', $secret), [
            'user_id' => $userId,
            'expires_at' => time() + 7 * 86400,
            'revoked' => false,
            'created_at' => gmdate('c'),
        ]);
        return ['token' => $secret, 'expires_in' => 7 * 86400, 'token_type' => 'Bearer'];
    }

    /** Returns [merchant, SHA256(token)] or null. Never accepts a WP site credential. */
    public function authenticate(string $authorization): ?array
    {
        if (!preg_match('/^Bearer (bp_mobile_[a-f0-9]{64})$/D', $authorization, $matches)) {
            return null;
        }
        $hash = hash('sha256', $matches[1]);
        $record = $this->store->read('mobile_tokens', $hash);
        if ($record === null || ($record['revoked'] ?? true) !== false
            || (int) ($record['expires_at'] ?? 0) <= time()) {
            return null;
        }
        $merchant = $this->store->read('users', (string) ($record['user_id'] ?? ''));
        return $merchant !== null ? [$merchant, $hash] : null;
    }

    public function logout(string $hash): void
    {
        $this->store->locked('mobile_token_' . $hash, function () use ($hash): void {
            $record = $this->store->read('mobile_tokens', $hash);
            if ($record !== null) {
                $record['revoked'] = true;
                $record['revoked_at'] = gmdate('c');
                $this->store->write('mobile_tokens', $hash, $record);
            }
        });
    }

    /** Strictly user-scoped data; same data source and totals as the web dashboard. */
    public function overview(array $merchant): array
    {
        $id = (string) $merchant['id'];
        $plan = $this->plans->current($id);
        $sites = array_values(array_filter($this->store->all('sites'), static fn (array $s): bool =>
            ($s['user_id'] ?? '') === $id && ($s['status'] ?? '') !== 'deleted'));
        $links = array_values(array_filter($this->store->all('links'), static fn (array $s): bool =>
            ($s['user_id'] ?? '') === $id));
        $transactions = array_values(array_filter($this->store->all('transactions'), static fn (array $t): bool =>
            ($t['user_id'] ?? '') === $id));
        $sort = static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        usort($links, $sort);
        usort($transactions, $sort);
        usort($sites, $sort);
        $paid = array_values(array_filter($transactions, static fn (array $t): bool => ($t['status'] ?? '') === 'paid'));
        $revenue = array_sum(array_map(static fn (array $t): int => (int) ($t['amount_minor'] ?? 0), $paid));
        return [
            'mode' => 'test',
            'merchant' => ['name' => (string) $merchant['name'], 'email' => (string) $merchant['email']],
            'subscription' => [
                'status' => $plan !== null ? 'active' : 'inactive',
                'tier' => $plan['tier'] ?? null,
                'requested_tier' => (string) ($merchant['requested_tier'] ?? 'business'),
                'expires_at' => $plan['expires_at'] ?? null,
                'site_limit' => $plan !== null ? $this->plans->siteLimit($id) : 0,
                'sites_used' => count($sites),
            ],
            'metrics' => [
                'paid_minor' => $revenue,
                'successful_payments' => count($paid),
                'transactions' => count($transactions),
                'active_links' => count(array_filter($links, static fn (array $l): bool => ($l['active'] ?? true) === true)),
            ],
            'sites' => array_map(static fn (array $s): array => [
                'id' => (string) $s['id'],
                'origin' => (string) $s['origin'],
                'status' => (string) ($s['status'] ?? 'pending'),
                'last_seen_at' => $s['last_seen_at'] ?? null,
                'plugin_version' => $s['plugin_version'] ?? null,
            ], $sites),
            'links' => array_map(fn (array $l): array => [
                'id' => (string) $l['id'],
                'site_id' => (string) $l['site_id'],
                'title' => (string) $l['title'],
                'amount_minor' => (int) $l['amount_minor'],
                'active' => ($l['active'] ?? true) === true,
                'url' => $this->baseUrl . '/p/' . $l['id'],
            ], array_slice($links, 0, 50)),
            'transactions' => array_map(static fn (array $t): array => [
                'id' => (string) $t['id'],
                'created_at' => (string) ($t['created_at'] ?? ''),
                'description' => (string) ($t['description'] ?? ''),
                'amount_minor' => (int) ($t['amount_minor'] ?? 0),
                'status' => (string) ($t['status'] ?? 'pending'),
                'provider' => (string) ($t['provider'] ?? ''),
                'reference' => (string) ($t['reference'] ?? ''),
            ], array_slice($transactions, 0, 50)),
        ];
    }

    /** Toggle only merchant-owned links with an active paid plan and a record lock. */
    public function toggleLink(array $merchant, string $linkId): ?bool
    {
        $this->plans->activeOrFail((string) $merchant['id']);
        return $this->store->locked('link_' . $linkId, function () use ($merchant, $linkId): ?bool {
            $link = $this->store->read('links', $linkId);
            if ($link === null || ($link['user_id'] ?? '') !== $merchant['id']) {
                return null;
            }
            $link['active'] = !(($link['active'] ?? true) === true);
            $link['updated_at'] = gmdate('c');
            $this->store->write('links', $linkId, $link);
            return $link['active'];
        });
    }

    /** Mobile link creation follows website rules: active paid plan and activated merchant-owned site. */
    public function createLink(array $merchant, array $data): array
    {
        $this->plans->activeOrFail((string) $merchant['id']);
        foreach (['site_id', 'title', 'amount'] as $key) {
            if (!is_string($data[$key] ?? null)) {
                throw new RuntimeException('Invalid payload fields');
            }
        }
        if (!is_string($data['description'] ?? '')) {
            throw new RuntimeException('Invalid description');
        }
        $siteId = $data['site_id'];
        $title = trim($data['title']);
        $description = trim($data['description'] ?? '');
        $amount = money_to_minor($data['amount']);
        if (!preg_match('/^[a-f0-9]{24}$/D', $siteId)
            || strlen($title) < 2 || strlen($title) > 160
            || strlen($description) > 500 || $amount === null) {
            throw new RuntimeException('Invalid link data');
        }
        $site = $this->store->read('sites', $siteId);
        if ($site === null || ($site['user_id'] ?? '') !== $merchant['id']
            || ($site['status'] ?? '') !== 'active') {
            throw new RuntimeException('Site not licensed');
        }
        $link = [
            'id' => random_id(12), 'user_id' => $merchant['id'], 'site_id' => $siteId,
            'title' => $title, 'description' => $description, 'amount_minor' => $amount,
            'currency' => 'EUR', 'active' => true, 'created_at' => gmdate('c'),
        ];
        $this->store->write('links', $link['id'], $link);
        return ['id' => $link['id'], 'url' => $this->baseUrl . '/p/' . $link['id']];
    }
}
