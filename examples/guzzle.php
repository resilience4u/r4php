<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Resiliente\R4PHP\Core\Factory;
use Resiliente\R4PHP\Core\Config;
use Resiliente\R4PHP\Integrations\Guzzle\R4Middleware;

require __DIR__ . '/../vendor/autoload.php';

$policy = Factory::fromConfig(Config::fromArray([
    'retry'          => ['maxAttempts' => 4, 'initialMs' => 100, 'maxMs' => 2000, 'jitter' => true],
    'circuitBreaker' => ['name'=>'http','failureRatePct'=>50,'minSamples'=>10,'openMs'=>15000],
]));

$stack = HandlerStack::create();
$stack->push(R4Middleware::wrap($policy));

$client = new Client(['handler' => $stack, 'timeout' => 5.0]);

$res = $client->get('https://httpbin.org/status/200');
echo $res->getStatusCode(), PHP_EOL;
