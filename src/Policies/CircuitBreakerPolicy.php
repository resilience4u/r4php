<?php
declare(strict_types=1);

namespace Resiliente\R4PHP\Policies;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Resilience4u\R4Contracts\Contracts\Executable;
use Resilience4u\R4Contracts\Contracts\Policy;
use Resilience4u\R4Contracts\Contracts\StorageAdapter;
use Resilience4u\R4Contracts\Support\Time;
use Resiliente\R4PHP\Storage\InMemoryStorage;


final class CircuitBreakerPolicy implements Policy
{
    private const STATE_CLOSED    = 'closed';
    private const STATE_OPEN      = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    public function __construct(
        private string $name,
        private StorageAdapter $storage,
        private int $failureRateThreshold = 50,
        private int $minimumSamples = 10,
        private int $timeWindowSec = 10,
        private int $openDurationMs = 30000
    ) {}

    public static function fromArray(array $a, ?StorageAdapter $storage = null): self
    {
        $storage ??= new InMemoryStorage();

        return new self(
            $a['name'] ?? 'cb',
            $storage,
            (int)($a['failureRatePct'] ?? 50),
            (int)($a['minSamples'] ?? 10),
            (int)($a['timeWindowSec'] ?? 10),
            (int)($a['openMs'] ?? 30000),
        );
    }

    public function name(): string
    {
        return "circuitBreaker:{$this->name}";
    }

    public function execute(Executable $op): mixed
    {
        $state    = $this->storage->get("{$this->name}:state", self::STATE_CLOSED);
        $openedAt = (int)$this->storage->get("{$this->name}:openedAt", 0);

        if ($state === self::STATE_OPEN) {
            if (Time::elapsedMs($openedAt) > $this->openDurationMs) {
                $this->transitionToHalfOpen();
            } else {
                throw new \RuntimeException("circuit breaker open: {$this->name}");
            }
        }

        try {
            $result = $op();

            if ($result instanceof PromiseInterface) {
                return $result->then(
                    function ($response) {
                        if ($response instanceof ResponseInterface && $response->getStatusCode() >= 500) {
                            $this->recordFailure();
                            $this->maybeReopenIfHalfOpen();
                        } else {
                            $this->recordSuccess();
                            $this->transitionToClosed();
                        }
                        return $response;
                    },
                    function ($reason) {
                        $this->recordFailure();
                        $this->maybeReopenIfHalfOpen();
                        throw $reason instanceof \Throwable
                            ? $reason
                            : new \RuntimeException(is_scalar($reason) ? (string)$reason : 'Request failed');
                    }
                );
            }

            if ($result instanceof ResponseInterface && $result->getStatusCode() >= 500) {
                $this->recordFailure();
                $this->maybeReopenIfHalfOpen();
            } else {
                $this->recordSuccess();
                $this->transitionToClosed();
            }

            return $result;

        } catch (\Throwable $e) {
            $this->recordFailure();
            $this->maybeReopenIfHalfOpen();
            throw $e;
        }
    }

    private function maybeReopenIfHalfOpen(): void
    {
        if ($this->storage->get("{$this->name}:state", self::STATE_CLOSED) === self::STATE_HALF_OPEN) {
            $this->transitionToOpen();
        }
    }

    private function recordSuccess(): void
    {
        $metrics = $this->getMetrics();
        $metrics['success'] = ($metrics['success'] ?? 0) + 1;
        $this->saveMetrics($metrics);
    }

    private function recordFailure(): void
    {
        $metrics = $this->getMetrics();
        $metrics['failures'] = ($metrics['failures'] ?? 0) + 1;
        $this->saveMetrics($metrics);

        if ($this->shouldOpen($metrics)) {
            $this->transitionToOpen();
        }
    }

    private function getMetrics(): array
    {
        $key = "{$this->name}:metrics";
        $raw = $this->storage->get($key);

        if (!is_array($raw) || !isset($raw['success'], $raw['failures'], $raw['timestamp'])) {
            $raw = ['success' => 0, 'failures' => 0, 'timestamp' => time()];
            $this->storage->set($key, $raw);
        }

        if (time() - ($raw['timestamp'] ?? 0) > $this->timeWindowSec) {
            $raw = ['success' => 0, 'failures' => 0, 'timestamp' => time()];
            $this->storage->set($key, $raw);
        }

        return $raw;
    }

    private function saveMetrics(array $metrics): void
    {
        $this->storage->set("{$this->name}:metrics", $metrics);
    }

    private function getFailureRate(array $m): float
    {
        $total = $m['success'] + $m['failures'];
        if ($total < $this->minimumSamples) {
            return 0.0;
        }
        return ($m['failures'] / $total) * 100;
    }

    private function shouldOpen(array $metrics): bool
    {
        return $this->getFailureRate($metrics) >= $this->failureRateThreshold
            && ($metrics['success'] + $metrics['failures']) >= $this->minimumSamples;
    }

    private function transitionToOpen(): void
    {
        $this->debug();
        $this->storage->set("{$this->name}:state", self::STATE_OPEN);
        $this->storage->set("{$this->name}:openedAt", Time::nowMs());
    }

    private function transitionToHalfOpen(): void
    {
        $this->debug();
        $this->storage->set("{$this->name}:state", self::STATE_HALF_OPEN);
    }

    private function transitionToClosed(): void
    {
        $this->storage->set("{$this->name}:state", self::STATE_CLOSED);
        $this->storage->set("{$this->name}:metrics", [
            'success' => 0,
            'failures' => 0,
            'timestamp' => time()
        ]);
    }

    private function debug(): void
    {
        error_log("[R4PHP] Circuit {$this->name} -> OPEN aos " . Time::nowMs());
        error_log("[R4PHP] Circuit {$this->name} -> CLOSED aos " . Time::nowMs());
    }
}
