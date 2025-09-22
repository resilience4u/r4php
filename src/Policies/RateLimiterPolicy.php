<?php
namespace Resiliente\R4PHP\Policies;

use Resiliente\R4PHP\Contracts\Executable;
use Resiliente\R4PHP\Contracts\Policy;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class RateLimiterPolicy implements Policy
{
    public function __construct(private RateLimiterFactory $factory, private string $name) {}

    public static function fromArray(array $a): self
    {
        $storage = new InMemoryStorage();

        $perSecond = (int)($a['tokensPerSecond'] ?? $a['ratePerSecond'] ?? 10);
        if ($perSecond < 1) { $perSecond = 1; }
        $limit = (int)($a['limit'] ?? $a['burst'] ?? $perSecond);

        $cfg = [
            'id'     => $a['limiter'] ?? 'default',
            'policy' => 'token_bucket',
            'limit'  => $limit,
            'rate'   => [
                'interval' => '1 second',
                'amount'   => $perSecond,
            ],
        ];

        $factory = new RateLimiterFactory($cfg, $storage);
        return new self($factory, $cfg['id']);
    }

    public function name(): string { return "rateLimiter:{$this->name}"; }

    public function execute(Executable $op): mixed
    {
        $limiter = $this->factory->create($this->name);
        $token = $limiter->consume(1);
        if (!$token->isAccepted()) {
            throw new \RuntimeException('Rate limit exceeded');
        }
        return $op();
    }
}
