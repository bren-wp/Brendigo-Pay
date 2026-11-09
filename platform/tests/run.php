<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/JsonStore.php';
require dirname(__DIR__) . '/src/JsonSessionHandler.php';
require dirname(__DIR__) . '/src/Support.php';
require dirname(__DIR__) . '/src/Ui.php';

use BrendigoPay\JsonStore;
use BrendigoPay\JsonSessionHandler;
use function BrendigoPay\money_to_minor;
use function BrendigoPay\payment_link_is_current;
use function BrendigoPay\safe_origin;
use function BrendigoPay\token_encrypt;
use function BrendigoPay\token_decrypt;
use function BrendigoPay\verify_stripe_signature;

function ensure(bool $condition, string $name): void
{
    if (!$condition) {
        throw new RuntimeException('FAILED: ' . $name);
    }
    echo "PASS: {$name}\n";
}

$emptyGraph = bp_test_chart([]);
$paidGraph = bp_test_chart([['id' => 'chart-test', 'status' => 'paid', 'amount_minor' => 5000, 'created_at' => gmdate('c'), 'paid_at' => gmdate('c')]]);
ensure($emptyGraph !== $paidGraph && str_contains($paidGraph, 'Potvrđene sandbox uplate'), '30-day chart uses actual paid transaction values');
$path = sys_get_temp_dir() . '/bp-tests-' . bin2hex(random_bytes(8));
$store = new JsonStore($path);
$store->write('users', 'test', ['id' => 'test', 'name' => 'Brendigo']);
ensure($store->read('users', 'test')['name'] === 'Brendigo', 'JSON atomic write + read');
ensure(count($store->allWithIds('users')) === 1 && $store->allWithIds('users')[0]['id'] === 'test', 'JSON listing exposes validated IDs');
$store->locked('counter', function () use ($store): void {
    $store->write('stats', 'counter', ['total' => 1]);
});
ensure($store->read('stats', 'counter')['total'] === 1, 'lock-protected update');
ensure(money_to_minor('15,99') === 1599 && money_to_minor('0') === null && money_to_minor('1.999') === null, 'EUR amount parser');
$t = 1700000000;
ensure(payment_link_is_current(['active' => true], $t), 'legacy link is valid');
ensure(!payment_link_is_current(['active' => false], $t), 'disabled link rejected');
ensure(payment_link_is_current(['expires_at' => gmdate('c', $t+60)], $t), 'future-expiring link is valid');
ensure(!payment_link_is_current(['expires_at' => gmdate('c', $t)], $t), 'expiry boundary rejected');
ensure(!payment_link_is_current(['expires_at' => 'invalid'], $t), 'bad expiry rejected');
ensure(safe_origin('https://example.com/some/path') === 'https://example.com', 'canonical origin');
ensure(safe_origin('http://example.com') === null, 'reject insecure remote origin');
$key = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
$encrypted = token_encrypt('sk_test_sample', $key);
ensure(token_decrypt($encrypted, $key) === 'sk_test_sample' && !str_contains($encrypted, 'sample'), 'secretbox crypto round-trip');
$body = '{"id":"evt_test"}';
$now = time();
$header = 't=' . $now . ',v1=' . hash_hmac('sha256', $now . '.' . $body, 'whsec_test');
ensure(verify_stripe_signature($body, $header, 'whsec_test'), 'valid Stripe webhook signature');
ensure(!verify_stripe_signature($body . 'x', $header, 'whsec_test'), 'reject tampered webhook');
$session = new JsonSessionHandler($store, $path . '/sessions');
$id = bin2hex(random_bytes(16));
ensure($session->open('', 'bp') && $session->read($id) === '' && $session->write($id, 'key|s:5:"value";'), 'JSON-backed PHP sessions');
$session->close();
ensure($session->read($id) === 'key|s:5:"value";', 'session reload');
$session->destroy($id);
$session->close();
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($files as $file) {
    $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
}
rmdir($path);
echo "All tests passed.\n";
