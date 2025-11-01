<?php
declare(strict_types=1);

namespace Resiliente\R4PHP\Storage;

use Resilience4u\R4Contracts\Contracts\StorageAdapter;

/**
 * Simple file-based storage for demo/debug purposes.
 * Stores each key as serialized file under /tmp/r4php-cache/
 */
final class FileStorage implements StorageAdapter
{
    private string $dir;

    public function __construct(?string $dir = null)
    {
        $this->dir = $dir ?? sys_get_temp_dir() . '/r4php-cache';
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0777, true);
        }
    }

    private function path(string $key): string
    {
        return $this->dir . '/' . md5($key) . '.cache';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->path($key);
        if (!file_exists($file)) {
            return $default;
        }

        $data = file_get_contents($file);
        return $data === false ? $default : unserialize($data);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $file = $this->path($key);
        file_put_contents($file, serialize($value));
    }

    public function delete(string $key): void
    {
        @unlink($this->path($key));
    }
}
