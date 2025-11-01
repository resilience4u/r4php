<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Resilience4u\R4Contracts\Contracts\Executable;
use Resilience4u\R4Contracts\Contracts\Policy;
use Resiliente\R4PHP\Core\Chain;

final class ChainTest extends TestCase
{
    public function testExecutesInOrder(): void
    {
        $trace = new class { public array $events = []; };

        $p1 = new class($trace) implements Policy {
            public function __construct(private object $trace) {}
            public function name(): string { return 'p1'; }
            public function execute(Executable $op): mixed {
                $this->trace->events[] = 'p1-before';
                $r = $op();
                $this->trace->events[] = 'p1-after';
                return $r;
            }
        };

        $p2 = new class($trace) implements Policy {
            public function __construct(private object $trace) {}
            public function name(): string { return 'p2'; }
            public function execute(Executable $op): mixed {
                $this->trace->events[] = 'p2-before';
                $r = $op();
                $this->trace->events[] = 'p2-after';
                return $r;
            }
        };

        $chain = new Chain($p1, $p2);

        $result = $chain->execute(new class implements Executable {
            public function __invoke(): mixed { return 'ok'; }
        });

        $this->assertSame('ok', $result);
        $this->assertSame(
            ['p1-before','p2-before','p2-after','p1-after'],
            $trace->events
        );
    }
}
