<?php
namespace Resiliente\R4PHP\Core;

use Resiliente\R4PHP\Contracts\Policy;

final class Presets
{
    public static function httpDefault(): Policy
    {
        $cfg = Config::fromArray([
            'retry' => ['maxAttempts' => 3, 'initialMs' => 100, 'maxMs' => 800, 'multiplier' => 2.0, 'jitter' => true],
            'circuitBreaker' => ['name'=>'http','failureRatePct'=>50,'minSamples'=>4,'timeWindowSec'=>5,'openMs'=>2000],
            'timeLimiter' => ['timeoutMs' => 2000],
        ]);
        return Factory::fromConfig($cfg);
    }
}
