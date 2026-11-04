<?php
declare(strict_types=1);

namespace BrendigoPay;

use RuntimeException;

final class SiteLicenses
{
    public function __construct(
        private readonly JsonStore $store,
        private readonly Plans $plans,
        private readonly bool $allowLocal = false
    ) {}

    public function origin(string $url): ?string
    {
        $url = rtrim(trim($url), '/');
        $origin = safe_origin($url, $this->allowLocal);
        return $origin !== null && $origin === $url ? $origin : null;
    }

    /** Returns the only plaintext copy of the new license key. */
    public function issue(string $merchantId, string $url): array
    {
        $origin = $this->origin($url);
        if ($origin === null) {
            throw new RuntimeException('Invalid HTTPS website origin');
        }
        $originHash = hash('sha256', $origin);
        return $this->store->locked('origin_' . $originHash, function () use ($merchantId, $origin, $originHash): array {
            return $this->store->locked('merchant_sites_' . $merchantId, function () use ($merchantId, $origin, $originHash): array {
                $limit = $this->plans->siteLimit($merchantId);
                if ($this->plans->siteCount($merchantId) >= $limit) {
                    throw new RuntimeException('Your paid plan website limit has been reached');
                }
                if ($this->store->read('site_origins', $originHash) !== null) {
                    throw new RuntimeException('This domain is already registered');
                }
                $siteId = random_id(12);
                $plain = 'bp_lic_' . random_id(32);
                $hash = hash('sha256', $plain);
                $site = [
                    'id' => $siteId, 'user_id' => $merchantId, 'origin' => $origin,
                    'license_hash' => $hash, 'status' => 'pending',
                    'created_at' => gmdate('c'), 'activated_at' => null,
                ];
                $this->store->write('sites', $siteId, $site);
                $this->store->write('licenses', $hash, [
                    'user_id' => $merchantId, 'site_id' => $siteId,
                    'origin' => $origin, 'revoked' => false, 'created_at' => gmdate('c'),
                ]);
                $this->store->write('site_origins', $originHash, [
                    'user_id' => $merchantId, 'site_id' => $siteId,
                ]);
                return ['site' => $site, 'license' => $plain, 'limit' => $limit];
            });
        });
    }

    /** Rotating a license revokes the old WordPress API credential. */
    public function rotate(string $siteId, string $merchantId): array
    {
        return $this->store->locked('merchant_sites_' . $merchantId, function () use ($siteId, $merchantId): array {
            $site = $this->store->read('sites', $siteId);
            if ($site === null || ($site['user_id'] ?? '') !== $merchantId || ($site['status'] ?? '') === 'deleted') {
                throw new RuntimeException('Site not found');
            }
            $this->plans->activeOrFail($merchantId);
            $oldHash = (string) $site['license_hash'];
            return $this->store->locked('license_' . $oldHash, function () use ($site, $oldHash): array {
                $previous = $this->store->read('licenses', $oldHash);
                if ($previous === null || ($previous['site_id'] ?? '') !== $site['id']) {
                    throw new RuntimeException('License record unavailable');
                }
                $this->revokeRecord($oldHash, $previous);
                $plain = 'bp_lic_' . random_id(32);
                $newHash = hash('sha256', $plain);
                $this->store->write('licenses', $newHash, [
                    'site_id' => $site['id'], 'user_id' => $site['user_id'],
                    'origin' => $site['origin'], 'revoked' => false,
                    'created_at' => gmdate('c'),
                ]);
                $site['license_hash'] = $newHash;
                $site['status'] = 'pending';
                $site['activated_at'] = null;
                $this->store->write('sites', $site['id'], $site);
                return ['site' => $site, 'license' => $plain];
            });
        });
    }

    /** Delete a site, revoke credentials and free a package slot. */
    public function delete(string $siteId, string $merchantId): void
    {
        $this->store->locked('merchant_sites_' . $merchantId, function () use ($siteId, $merchantId): void {
            $site = $this->store->read('sites', $siteId);
            if ($site === null || ($site['user_id'] ?? '') !== $merchantId || ($site['status'] ?? '') === 'deleted') {
                throw new RuntimeException('Site not found');
            }
            $hash = (string) $site['license_hash'];
            $this->store->locked('license_' . $hash, function () use ($hash): void {
                $record = $this->store->read('licenses', $hash);
                if ($record !== null) {
                    $this->revokeRecord($hash, $record);
                }
            });
            $site['status'] = 'deleted';
            $site['deleted_at'] = gmdate('c');
            $this->store->write('sites', $siteId, $site);
            $this->store->delete('site_origins', hash('sha256', $site['origin']));
        });
    }

    /** Called server-to-server by WordPress, not by a public browser form. */
    public function activate(string $license, string $siteUrl, string $installationId): array
    {
        $origin = $this->origin($siteUrl);
        if (!preg_match('/^bp_lic_[a-f0-9]{64}$/D', $license)
            || $origin === null || !preg_match('/^[a-f0-9]{32}$/D', $installationId)) {
            throw new RuntimeException('Invalid license activation request');
        }
        $hash = hash('sha256', $license);
        return $this->store->locked('license_' . $hash, function () use ($hash, $origin, $installationId): array {
            $record = $this->store->read('licenses', $hash);
            if ($record === null || ($record['revoked'] ?? false)
                || !hash_equals((string) ($record['origin'] ?? ''), $origin)) {
                throw new RuntimeException('License invalid or domain does not match');
            }
            $this->plans->activeOrFail($record['user_id']);
            $site = $this->store->read('sites', (string) $record['site_id']);
            if ($site === null || ($site['status'] ?? '') === 'deleted'
                || ($site['license_hash'] ?? '') !== $hash || ($site['user_id'] ?? '') !== $record['user_id']) {
                throw new RuntimeException('License revoked');
            }
            $installationHash = hash('sha256', $installationId);
            if (isset($record['installation_hash']) &&
                !hash_equals($record['installation_hash'], $installationHash)) {
                throw new RuntimeException('License already in use on another installation');
            }
            if (isset($record['active_token_hash'])) {
                $old = $this->store->read('tokens', $record['active_token_hash']);
                if ($old !== null) {
                    $old['revoked'] = true;
                    $old['revoked_at'] = gmdate('c');
                    $this->store->write('tokens', $record['active_token_hash'], $old);
                }
            }
            $apiKey = 'bp_test_' . random_id(32);
            $tokenHash = hash('sha256', $apiKey);
            $this->store->write('tokens', $tokenHash, [
                'user_id' => $record['user_id'], 'site_id' => $site['id'],
                'origin' => $origin, 'revoked' => false, 'created_at' => gmdate('c'),
            ]);
            $record['installation_hash'] = $installationHash;
            $record['active_token_hash'] = $tokenHash;
            $record['activated_at'] = gmdate('c');
            $this->store->write('licenses', $hash, $record);
            $site['status'] = 'active';
            $site['activated_at'] = gmdate('c');
            $this->store->write('sites', $site['id'], $site);
            return ['api_key' => $apiKey, 'site_id' => $site['id'],
                'origin' => $origin, 'mode' => 'test'];
        });
    }

    private function revokeRecord(string $hash, array $record): void
    {
        $record['revoked'] = true;
        $record['revoked_at'] = gmdate('c');
        $this->store->write('licenses', $hash, $record);
        if (isset($record['active_token_hash'])) {
            $token = $this->store->read('tokens', $record['active_token_hash']);
            if ($token !== null) {
                $token['revoked'] = true;
                $token['revoked_at'] = gmdate('c');
                $this->store->write('tokens', $record['active_token_hash'], $token);
            }
        }
    }
}
