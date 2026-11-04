<?php
declare(strict_types=1);

namespace BrendigoPay;

use DateTimeImmutable;
use RuntimeException;

/**
 * All plans are paid. A registration or a selected plan is NOT proof of payment.
 * Only an operator can grant an entitlement after independently verifying billing.
 */
final class Plans
{
    public const TIERS = [
        'start' => ['label' => 'Start', 'monthly_minor' => 1990, 'sites' => 1],
        'business' => ['label' => 'Business', 'monthly_minor' => 4990, 'sites' => 5],
        'pro' => ['label' => 'Pro', 'monthly_minor' => 9990, 'sites' => 25],
    ];

    public function __construct(private readonly JsonStore $store) {}

    public function current(string $userId): ?array
    {
        $record = $this->store->read('entitlements', $userId);
        if ($record === null || !isset(self::TIERS[$record['tier'] ?? ''])
            || ($record['status'] ?? '') !== 'active'
            || !isset($record['expires_at'])
            || strtotime((string) $record['expires_at']) === false
            || strtotime((string) $record['expires_at']) <= time()) {
            return null;
        }
        return $record;
    }

    public function activeOrFail(string $userId): array
    {
        return $this->current($userId) ?? throw new RuntimeException('Paid plan inactive or expired');
    }

    public function siteLimit(string $userId): int
    {
        $tier = $this->activeOrFail($userId)['tier'];
        return self::TIERS[$tier]['sites'];
    }

    public function siteCount(string $userId): int
    {
        return count(array_filter(
            $this->store->all('sites'),
            static fn (array $site): bool => ($site['user_id'] ?? '') === $userId && ($site['status'] ?? '') !== 'deleted'
        ));
    }

    /** Operator-only entry point. Caller must authenticate admin and verify payment first. */
    public function grant(string $userId, string $tier, string $expiresAt, string $operatorId): array
    {
        if (!isset(self::TIERS[$tier])) {
            throw new RuntimeException('Unknown paid tier');
        }
        try {
            $expires = new DateTimeImmutable($expiresAt);
        } catch (\Throwable $e) {
            throw new RuntimeException('Invalid expiry', 0, $e);
        }
        if ($expires->getTimestamp() <= time() || $expires->getTimestamp() > time() + 370 * 86400) {
            throw new RuntimeException('Expiry must be in next 370 days');
        }
        return $this->store->locked('merchant_sites_' . $userId, function () use ($userId, $tier, $expires, $operatorId): array {
            if ($this->store->read('users', $userId) === null) {
                throw new RuntimeException('Merchant not found');
            }
            if ($this->siteCount($userId) > self::TIERS[$tier]['sites']) {
                throw new RuntimeException('Existing website count exceeds selected plan');
            }
            $record = [
                'user_id' => $userId, 'tier' => $tier, 'status' => 'active',
                'expires_at' => $expires->format(DATE_ATOM),
                'confirmed_by' => $operatorId, 'confirmed_at' => gmdate('c'),
                'billing_mode' => 'manual_verified',
            ];
            $this->store->write('entitlements', $userId, $record);
            return $record;
        });
    }

    public function suspend(string $userId, string $operatorId): void
    {
        $this->store->locked('merchant_sites_' . $userId, function () use ($userId, $operatorId): void {
            $record = $this->store->read('entitlements', $userId);
            if ($record === null) {
                throw new RuntimeException('Entitlement not found');
            }
            $record['status'] = 'suspended';
            $record['suspended_by'] = $operatorId;
            $record['suspended_at'] = gmdate('c');
            $this->store->write('entitlements', $userId, $record);
        });
    }
}
