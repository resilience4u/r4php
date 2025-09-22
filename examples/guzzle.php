<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Resiliente\R4PHP\Core\Factory;
use Resiliente\R4PHP\Core\Config;
use Resiliente\R4PHP\Integrations\Guzzle\R4Middleware;

require __DIR__ . '/../vendor/autoload.php';

$policy = Factory::fromConfig(Config::fromArray([
    'retry' => ['maxAttempts' => 3, 'initialMs' => 100, 'maxMs' => 500, 'multiplier' => 2.0, 'jitter' => false],
    'circuitBreaker' => [
        'name' => 'http',
        'failureRatePct' => 50,
        'minSamples' => 4,
        'openMs' => 5000,
        'timeWindowSec' => 5,
    ],
]));

$stack = HandlerStack::create();
$stack->push(R4Middleware::wrap($policy));

$client = new Client([
    'base_uri' => 'http://httpbin:8080',
    'handler'  => $stack,
    'timeout'  => 3.0,
]);

$res = $client->get('/status/200');
echo $res->getStatusCode(), PHP_EOL;