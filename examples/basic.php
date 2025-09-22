<?php

use Resiliente\R4PHP\Contracts\Executable;
use Resiliente\R4PHP\Core\Config;
use Resiliente\R4PHP\Core\Factory;

require __DIR__ . '/../vendor/autoload.php';

$cfg = Config::fromArray([
    'rateLimiter' => ['tokensPerSecond' => 20, 'burst' => 40],
    'bulkhead'    => ['maxConcurrent' => 32],
    'timeLimiter' => ['timeoutMs' => 1500],
    'retry'       => ['maxAttempts' => 5, 'initialMs' => 200, 'maxMs' => 3000, 'multiplier' => 2.0, 'jitter' => true],
    'circuitBreaker' => ['name' => 'core', 'failureRatePct' => 50, 'minSamples' => 10, 'openMs' => 30000],
]);

$policy = Factory::fromConfig($cfg);

$result = $policy->execute(new class implements Executable {
    public function __invoke(): mixed {
        if (random_int(0, 1)) throw new \RuntimeException('falhou');
        return "ok";
    }
});

echo $result, PHP_EOL;
