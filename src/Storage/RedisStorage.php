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
        if ($redis) {
            $this->redis = $redis;
        } else {
            $this->redis = new Redis();
            $this->redis->connect($host, $port, $timeout);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($key);
        return $value === false ? $default : unserialize($value);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $serialized = serialize($value);
        if ($ttl) {
            $this->redis->setex($key, $ttl, $serialized);
        } else {
            $this->redis->set($key, $serialized);
        }
    }

    public function delete(string $key): void
    {
        $this->redis->del($key);
    }
}
