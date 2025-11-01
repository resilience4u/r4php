<?php
declare(strict_types=1);

use Resilience4u\R4Contracts\Contracts\Executable;
use Resiliente\R4PHP\Core\Config;
use Resiliente\R4PHP\Policies\CircuitBreakerPolicy;
use Resiliente\R4PHP\Policies\RetryPolicy;
use Resiliente\R4PHP\Policies\TimeLimiterPolicy;
use Resiliente\R4PHP\Policies\RateLimiterPolicy;
use Resiliente\R4PHP\Policies\BulkheadPolicy;
use Resiliente\R4PHP\Storage\InMemoryStorage;

require __DIR__ . '/../vendor/autoload.php';

$cfg = Config::fromArray([
    'rateLimiter' => ['tokensPerSecond' => 20, 'limit' => 40],
    'bulkhead'    => ['maxConcurrent' => 32],
    'timeLimiter' => ['timeoutMs' => 1500],
    'retry'       => ['maxAttempts' => 5, 'initialMs' => 200, 'maxMs' => 3000, 'multiplier' => 2.0, 'jitter' => true],
    'circuitBreaker' => [
        'name' => 'core',
        'failureRatePct' => 50,
        'minSamples' => 10,
        'openMs' => 30000,
        'timeWindowSec' => 10,
    ],
]);

$storage = new InMemoryStorage();

$policies = [
    RateLimiterPolicy::fromArray($cfg->rateLimiter() ?? []),
    BulkheadPolicy::fromArray($cfg->bulkhead() ?? []),
    TimeLimiterPolicy::fromArray($cfg->timeLimiter() ?? []),
    RetryPolicy::fromArray($cfg->retry() ?? []),
    CircuitBreakerPolicy::fromArray($cfg->circuitBreaker() ?? [], $storage),
];

$composed = array_reduce(
    array_reverse($policies),
    fn($next, $policy) => new class($policy, $next) implements Executable {
        public function __construct(private $policy, private $next) {}
        public function __invoke(): mixed { return $this->policy->execute($this->next); }
    },
    new class implements Executable {
        public function __invoke(): mixed {
            if (random_int(0, 1)) throw new \RuntimeException('falhou');
            return "ok";
        }
    }
);

try {
    $result = $composed();
    echo "Resultado: {$result}" . PHP_EOL;
} catch (\Throwable $e) {
    echo "Erro: {$e->getMessage()}" . PHP_EOL;
}
