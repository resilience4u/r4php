<?php
namespace Resiliente\R4PHP\Storage;

use Resilience4u\R4Contracts\Contracts\StorageAdapter;

final class InMemoryStorage implements StorageAdapter
{
    private array $data = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->data[$key] = $value;
    }

    public function delete(string $key): void
    {
        unset($this->data[$key]);
    }
}
