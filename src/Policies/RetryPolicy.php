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
        $strategyName = $a['strategy'] ?? 'exponential';

        if ($strategyName === 'constant') {
            $intervalMs = (int)($a['intervalMs'] ?? 200);
            $strategy = new ConstantStrategy($intervalMs);
        } else {
            $initialMs = (int)($a['initialMs'] ?? 200);
            $maxMs     = (int)($a['maxMs'] ?? 5000);
            $mult      = (float)($a['multiplier'] ?? 2.0);
            $jitter    = (bool)($a['jitter'] ?? true);
            $strategy  = new ExponentialStrategy($initialMs, $maxMs, $mult, $jitter);
        }

        $attempts = (int)($a['maxAttempts'] ?? 5);
        $bo = new Backoff($attempts, $strategy);

        return new self($bo);
    }

    public function name(): string { return $this->name; }

    public function execute(Executable $op): mixed
    {
        return $this->backoff->run(fn() => $op());
    }
}
