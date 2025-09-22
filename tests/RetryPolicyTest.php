<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Resiliente\R4PHP\Policies\RetryPolicy;
use Resiliente\R4PHP\Contracts\Executable;

final class RetryPolicyTest extends TestCase
{
    public function testRetriesAndEventuallySucceeds(): void
    {
        $p = RetryPolicy::fromArray([
            'maxAttempts' => 3,
            'initialMs' => 10,
            'maxMs' => 20,
            'multiplier' => 2.0,
            'jitter' => false
        ]);

        $calls = 0;
        $result = $p->execute(new class($calls) implements Executable {
            private static int $c = 0;
            public function __construct(&$calls){ self::$c = 0; }
            public function __invoke(): mixed {
                self::$c++;
                if (self::$c < 3) throw new RuntimeException('fail');
                return 'ok';
            }
        });

        $this->assertSame('ok', $result);
    }

    public function testRetriesAndFails(): void
    {
        $p = RetryPolicy::fromArray([
            'maxAttempts' => 2,
            'initialMs' => 1,
            'maxMs' => 2,
            'multiplier' => 2.0,
            'jitter' => false
        ]);

        $this->expectException(RuntimeException::class);

        $p->execute(new class implements Executable {
            public function __invoke(): mixed {
                throw new RuntimeException('always');
            }
        });
    }
}
