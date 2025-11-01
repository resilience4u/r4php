<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Resilience4u\R4Contracts\Contracts\Executable;
use Resiliente\R4PHP\Policies\CircuitBreakerPolicy;

final class CircuitBreakerPolicyTest extends TestCase
{
    public function setUp(): void
    {
        if (!function_exists('apcu_clear_cache')) {
            $this->markTestSkipped('APCu not available');
        } else {
            apcu_clear_cache();
        }
    }

    public function testTripsAfterFailuresAndRecoversAfterTimeout(): void
    {
        $p = CircuitBreakerPolicy::fromArray([
            'name' => 'cb-unit',
            'failureRatePct' => 50,
            'minSamples' => 2,
            'timeWindowSec' => 1,
            'openMs' => 300,
        ]);

        for ($i = 0; $i < 5; $i++) {
            try {
                $p->execute(new class implements Executable {
                    public function __invoke(): mixed {
                        throw new RuntimeException('boom');
                    }
                });
            } catch (RuntimeException) {}
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/circuit breaker open/i');
        $p->execute(new class implements Executable {
            public function __invoke(): mixed { return 'ok'; }
        });
    }

    public function testHalfOpenAfterTimeoutAllowsSuccess(): void
    {
        $p = CircuitBreakerPolicy::fromArray([
            'name' => 'cb-unit-2',
            'failureRatePct' => 50,
            'minSamples' => 2,
            'timeWindowSec' => 1,
            'openMs' => 1200,
        ]);

        for ($i = 0; $i < 4; $i++) {
            try {
                $p->execute(new class implements Executable {
                    public function __invoke(): mixed { throw new RuntimeException('boom'); }
                });
            } catch (\Throwable) {}
        }

        sleep(2);

        $res = $p->execute(new class implements Executable {
            public function __invoke(): mixed { return 'ok'; }
        });

        $this->assertSame('ok', $res, 'Circuit did not recover correctly');
    }
}
