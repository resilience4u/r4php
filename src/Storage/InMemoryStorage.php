<?php
declare(strict_types=1);

namespace Resiliente\R4PHP\Storage;

use Resilience4u\R4Contracts\Contracts\StorageAdapter;

final class InMemoryStorage implements StorageAdapter
{
    private array $data = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $this->data[$key] = $value;
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->data[$key]);
        return true;
    }

    public function increment(string $key, int $by = 1, ?int $ttl = null): int
    {
        $current = (int)($this->data[$key] ?? 0);
        $current += $by;
        $this->data[$key] = $current;
        return $current;
    }

    public function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->data[$key] ?? null;
        }
        return $result;
    }
}
