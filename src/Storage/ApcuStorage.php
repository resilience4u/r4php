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

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return apcu_store($key, $value, $ttl ?? 0);
    }

    public function delete(string $key): bool
    {
        return (bool)apcu_delete($key);
    }

    public function increment(string $key, int $by = 1, ?int $ttl = null): int
    {
        if (!apcu_exists($key)) {
            apcu_add($key, 0, $ttl ?? 0);
        }
        $newValue = apcu_inc($key, $by);
        if ($ttl) {
            apcu_store($key, $newValue, $ttl);
        }
        return $newValue;
    }

    public function getMultiple(array $keys): array
    {
        $found = [];
        $values = apcu_fetch($keys, $success);
        if (is_array($values)) {
            foreach ($keys as $key) {
                $found[$key] = $values[$key] ?? null;
            }
        }
        return $found;
    }
}
