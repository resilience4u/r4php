<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Resilience4u\R4Contracts\Contracts\Executable;
use Resiliente\R4PHP\Core\Config;
use Resiliente\R4PHP\Core\Factory;

final class FactoryIntegrationTest extends TestCase
{
    public function testBuildsChainAndRateLimitTriggers(): void
    {
        $policy = Factory::fromConfig(Config::fromArray([
            'rateLimiter' => ['tokensPerSecond' => 0, 'limit' => 1],
            'bulkhead'    => ['maxConcurrent' => 1],
            'timeLimiter' => ['timeoutMs' => 200],
            'retry'       => ['maxAttempts' => 2, 'initialMs' => 10, 'maxMs' => 20, 'multiplier' => 2.0, 'jitter' => false],
            'circuitBreaker' => [
                'name' => 'factory-cb',
                'failureRatePct' => 50,
                'minSamples' => 2,
                'timeWindowSec' => 1,
                'openMs' => 300,
            ],
        ]));

        $ok = $policy->execute(new class implements Executable {
            public function __invoke(): mixed { return 'ok'; }
        });
        $this->assertSame('ok', $ok);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Rate limit exceeded');
        $policy->execute(new class implements Executable {
            public function __invoke(): mixed { return 'second'; }
        });
    }
}
