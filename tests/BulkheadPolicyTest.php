<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Resilience4u\R4Contracts\Contracts\Executable;
use Resiliente\R4PHP\Policies\BulkheadPolicy;

final class BulkheadPolicyTest extends TestCase
{
    public function testBlocksReentrantExecutionWhenFull(): void
    {
        $p = BulkheadPolicy::fromArray(['maxConcurrent' => 1]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bulkhead full');

        $p->execute(new class($p) implements Executable {
            private BulkheadPolicy $p;

            public function __construct(BulkheadPolicy $p)
            {
                $this->p = $p;
            }

            public function __invoke(): mixed
            {
                return $this->p->execute(new class implements Executable {
                    public function __invoke(): mixed { return 'inner'; }
                });
            }
        });
    }
}
