<?php
namespace Resiliente\R4PHP\Integrations\Guzzle;

use Psr\Http\Message\RequestInterface;
use Resiliente\R4PHP\Contracts\Executable;
use Resiliente\R4PHP\Contracts\Policy;

final class R4Middleware
{
    public static function wrap(Policy $policy): callable
    {
        return function (callable $handler) use ($policy) {
            return function (RequestInterface $request, array $options) use ($handler, $policy) {
                return $policy->execute(new class($handler, $request, $options) implements Executable {
                    public function __construct(private $handler, private $request, private $options) {}
                    public function __invoke(): mixed {
                        return ($this->handler)($this->request, $this->options);
                    }
                });
            };
        };
    }
}
