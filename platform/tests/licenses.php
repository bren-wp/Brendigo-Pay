<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/JsonStore.php';
require dirname(__DIR__) . '/src/Support.php';
require dirname(__DIR__) . '/src/Plans.php';
require dirname(__DIR__) . '/src/SiteLicenses.php';

use BrendigoPay\JsonStore;
use BrendigoPay\Plans;
use BrendigoPay\SiteLicenses;

$folder = sys_get_temp_dir() . '/bp-license-' . bin2hex(random_bytes(8));
$store = new JsonStore($folder);
$plans = new Plans($store);
$licenses = new SiteLicenses($store, $plans, true);
$store->write('users', 'merchant1', ['id' => 'merchant1', 'name' => 'First merchant']);
$store->write('users', 'merchant2', ['id' => 'merchant2', 'name' => 'Second merchant']);
function check_license(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException('FAIL: ' . $message); }
    echo "PASS: {$message}\n";
}
check_license(count(Plans::TIERS) === 3 &&
    !array_key_exists('free', Plans::TIERS) &&
    Plans::TIERS['start']['sites'] === 1 &&
    Plans::TIERS['business']['sites'] === 5 &&
    Plans::TIERS['pro']['sites'] === 25, 'exactly 3 paid tiers with website quotas');
try { $licenses->issue('merchant1', 'https://shop.example'); throw new RuntimeException('Unpaid issuance allowed'); }
catch (RuntimeException $e) { check_license(str_contains($e->getMessage(), 'Paid plan'), 'unpaid merchant cannot issue licenses'); }
try { $plans->grant('merchant1', 'free', '+30 days', 'operator'); throw new RuntimeException('Free tier allowed'); }
catch (RuntimeException $e) { check_license($e->getMessage() === 'Unknown paid tier', 'free tier cannot be granted'); }
$plans->grant('merchant1', 'start', '+30 days', 'operator');
check_license($plans->siteLimit('merchant1') === 1, 'Start is 1 site');
$site = $licenses->issue('merchant1', 'https://shop.example/');
check_license(strlen($site['license']) === 71 && !str_contains(json_encode($store->read('sites', $site['site']['id'])), $site['license']), 'license secret hash-only');
try { $licenses->issue('merchant1', 'https://extra.example'); throw new RuntimeException('Start quota bypass'); }
catch (RuntimeException $e) { check_license(str_contains($e->getMessage(), 'limit'), 'server enforces website quota'); }
try { $licenses->activate($site['license'], 'https://evil.example', str_repeat('a', 32)); throw new RuntimeException('Origin bypass'); }
catch (RuntimeException $e) { check_license(str_contains($e->getMessage(), 'domain'), 'license activation requires exact domain'); }
$activation = $licenses->activate($site['license'], 'https://shop.example', str_repeat('a', 32));
check_license($activation['site_id'] === $site['site']['id'], 'activation creates site-scoped credential');
try { $licenses->activate($site['license'], 'https://shop.example', str_repeat('b', 32)); throw new RuntimeException('Installation bypass'); }
catch (RuntimeException $e) { check_license(str_contains($e->getMessage(), 'another installation'), 'cannot re-use on different installation'); }
try { $licenses->issue('merchant2', 'https://shop.example'); throw new RuntimeException('Cross-owner collision'); }
catch (RuntimeException $e) { check_license(str_contains($e->getMessage(), 'Paid plan'), 'no site without entitlement for second merchant'); }
$plans->grant('merchant2', 'start', '+30 days', 'operator');
try { $licenses->issue('merchant2', 'https://shop.example'); throw new RuntimeException('Duplicate origin'); }
catch (RuntimeException $e) { check_license(str_contains($e->getMessage(), 'already registered'), 'domain uniquely reserved'); }
$plans->grant('merchant1', 'business', '+30 days', 'operator');
for ($i=2; $i<=5; $i++) { $licenses->issue('merchant1', 'https://shop' . $i . '.example'); }
check_license($plans->siteCount('merchant1') === 5, 'Business = maximum 5 sites');
try { $plans->grant('merchant1', 'start', '+30 days', 'operator'); throw new RuntimeException('Unsafe downgrade'); }
catch (RuntimeException $e) { check_license(str_contains($e->getMessage(), 'exceeds'), 'downgrade cannot silently orphan existing sites'); }
$plans->grant('merchant1', 'pro', '+30 days', 'operator');
check_license($plans->siteLimit('merchant1') === 25, 'Pro = maximum 25 sites');
$rotated = $licenses->rotate($site['site']['id'], 'merchant1');
check_license($store->read('tokens', hash('sha256', $activation['api_key']))['revoked'], 'rotation revokes API key');
try { $licenses->activate($site['license'], 'https://shop.example', str_repeat('a', 32)); throw new RuntimeException('Old license valid'); }
catch (RuntimeException $e) { check_license(str_contains($e->getMessage(), 'invalid'), 'rotation revokes previous license'); }
$licenses->delete($site['site']['id'], 'merchant1');
check_license($plans->siteCount('merchant1') === 4, 'deletion frees plan slot');
$plans->suspend('merchant1', 'operator');
try { $licenses->issue('merchant1', 'https://new.example'); throw new RuntimeException('Suspension bypass'); }
catch (RuntimeException $e) { check_license(str_contains($e->getMessage(), 'Paid plan'), 'suspension disables new sites'); }
try { $licenses->activate($rotated['license'], 'https://shop.example', str_repeat('a', 32)); throw new RuntimeException('Inactive activation'); }
catch (RuntimeException $e) { check_license(str_contains($e->getMessage(), 'Paid plan') || str_contains($e->getMessage(), 'revoked') || str_contains($e->getMessage(), 'invalid'), 'suspension/removed site cannot activate'); }
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($files as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
rmdir($folder);
echo "Site license/paid plan tests passed.\n";
