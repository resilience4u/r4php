<?php
namespace Resiliente\R4PHP\Core;

final class Config
{
    public ?array $retry = null;          // ["maxAttempts"=>5,"strategy"=>"exponential","initialMs"=>200,"maxMs"=>5000,"jitter"=>true]
    public ?array $circuitBreaker = null; // ["name"=>"core","failureRatePct"=>50,"minSamples"=>10,"openMs"=>30000]
    public ?array $rateLimiter = null;    // ["limiter"=>"api","tokensPerSecond"=>20,"burst"=>40]
    public ?array $timeLimiter = null;    // ["timeoutMs"=>1500]
    public ?array $bulkhead = null;       // ["maxConcurrent"=>32,"queue"=>0]

    public static function fromArray(array $a): self
    {
        $c = new self();
        foreach (['retry','circuitBreaker','rateLimiter','timeLimiter','bulkhead'] as $k) {
            if (isset($a[$k]) && is_array($a[$k])) $c->$k = $a[$k];
        }
        return $c;
    }
}
