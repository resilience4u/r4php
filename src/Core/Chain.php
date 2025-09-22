<?php
namespace Resiliente\R4PHP\Core;

use Resiliente\R4PHP\Contracts\Executable;
use Resiliente\R4PHP\Contracts\Policy;

/**
 * Composição: RateLimiter -> Bulkhead -> TimeLimiter -> Retry -> CircuitBreaker -> op
 * (a ordem é configurável; aqui só encadeamos na ordem passada)
 */
final class Chain implements Policy
{
    /** @var Policy[] */
    private array $policies;

    public function __construct(Policy ...$policies)
    {
        $this->policies = array_values(array_filter($policies));
    }

    public function name(): string { return 'chain'; }

    public function execute(Executable $op): mixed
    {
        $wrapped = array_reduce(
            array_reverse($this->policies),
            fn(callable $next, Policy $p) =>
            fn() => $p->execute(new class($next) implements Executable {
                public function __construct(private $next) {}
                public function __invoke(): mixed { return ($this->next)(); }
            }),
            fn() => $op()
        );

        return $wrapped();
    }
}
