<?php
/** Restore into EMPTY storage/data directory only. Reuse original config.php/app_key. */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
$configFile = dirname(__DIR__) . '/config.php';
$config = is_file($configFile) ? require $configFile : null;
$passphrase = getenv('BP_BACKUP_PASSPHRASE');
$file = $argv[1] ?? '';
if (!is_array($config) || !is_string($passphrase) || strlen($passphrase) < 16 || !is_file($file)) {
    fwrite(STDERR, "Usage: BP_BACKUP_PASSPHRASE='secret' php bin/restore.php /private/backup.bpenc\n");
    exit(2);
}
$root = $config['data_dir'];
if (!is_dir($root) && !mkdir($root, 0700, true) && !is_dir($root)) {
    throw new RuntimeException('Storage directory unavailable');
}
$existing = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($existing as $entry) {
    if ($entry->isFile() && str_ends_with($entry->getFilename(), '.json')) {
        fwrite(STDERR, "Restore requires EMPTY JSON storage. Stop the application and preserve a backup first.\n");
        exit(3);
    }
}
$binary = file_get_contents($file);
if (!is_string($binary) || strlen($binary) < 80 || !str_starts_with($binary, 'BPJSON03')) {
    throw new RuntimeException('Invalid backup format');
}
$offset = 8;
$salt = substr($binary, $offset, SODIUM_CRYPTO_PWHASH_SALTBYTES);
$offset += SODIUM_CRYPTO_PWHASH_SALTBYTES;
$nonce = substr($binary, $offset, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$offset += SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
$key = sodium_crypto_pwhash(SODIUM_CRYPTO_SECRETBOX_KEYBYTES, $passphrase, $salt,
    SODIUM_CRYPTO_PWHASH_OPSLIMIT_MODERATE, SODIUM_CRYPTO_PWHASH_MEMLIMIT_MODERATE);
$compressed = sodium_crypto_secretbox_open(substr($binary, $offset), $nonce, $key);
sodium_memzero($key);
if ($compressed === false) {
    throw new RuntimeException('Incorrect passphrase or damaged backup');
}
$decoded = gzdecode($compressed, 75 * 1024 * 1024);
if (!is_string($decoded)) {
    throw new RuntimeException('Damaged compressed backup');
}
$manifest = json_decode($decoded, true, 64, JSON_THROW_ON_ERROR);
if (!is_array($manifest) || ($manifest['format'] ?? '') !== 1 || !is_array($manifest['entries'] ?? null)) {
    throw new RuntimeException('Invalid backup manifest');
}
$validated = [];
foreach ($manifest['entries'] as $relative => $raw) {
    if (!is_string($relative) || !preg_match('#^[a-z][a-z0-9_-]{0,39}/[a-zA-Z0-9_-]{1,128}\.json$#D', $relative)
        || str_starts_with($relative, 'sessions/') || !is_string($raw) || !is_array(json_decode($raw, true))) {
        throw new RuntimeException('Invalid JSON entry in backup');
    }
    $validated[$relative] = $raw;
}
foreach ($validated as $relative => $raw) {
    $path = $root . '/' . $relative;
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Cannot create restore directory');
    }
    if (is_link($dir) || file_exists($path) || is_link($path)) {
        throw new RuntimeException('Restore refused to overwrite: ' . $relative);
    }
    if (file_put_contents($path, $raw, LOCK_EX) !== strlen($raw)) {
        throw new RuntimeException('Restore failed: ' . $relative);
    }
    chmod($path, 0600);
}
echo 'Restored ' . count($validated) . " JSON entries. Restart application; old sessions are intentionally excluded.\n";
