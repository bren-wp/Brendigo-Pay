<?php

declare(strict_types=1);

namespace BrendigoPay;

use RuntimeException;

final class JsonStore
{
    public function __construct(private readonly string $directory)
    {
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Data directory could not be created');
        }
        @chmod($directory, 0700);
    }

    private function path(string $collection, string $id): string
    {
        if (!preg_match('/^[a-z][a-z0-9_-]{0,39}$/D', $collection)
            || !preg_match('/^[a-zA-Z0-9_-]{1,128}$/D', $id)) {
            throw new RuntimeException('Invalid store identifier');
        }
        $dir = $this->directory . DIRECTORY_SEPARATOR . $collection;
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Store collection unavailable');
        }
        return $dir . DIRECTORY_SEPARATOR . $id . '.json';
    }

    public function read(string $collection, string $id): ?array
    {
        $path = $this->path($collection, $id);
        if (!is_file($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException('Read failed');
        }
        $data = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON record');
        }
        return $data;
    }

    public function write(string $collection, string $id, array $data): void
    {
        $path = $this->path($collection, $id);
        $temp = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($temp, $json . "\n", LOCK_EX) === false) {
            throw new RuntimeException('Write failed');
        }
        @chmod($temp, 0600);
        if (!rename($temp, $path)) {
            @unlink($temp);
            throw new RuntimeException('Atomic replace failed');
        }
    }

    /** Delete a known record; callers must hold the appropriate ownership lock. */
    public function delete(string $collection, string $id): void
    {
        $path = $this->path($collection, $id);
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Delete failed');
        }
    }

    public function all(string $collection): array
    {
        $this->path($collection, 'probe');
        $files = glob($this->directory . DIRECTORY_SEPARATOR . $collection . DIRECTORY_SEPARATOR . '*.json') ?: [];
        $items = [];
        foreach ($files as $file) {
            $id = basename($file, '.json');
            try {
                $item = $this->read($collection, $id);
                if ($item !== null) {
                    $items[] = $item;
                }
            } catch (\JsonException $e) {
                error_log('Brendigo Pay corrupt JSON: ' . $collection . '/' . $id);
            }
        }
        return $items;
    }

    /** Returns existing records together with validated filename IDs (for revocation/UI). */
    public function allWithIds(string $collection): array
    {
        $this->path($collection, 'probe');
        $records = [];
        foreach (glob($this->directory . DIRECTORY_SEPARATOR . $collection . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
            $id = basename($file, '.json');
            if (!preg_match('/^[a-zA-Z0-9_-]{1,128}$/D', $id)) {
                continue;
            }
            try {
                $record = $this->read($collection, $id);
                if ($record !== null) {
                    $records[] = ['id' => $id, 'record' => $record];
                }
            } catch (\JsonException $e) {
                error_log('Brendigo Pay corrupt JSON: ' . $collection . '/' . $id);
            }
        }
        return $records;
    }

    public function locked(string $name, callable $action): mixed
    {
        if (!preg_match('/^[a-zA-Z0-9_-]{1,128}$/D', $name)) {
            throw new RuntimeException('Invalid lock');
        }
        $dir = $this->directory . '/locks';
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Lock directory unavailable');
        }
        $handle = fopen($dir . '/' . $name . '.lock', 'c');
        if ($handle === false) {
            throw new RuntimeException('Lock unavailable');
        }
        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Lock failed');
            }
            return $action();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
