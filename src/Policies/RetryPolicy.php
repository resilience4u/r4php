<?php
namespace Resiliente\R4PHP\Policies;

use Resiliente\R4PHP\Contracts\Executable;
use Resiliente\R4PHP\Contracts\Policy;
use STS\Backoff\Backoff;
use STS\Backoff\Strategies\ExponentialStrategy;
use STS\Backoff\Strategies\ConstantStrategy;

final class RetryPolicy implements Policy
{
    public function __construct(
        private Backoff $backoff,
        private string $name = 'retry'
    ) {}

    public static function fromArray(array $a): self
    {
        $strategy = ($a['strategy'] ?? 'exponential') === 'constant'
            ? new ConstantStrategy(interval: ($a['intervalMs'] ?? 200) / 1000)
            : new ExponentialStrategy(
                initialDelay: ($a['initialMs'] ?? 200) / 1000,
                maxDelay:     ($a['maxMs'] ?? 5_000) / 1000,
                multiplier:   $a['multiplier'] ?? 2.0,
                jitter:       $a['jitter'] ?? true
            );

        $bo = new Backoff($strategy, attempts: $a['maxAttempts'] ?? 5);
        return new self($bo);
    }

    public function name(): string { return $this->name; }

    public function execute(Executable $op): mixed
    {
        return $this->backoff->run(fn() => $op());
    }
}
