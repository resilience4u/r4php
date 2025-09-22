<?php
namespace Resiliente\R4PHP\Policies;

use Resiliente\R4PHP\Contracts\Executable;
use Resiliente\R4PHP\Contracts\Policy;
use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Builder;

final class CircuitBreakerPolicy implements Policy
{
    public function __construct(private Ganesha $ganesha, private string $name) {}

    public static function fromArray(array $a): self
    {
        $builder = Builder::withRateStrategy();
        $builder
            ->failureRateThreshold($a['failureRatePct'] ?? 50)
            ->minimumRequests($a['minSamples'] ?? 10)
            ->intervalToHalfOpen((int) (($a['openMs'] ?? 30000) / 1000));

        $ganesha = $builder->build();
        return new self($ganesha, $a['name'] ?? 'cb');
    }

    public function name(): string { return "circuitBreaker:{$this->name}"; }

    public function execute(Executable $op): mixed
    {
        return $this->ganesha->invoke(fn() => $op());
    }
}
