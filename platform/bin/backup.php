<?php
/** Encrypted Brendigo Pay JSON backup. CLI only; save config.php/app_key separately. */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

$configFile = dirname(__DIR__) . '/config.php';
$config = is_file($configFile) ? require $configFile : null;
$passphrase = getenv('BP_BACKUP_PASSPHRASE');
$target = $argv[1] ?? '';
if (!is_array($config) || !is_string($passphrase) || strlen($passphrase) < 16
    || strlen($target) < 4 || !is_dir(dirname($target))) {
    fwrite(STDERR, "Usage: BP_BACKUP_PASSPHRASE='long secret' php bin/backup.php /private/backup.bpenc\n");
    exit(2);
}
$dataRoot = realpath($config['data_dir']);
$parent = realpath(dirname($target));
$publicRoot = realpath(dirname(__DIR__) . '/public');
if ($dataRoot === false || $parent === false || $publicRoot === false || str_starts_with($parent . '/', $publicRoot . '/')) {
    fwrite(STDERR, "Unsafe storage or backup path\n");
    exit(2);
}
if (file_exists($target)) {
    fwrite(STDERR, "Backup file already exists; no overwrite\n");
    exit(2);
}
$entries = [];
$total = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dataRoot, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if (!$file->isFile() || $file->isLink() || $file->getExtension() !== 'json') {
        continue;
    }
    $relative = substr($file->getPathname(), strlen($dataRoot) + 1);
    $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
    if (!preg_match('#^[a-z][a-z0-9_-]{0,39}/[a-zA-Z0-9_-]{1,128}\.json$#D', $relative)
        || str_starts_with($relative, 'sessions/') || str_starts_with($relative, 'locks/')) {
        continue;
    }
    $raw = file_get_contents($file->getPathname());
    if (!is_string($raw) || !is_array(json_decode($raw, true))) {
        throw new RuntimeException('Corrupt JSON entry: ' . $relative);
    }
    $total += strlen($raw);
    if ($total > 50 * 1024 * 1024) {
        throw new RuntimeException('Backup limit exceeded (50 MB JSON); use a streaming archive process');
    }
    $entries[$relative] = $raw;
}
ksort($entries, SORT_STRING);
$data = json_encode(['format' => 1, 'generated_at' => gmdate('c'), 'entries' => $entries], JSON_THROW_ON_ERROR);
$compressed = gzencode($data, 6);
if ($compressed === false) {
    throw new RuntimeException('Compression failed');
}
$salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$key = sodium_crypto_pwhash(SODIUM_CRYPTO_SECRETBOX_KEYBYTES, $passphrase, $salt,
    SODIUM_CRYPTO_PWHASH_OPSLIMIT_MODERATE, SODIUM_CRYPTO_PWHASH_MEMLIMIT_MODERATE);
$binary = "BPJSON03" . $salt . $nonce . sodium_crypto_secretbox($compressed, $nonce, $key);
sodium_memzero($key);
if (file_put_contents($target, $binary, LOCK_EX) !== strlen($binary)) {
    @unlink($target);
    throw new RuntimeException('Backup write failed');
}
chmod($target, 0600);
echo 'Encrypted JSON backup created: ' . count($entries) . " entries\n";
