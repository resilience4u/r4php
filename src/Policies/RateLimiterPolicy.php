<?php
namespace Resiliente\R4PHP\Policies;

use Resiliente\R4PHP\Contracts\Executable;
use Resiliente\R4PHP\Contracts\Policy;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class RateLimiterPolicy implements Policy
{
    public function __construct(private $limiterFactory, private string $name) {}

    public static function fromArray(array $a): self
    {
        $storage = new InMemoryStorage();
        $cfg = [
            'id' => $a['limiter'] ?? 'default',
            'policy' => 'token_bucket',
            'limit' => (int)($a['tokensPerSecond'] ?? 10),
            'rate' => ['interval' => '1 second', 'amount' => (int)($a['tokensPerSecond'] ?? 10)],
            'burst' => (int)($a['burst'] ?? 20),
        ];
        $factory = new RateLimiterFactory($cfg, $storage);
        return new self($factory, $cfg['id']);
    }

    public function name(): string { return "rateLimiter:{$this->name}"; }

    public function execute(Executable $op): mixed
    {
        $limiter = $this->limiterFactory->create($this->name);
        $token = $limiter->consume(1);
        if (!$token->isAccepted()) {
            throw new \RuntimeException('Rate limit exceeded');
        }
        return $op();
    }
}
