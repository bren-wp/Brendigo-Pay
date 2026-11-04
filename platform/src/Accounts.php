<?php
declare(strict_types=1);

namespace BrendigoPay;

use InvalidArgumentException;
use RuntimeException;

/**
 * One merchant identity, independent of registration client.
 * Web and Android create identical user/email records. Registration never grants an entitlement.
 */
final class Accounts
{
    public function __construct(private readonly JsonStore $store, private readonly SiteLicenses $licenses) {}

    /**
     * @return string|null Merchant ID; null when the email already exists.
     * @throws InvalidArgumentException Invalid registration data
     * @throws RuntimeException IP registration rate exceeded
     */
    public function register(array $input, string $ip): ?string
    {
        foreach (['name', 'email', 'password', 'requested_tier'] as $required) {
            if (!is_string($input[$required] ?? null)) {
                throw new InvalidArgumentException('Invalid registration data');
            }
        }
        if (!is_string($input['website'] ?? '')) {
            throw new InvalidArgumentException('Invalid website');
        }
        $name = trim($input['name']);
        $email = strtolower(trim($input['email']));
        $password = $input['password'];
        $tier = $input['requested_tier'];
        $website = trim($input['website'] ?? '');
        if (!isset(Plans::TIERS[$tier]) || strlen($name) < 2 || strlen($name) > 100
            || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254
            || strlen($password) < 12 || strlen($password) > 256
            || strlen($website) > 255 || ($website !== '' && $this->licenses->origin($website) === null)) {
            throw new InvalidArgumentException('Invalid name, email, password, paid plan or HTTPS website');
        }

        $ipHash = hash('sha256', 'registration:' . $ip);
        return $this->store->locked('registration_rate_' . $ipHash, function () use (
            $ipHash, $name, $email, $password, $tier, $website
        ): ?string {
            $rate = $this->store->read('rates', $ipHash) ?? ['count' => 0, 'start' => time()];
            if (time() - (int) $rate['start'] >= 900) {
                $rate = ['count' => 0, 'start' => time()];
            }
            if ((int) $rate['count'] >= 6) {
                throw new RuntimeException('Registration rate limit exceeded');
            }
            ++$rate['count'];
            $this->store->write('rates', $ipHash, $rate);
            return $this->store->locked('registration', function () use (
                $name, $email, $password, $tier, $website
            ): ?string {
                $emailHash = hash('sha256', $email);
                if ($this->store->read('emails', $emailHash) !== null) {
                    return null;
                }
                $id = random_id();
                $this->store->write('users', $id, [
                    'id' => $id,
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
                    'requested_tier' => $tier,
                    'requested_site' => $website,
                    'created_at' => gmdate('c'),
                ]);
                $this->store->write('emails', $emailHash, ['user_id' => $id]);
                return $id;
            });
        });
    }
}
