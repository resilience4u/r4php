<?php
namespace Resiliente\R4PHP\Policies;

use Resilience4u\R4Contracts\Contracts\Executable;
use Resilience4u\R4Contracts\Contracts\Policy;

final class BulkheadPolicy implements Policy
{
    private int $inflight = 0;

    public function __construct(private int $maxConcurrent = 32) {}

    public static function fromArray(array $a): self
    {
        return new self((int)($a['maxConcurrent'] ?? 32));
    }

    public function name(): string { return 'bulkhead'; }

    public function execute(Executable $op): mixed
    {
        if ($this->inflight >= $this->maxConcurrent) {
            throw new \RuntimeException('Bulkhead full');
        }
        $this->inflight++;
        try { return $op(); }
        finally { $this->inflight--; }
    }
}
