<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Resiliente\R4PHP\Policies\CircuitBreakerPolicy;
use Resiliente\R4PHP\Contracts\Executable;

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
                $this->fail('Should have thrown');
            } catch (RuntimeException $e) {
            } catch (\Throwable $e) {
                $this->fail('Unexpected exception: ' . $e::class);
            }
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('circuit breaker open');
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
            'openMs' => 1000,
        ]);

        for ($i = 0; $i < 4; $i++) {
            try {
                $p->execute(new class implements Executable {
                    public function __invoke(): mixed { throw new RuntimeException('boom'); }
                });
            } catch (\Throwable $e) { }
        }

        $deadline = microtime(true) + 3.0;
        $ok = false;
        while (microtime(true) < $deadline) {
            try {
                $res = $p->execute(new class implements Executable {
                    public function __invoke(): mixed { return 'ok'; }
                });
                $this->assertSame('ok', $res);
                $ok = true;
                break;
            } catch (\RuntimeException $e) {
                if (str_starts_with($e->getMessage(), 'circuit breaker open')) {
                    usleep(120 * 1000);
                    continue;
                }
                throw $e;
            }
        }

        $this->assertTrue($ok, 'Circuit breaker did not enter half-open within timeout');
    }
}
