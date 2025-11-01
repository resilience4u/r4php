<?php
declare(strict_types=1);

namespace Resiliente\R4PHP\Integrations\Guzzle;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Resilience4u\R4Contracts\Contracts\Executable;
use Resilience4u\R4Contracts\Contracts\Policy;

final class R4Middleware
{
    /**
     * Wraps a Policy (e.g., Retry + CircuitBreaker) into a Guzzle middleware.
     */
    public static function wrap(Policy $policy): callable
    {
        return function (callable $handler) use ($policy): callable {
            return function (RequestInterface $request, array $options) use ($handler, $policy): PromiseInterface {
                return $policy->execute(new class($handler, $request, $options) implements Executable {
                    public function __construct(
                        private $handler,
                        private RequestInterface $request,
                        private array $options
                    ) {}

                    public function __invoke(): mixed
                    {
                        return ($this->handler)($this->request, $this->options);
                    }
                });
            };
        };
    }
}
