<?php
declare(strict_types=1);

namespace Resiliente\R4PHP\Storage;

use Resilience4u\R4Contracts\Contracts\StorageAdapter;

final class ApcuStorage implements StorageAdapter
{
    public function get(string $key, mixed $default = null): mixed
    {
        $success = false;
        $value = apcu_fetch($key, $success);
        return $success ? $value : $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        apcu_store($key, $value, $ttl ?? 0);
    }

    public function delete(string $key): void
    {
        apcu_delete($key);
    }
}
