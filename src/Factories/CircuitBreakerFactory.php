<?php
declare(strict_types=1);

namespace Resiliente\R4PHP\Factories;

use Redis;
use Resiliente\R4PHP\Policies\CircuitBreakerPolicy;
use Resiliente\R4PHP\Storage\ApcuStorage;
use Resiliente\R4PHP\Storage\InMemoryStorage;
use Resiliente\R4PHP\Storage\RedisStorage;

final class CircuitBreakerFactory
{
    public static function makeInMemory(string $name, array $config = []): CircuitBreakerPolicy
    {
        return CircuitBreakerPolicy::fromArray(array_merge(['name' => $name], $config), new InMemoryStorage());
    }

    public static function makeApcu(string $name, array $config = []): CircuitBreakerPolicy
    {
        return CircuitBreakerPolicy::fromArray(array_merge(['name' => $name], $config), new ApcuStorage());
    }

    public static function makeRedis(string $name, ?\Redis $client = null, array $config = []): CircuitBreakerPolicy
    {
        $client ??= new Redis();
        $client->connect('127.0.0.1', 6379, 1.5);

        return CircuitBreakerPolicy::fromArray(array_merge(['name' => $name], $config), new RedisStorage($client));
    }
}
