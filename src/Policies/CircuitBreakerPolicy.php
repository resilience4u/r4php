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
                        } else {
                            $this->recordSuccess();
                            if ($this->storage->get("{$this->name}:state", self::STATE_CLOSED) === self::STATE_HALF_OPEN) {
                                $this->transitionToClosed();
                            }
                        }
                        return $response;
                    },
                    function ($reason) {
                        $this->recordFailure();
                        throw $reason instanceof \Throwable
                            ? $reason
                            : new \RuntimeException(is_scalar($reason) ? (string)$reason : 'Request failed');
                    }
                );
            }

            if ($result instanceof ResponseInterface && $result->getStatusCode() >= 500) {
                $this->recordFailure();
            } else {
                $this->recordSuccess();
                if ($this->storage->get("{$this->name}:state", self::STATE_CLOSED) === self::STATE_HALF_OPEN) {
                    $this->transitionToClosed();
                }
            }

            return $result;

        } catch (\Throwable $e) {
            $this->recordFailure();
            throw $e;
        }
    }

    private function recordSuccess(): void
    {
        $key = "{$this->name}:metrics:success";
        $this->storage->increment($key, 1, $this->timeWindowSec);
    }

    private function recordFailure(): void
    {
        $state = $this->storage->get("{$this->name}:state", self::STATE_CLOSED);

        if ($state === self::STATE_HALF_OPEN) {
            $this->transitionToOpen();
            return;
        }

        if ($state === self::STATE_OPEN) {
            return;
        }

        $failureKey = "{$this->name}:metrics:failures";
        $successKey = "{$this->name}:metrics:success";

        $newFailures = $this->storage->increment($failureKey, 1, $this->timeWindowSec);
        $successes = (int)$this->storage->get($successKey, 0);

        $metrics = [
            'failures' => $newFailures,
            'success' => $successes,
        ];

        if ($this->shouldOpen($metrics)) {
            $this->transitionToOpen();
        }
    }

    private function getMetrics(): array
    {
        $keys = [
            "{$this->name}:metrics:success",
            "{$this->name}:metrics:failures",
        ];

        $values = $this->storage->getMultiple($keys);
        return [
            'success' => (int)($values["{$this->name}:metrics:success"] ?? 0),
            'failures' => (int)($values["{$this->name}:metrics:failures"] ?? 0),
        ];
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
        $this->debug('OPEN');
        $this->storage->set("{$this->name}:state", self::STATE_OPEN);
        $this->storage->set("{$this->name}:openedAt", Time::nowMs());
    }

    private function transitionToHalfOpen(): void
    {
        $this->debug('HALF_OPEN');
        $this->storage->set("{$this->name}:state", self::STATE_HALF_OPEN);
    }

    private function transitionToClosed(): void
    {
        $this->debug('CLOSED');
        $this->storage->set("{$this->name}:state", self::STATE_CLOSED);
        $this->storage->delete("{$this->name}:metrics:failures");
        $this->storage->delete("{$this->name}:metrics:success");
    }

    private function debug(string $toState): void
    {
        error_log("[R4PHP] Circuit {$this->name} transitioned to {$toState} at " . Time::nowMs());
    }
}
