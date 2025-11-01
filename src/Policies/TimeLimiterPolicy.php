<?php
namespace Resiliente\R4PHP\Policies;


use Resilience4u\R4Contracts\Contracts\Executable;
use Resilience4u\R4Contracts\Contracts\Policy;

final class TimeLimiterPolicy implements Policy
{
    public function __construct(private int $timeoutMs) {}

    public static function fromArray(array $a): self
    {
        return new self((int)($a['timeoutMs'] ?? 1500));
    }

    public function name(): string { return 'timeLimiter'; }

    public function execute(Executable $op): mixed
    {
        $start = microtime(true);
        $result = $op();
        $elapsedMs = (microtime(true) - $start) * 1000;
        if ($elapsedMs > $this->timeoutMs) {
            throw new \RuntimeException('Time limit exceeded');
        }
        return $result;
    }
}
