<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Resiliente\R4PHP\Policies\BulkheadPolicy;
use Resiliente\R4PHP\Contracts\Executable;

final class BulkheadPolicyTest extends TestCase
{
    public function testBlocksReentrantExecutionWhenFull(): void
    {
        $p = BulkheadPolicy::fromArray(['maxConcurrent' => 1]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bulkhead full');

        $p->execute(new class($p) implements Executable {
            public function __construct(BulkheadPolicy $p) { $this->p = $p; }
            public function __invoke(): mixed {
                return $this->p->execute(new class implements Executable {
                    public function __invoke(): mixed { return 'inner'; }
                });
            }
        });
    }
}
