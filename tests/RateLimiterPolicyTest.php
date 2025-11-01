<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Resilience4u\R4Contracts\Contracts\Executable;
use Resiliente\R4PHP\Policies\RateLimiterPolicy;

final class RateLimiterPolicyTest extends TestCase
{
    public function testAcceptsFirstAndRejectsSecond(): void
    {
        $p = RateLimiterPolicy::fromArray([
            'limiter' => 'unit',
            'tokensPerSecond' => 1,
            'limit' => 1,
        ]);

        $ok = $p->execute(new class implements Executable {
            public function __invoke(): mixed { return 'ok'; }
        });
        $this->assertSame('ok', $ok);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Rate limit exceeded');
        $p->execute(new class implements Executable {
            public function __invoke(): mixed { return 'x'; }
        });
    }
}
