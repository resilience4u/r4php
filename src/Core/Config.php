<?php
namespace Resiliente\R4PHP\Core;

/**
 * Lightweight config holder used by Factory.
 * All sections are associative arrays.
 */
final class Config
{
    /** @var array<string, mixed> */
    private array $retry;

    /** @var array<string, mixed> */
    private array $circuitBreaker;

    /** @var array<string, mixed> */
    private array $rateLimiter;

    /** @var array<string, mixed> */
    private array $timeLimiter;

    /** @var array<string, mixed> */
    private array $bulkhead;

    /**
     * @param array<string, mixed> $retry
     * @param array<string, mixed> $circuitBreaker
     * @param array<string, mixed> $rateLimiter
     * @param array<string, mixed> $timeLimiter
     * @param array<string, mixed> $bulkhead
     */
    private function __construct(
        array $retry = [],
        array $circuitBreaker = [],
        array $rateLimiter = [],
        array $timeLimiter = [],
        array $bulkhead = [],
    ) {
        $this->retry          = $retry;
        $this->circuitBreaker = $circuitBreaker;
        $this->rateLimiter    = $rateLimiter;
        $this->timeLimiter    = $timeLimiter;
        $this->bulkhead       = $bulkhead;
    }

    /**
     * @param array<string, mixed> $a
     */
    public static function fromArray(array $a): self
    {
        /** @var array<string, mixed> $retry */
        $retry = isset($a['retry']) && is_array($a['retry']) ? $a['retry'] : [];

        /** @var array<string, mixed> $cb */
        $cb = isset($a['circuitBreaker']) && is_array($a['circuitBreaker']) ? $a['circuitBreaker'] : [];

        /** @var array<string, mixed> $rl */
        $rl = isset($a['rateLimiter']) && is_array($a['rateLimiter']) ? $a['rateLimiter'] : [];

        /** @var array<string, mixed> $tl */
        $tl = isset($a['timeLimiter']) && is_array($a['timeLimiter']) ? $a['timeLimiter'] : [];

        /** @var array<string, mixed> $bh */
        $bh = isset($a['bulkhead']) && is_array($a['bulkhead']) ? $a['bulkhead'] : [];

        return new self($retry, $cb, $rl, $tl, $bh);
    }

    /** @return array<string, mixed> */
    public function retry(): array { return $this->retry; }

    /** @return array<string, mixed> */
    public function circuitBreaker(): array { return $this->circuitBreaker; }

    /** @return array<string, mixed> */
    public function rateLimiter(): array { return $this->rateLimiter; }

    /** @return array<string, mixed> */
    public function timeLimiter(): array { return $this->timeLimiter; }

    /** @return array<string, mixed> */
    public function bulkhead(): array { return $this->bulkhead; }
}
