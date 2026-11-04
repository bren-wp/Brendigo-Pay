<?php

declare(strict_types=1);

namespace BrendigoPay;

use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

/** Session payloads are stored as base64 inside JSON under storage/data/sessions. */
final class JsonSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    private mixed $handle = null;

    public function __construct(private readonly JsonStore $store, private readonly string $directory)
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        if (is_resource($this->handle)) {
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
        }
        $this->handle = null;
        return true;
    }

    private function valid(string $id): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_-]{16,128}$/D', $id);
    }

    public function read(string $id): string|false
    {
        if (!$this->valid($id)) {
            return '';
        }
        $this->close();
        $handle = fopen($this->directory . '/' . $id . '.lock', 'c');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            return false;
        }
        $this->handle = $handle;
        $record = $this->store->read('sessions', $id);
        if ($record === null) {
            return '';
        }
        $payload = base64_decode((string) ($record['payload'] ?? ''), true);
        return $payload === false ? '' : $payload;
    }

    public function write(string $id, string $data): bool
    {
        if (!$this->valid($id)) {
            return false;
        }
        $this->store->write('sessions', $id, ['payload' => base64_encode($data), 'updated_at' => time()]);
        return true;
    }

    public function destroy(string $id): bool
    {
        if ($this->valid($id)) {
            $path = $this->directory . '/' . $id . '.json';
            if (is_file($path)) {
                unlink($path);
            }
        }
        return true;
    }

    public function validateId(string $id): bool
    {
        return $this->valid($id) && $this->store->read('sessions', $id) !== null;
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        return $this->write($id, $data);
    }

    public function gc(int $max_lifetime): int|false
    {
        $removed = 0;
        foreach (glob($this->directory . '/*.json') ?: [] as $file) {
            if (filemtime($file) !== false && filemtime($file) < time() - $max_lifetime) {
                if (@unlink($file)) {
                    $removed++;
                }
            }
        }
        return $removed;
    }
}
