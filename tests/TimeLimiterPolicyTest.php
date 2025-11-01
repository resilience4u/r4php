<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Resilience4u\R4Contracts\Contracts\Executable;
use Resiliente\R4PHP\Policies\TimeLimiterPolicy;

final class TimeLimiterPolicyTest extends TestCase
{
    public function testThrowsWhenExceeded(): void
    {
        $p = TimeLimiterPolicy::fromArray(['timeoutMs' => 50]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Time limit exceeded');

        $p->execute(new class implements Executable {
            public function __invoke(): mixed {
                usleep(150 * 1000);
                return 'too-late';
            }
        });
    }
}
