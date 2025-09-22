<?php
namespace Resiliente\R4PHP\Core;

use Resiliente\R4PHP\Contracts\Policy;
use Resiliente\R4PHP\Policies\RetryPolicy;
use Resiliente\R4PHP\Policies\CircuitBreakerPolicy;
use Resiliente\R4PHP\Policies\RateLimiterPolicy;
use Resiliente\R4PHP\Policies\TimeLimiterPolicy;
use Resiliente\R4PHP\Policies\BulkheadPolicy;

final class Factory
{
    public static function fromConfig(Config $cfg): Policy
    {
        $policies = [];

        if ($cfg->rateLimiter)     $policies[] = RateLimiterPolicy::fromArray($cfg->rateLimiter);
        if ($cfg->bulkhead)        $policies[] = BulkheadPolicy::fromArray($cfg->bulkhead);
        if ($cfg->timeLimiter)     $policies[] = TimeLimiterPolicy::fromArray($cfg->timeLimiter);
        if ($cfg->retry)           $policies[] = RetryPolicy::fromArray($cfg->retry);
        if ($cfg->circuitBreaker)  $policies[] = CircuitBreakerPolicy::fromArray($cfg->circuitBreaker);

        return new Chain(...$policies);
    }
}
