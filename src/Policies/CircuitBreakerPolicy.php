<?php
namespace Resiliente\R4PHP\Policies;

use Resiliente\R4PHP\Contracts\Policy;
use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\Storage\Adapter\Apcu as ApcuAdapter;

final class CircuitBreakerPolicy implements Policy
{
    public function __construct(private Ganesha $ganesha, private string $name) {}

    public static function fromArray(array $a): self
    {
        $builder = Builder::withRateStrategy();

        $builder->adapter(new ApcuAdapter());

        $failureRate = (int)($a['failureRatePct'] ?? 50);
        $minSamples  = (int)($a['minSamples']      ?? 10);
        $openMs      = (int)($a['openMs']          ?? 30000);
        $timeWindowS = (int)($a['timeWindowSec']   ?? 10);

        $halfOpenSec = max(1, (int)ceil($openMs / 1000));

        $builder
            ->failureRateThreshold($failureRate)
            ->minimumRequests($minSamples)
            ->timeWindow($timeWindowS)
            ->intervalToHalfOpen($halfOpenSec);

        $ganesha = $builder->build();
        return new self($ganesha, $a['name'] ?? 'cb');
    }

    public function name(): string { return "circuitBreaker:{$this->name}"; }

    public function execute(\Resiliente\R4PHP\Contracts\Executable $op): mixed
    {
        if (!$this->ganesha->isAvailable($this->name)) {
            throw new \RuntimeException('circuit breaker open: ' . $this->name);
        }

        try {
            $res = $op();
            $this->ganesha->success($this->name);
            return $res;
        } catch (\Throwable $e) {
            $this->ganesha->failure($this->name);
            throw $e;
        }
    }
}
