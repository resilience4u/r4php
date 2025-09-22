<?php
namespace Resiliente\R4PHP\Integrations\Guzzle;

use Psr\Http\Message\RequestInterface;
use Resiliente\R4PHP\Contracts\Executable;
use Resiliente\R4PHP\Contracts\Policy;

final class R4Middleware
{
    public static function wrap(Policy $policy): callable
    {
        return function (RequestInterface $request, array $options) use ($handler, $policy) {
            return $policy->execute(new class($handler, $request, $options) implements Executable {
                /** @var callable */
                private $handler;
                private RequestInterface $request;
                /** @var array<string,mixed> */
                private array $options;
                public function __construct(callable $handler, RequestInterface $request, array $options) {
                    $this->handler = $handler;
                    $this->request = $request;
                    $this->options = $options;
                }
                public function __invoke(): mixed {
                    return ($this->handler)($this->request, $this->options);
                }
            });
        };
    }
}
