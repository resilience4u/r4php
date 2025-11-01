<?php
namespace Resiliente\R4PHP\Policies;

use Resilience4u\R4Contracts\Contracts\Executable;
use Resilience4u\R4Contracts\Contracts\Policy;

/**
 * Compose multiple policies into a single executable unit.
 */
final class CompositePolicy implements Policy
{
    /** @var Policy[] */
    private array $policies;

    public function __construct(Policy ...$policies)
    {
        $this->policies = $policies;
    }

    public function name(): string
    {
        return 'composite:' . implode('+', array_map(fn($p) => $p->name(), $this->policies));
    }

    public function execute(Executable $op): mixed
    {
        $chain = array_reduce(
            array_reverse($this->policies),
            fn($next, $policy) => new class($policy, $next) implements Executable {
                public function __construct(private Policy $policy, private Executable $next) {}
                public function __invoke(): mixed { return $this->policy->execute($this->next); }
            },
            $op
        );

        return $chain();
    }
}
