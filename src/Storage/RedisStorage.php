<?php
declare(strict_types=1);

namespace Resiliente\R4PHP\Storage;

use Redis;
use Resilience4u\R4Contracts\Contracts\StorageAdapter;

final class RedisStorage implements StorageAdapter
{
    private Redis $redis;

    public function __construct(
        ?Redis $redis = null,
        string $host = '127.0.0.1',
        int $port = 6379,
        float $timeout = 1.5
    ) {
        $this->redis = $redis ?? new Redis();
        if (!$redis) {
            $this->redis->connect($host, $port, $timeout);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($key);
        return $value === false ? $default : unserialize($value);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $serialized = serialize($value);
        if ($ttl) {
            return $this->redis->setex($key, $ttl, $serialized);
        }
        return $this->redis->set($key, $serialized);
    }

    public function delete(string $key): bool
    {
        return (bool)$this->redis->del($key);
    }

    public function increment(string $key, int $by = 1, ?int $ttl = null): int
    {
        $newVal = $this->redis->incrBy($key, $by);
        if ($ttl) {
            $this->redis->expire($key, $ttl);
        }
        return $newVal;
    }

    public function getMultiple(array $keys): array
    {
        $values = $this->redis->mGet($keys);
        $result = [];
        foreach ($keys as $i => $key) {
            $val = $values[$i];
            $result[$key] = $val === false ? null : unserialize($val);
        }
        return $result;
    }
}
