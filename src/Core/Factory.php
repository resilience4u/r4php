<?php
namespace Resiliente\R4PHP\Core;

use Resilience4u\R4Contracts\Contracts\Policy;
use Resiliente\R4PHP\Policies\RateLimiterPolicy;
use Resiliente\R4PHP\Policies\BulkheadPolicy;
use Resiliente\R4PHP\Policies\TimeLimiterPolicy;
use Resiliente\R4PHP\Policies\RetryPolicy;
use Resiliente\R4PHP\Policies\CircuitBreakerPolicy;

/**
 * Builds a Policy chain from Config.
 */
final class Factory
{
    /**
     * Compose policies in a sane order for typical IO/HTTP:
     * RateLimiter -> Bulkhead -> TimeLimiter -> Retry -> CircuitBreaker
     */
    public static function fromConfig(Config $cfg): Policy
    {
        /** @var list<Policy> $policies */
        $policies = [];

        // Each section returns array<string,mixed>
        $rl = $cfg->rateLimiter();
        if ($rl !== [] && is_array($rl)) {
            $policies[] = RateLimiterPolicy::fromArray($rl);
        }

        $bh = $cfg->bulkhead();
        if ($bh !== [] && is_array($bh)) {
            $policies[] = BulkheadPolicy::fromArray($bh);
        }

        $tl = $cfg->timeLimiter();
        if ($tl !== [] && is_array($tl)) {
            $policies[] = TimeLimiterPolicy::fromArray($tl);
        }

        $rt = $cfg->retry();
        if ($rt !== [] && is_array($rt)) {
            $policies[] = RetryPolicy::fromArray($rt);
        }

        $cb = $cfg->circuitBreaker();
        if ($cb !== [] && is_array($cb)) {
            $policies[] = CircuitBreakerPolicy::fromArray($cb);
        }

        // Fallback: no policies configured → Chain vazia (pass-through)
        return new Chain(...$policies);
    }
}
