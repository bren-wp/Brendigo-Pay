<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}
$root = dirname(__DIR__);
$url = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$parsed = parse_url($url);
if ($parsed === false || !isset($parsed['scheme'], $parsed['host']) || !in_array($parsed['scheme'], ['http', 'https'], true)) {
    fwrite(STDERR, "Provide an absolute base URL\n");
    exit(1);
}
if ($parsed['scheme'] !== 'https' && !in_array($parsed['host'], ['127.0.0.1', 'localhost'], true)) {
    fwrite(STDERR, "HTTPS is mandatory outside localhost\n");
    exit(1);
}
if (!extension_loaded('sodium')) {
    fwrite(STDERR, "PHP sodium extension is mandatory\n");
    exit(1);
}
$file = $root . '/config.php';
if (file_exists($file)) {
    fwrite(STDERR, "config.php exists; leaving it untouched\n");
    exit(1);
}
$config = [
    'base_url' => $url,
    'app_key' => base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)),
    'data_dir' => $root . '/storage/data',
    'is_local' => in_array($parsed['host'], ['127.0.0.1', 'localhost'], true),
    // Set only registered operator UUIDs here after verifying operator identity.
    'admin_user_ids' => [],
    // Fill in verified business identity before publishing commercial legal pages.
    'legal_operator_name' => '',
    'legal_address' => '',
    'legal_contact_email' => '',

];
file_put_contents($file, "<?php\nreturn " . var_export($config, true) . ";\n", LOCK_EX);
chmod($file, 0600);
@mkdir($root . '/storage/data', 0700, true);
@chmod($root . '/storage/data', 0700);
echo "Brendigo Pay configured: {$url}\n";
