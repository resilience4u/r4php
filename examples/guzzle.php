<?php
declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Resiliente\R4PHP\Core\Config;
use Resiliente\R4PHP\Integrations\Guzzle\R4Middleware;
use Resiliente\R4PHP\Policies\CircuitBreakerPolicy;
use Resiliente\R4PHP\Policies\RetryPolicy;
use Resiliente\R4PHP\Policies\CompositePolicy;
use Resiliente\R4PHP\Storage\FileStorage;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Este script demonstra todas as transições do CircuitBreaker:
 * 1) Closed -> Normal (status 200)
 * 2) Closed -> Open (falhas consecutivas)
 * 3) Open -> bloqueando requisições
 * 4) Half-Open -> Closed (recuperação após openMs)
 */

$config = Config::fromArray([
    'retry' => ['maxAttempts' => 1, 'initialMs' => 50, 'maxMs' => 100, 'multiplier' => 1.0, 'jitter' => false],
    'circuitBreaker' => [
        'name' => 'http',
        'failureRatePct' => 10,
        'minSamples' => 1,
        'openMs' => 8000,       // circuito aberto por 8s
        'timeWindowSec' => 30,
    ],
]);

$storage = new FileStorage();
$retry = RetryPolicy::fromArray($config->retry());
$circuit = CircuitBreakerPolicy::fromArray($config->circuitBreaker(), $storage);
$policy = new CompositePolicy($retry, $circuit);

$stack = HandlerStack::create();
$stack->push(R4Middleware::wrap($policy));

$client = new Client([
    'base_uri' => 'http://httpbin:8080',
    'handler'  => $stack,
    'timeout'  => 3.0,
]);

function showState(FileStorage $storage): void {
    $state = $storage->get('http:state', 'closed');
    $metrics = $storage->get('http:metrics', []);
    $openedAt = $storage->get('http:openedAt', 0);
    $elapsed = (int)((microtime(true) * 1000) - $openedAt);
    $failures = $metrics['failures'] ?? 0;
    $success  = $metrics['success'] ?? 0;
    $total    = max(1, $failures + $success);
    $rate     = round(($failures / $total) * 100, 1);

    if ($state === 'open') echo "🚨 Circuito ABERTO — bloqueando requisições!\n";
    elseif ($state === 'half_open') echo "🌗 Circuito HALF-OPEN — testando estabilidade…\n";
    elseif ($state === 'closed') echo "🟢 Circuito FECHADO — tráfego normal.\n";

    echo "🔎 Estado: {$state} | Sucessos={$success} | Falhas={$failures} | Taxa={$rate}% | Elapsed={$elapsed}ms\n";
}

function attempt(Client $client, string $endpoint, FileStorage $storage): void {
    echo "➡️  Chamando {$endpoint} ..." . PHP_EOL;
    try {
        $res = $client->get($endpoint);
        echo "✅ HTTP " . $res->getStatusCode() . PHP_EOL;
    } catch (\Throwable $e) {
        echo "❌ Erro: {$e->getMessage()}" . PHP_EOL;
    }
    showState($storage);
    echo str_repeat('-', 60) . PHP_EOL;
}

// 1️⃣ Fase: fluxo normal
echo PHP_EOL . "🌿 FASE 1 — FLUXO NORMAL (closed)\n";
for ($i = 1; $i <= 2; $i++) {
    attempt($client, '/status/200', $storage);
    usleep(300_000);
}

// 2️⃣ Fase: induz falhas consecutivas
echo PHP_EOL . "🔥 FASE 2 — GERANDO FALHAS PARA ABRIR O CIRCUITO\n";
for ($i = 1; $i <= 3; $i++) {
    attempt($client, '/status/500', $storage);
    usleep(300_000);
}

// 3️⃣ Fase: circuito aberto bloqueando chamadas
echo PHP_EOL . "🚫 FASE 3 — CIRCUITO ABERTO, TESTANDO BLOQUEIO\n";
for ($i = 1; $i <= 2; $i++) {
    attempt($client, '/status/200', $storage);
    usleep(300_000);
}

// 4️⃣ Fase: espera openMs para ir a half-open
echo PHP_EOL . "⏳ FASE 4 — AGUARDANDO TEMPO DE OPEN PARA TENTAR RECUPERAÇÃO (8s)\n";
sleep(9);

// 5️⃣ Fase: half-open -> fechado após sucesso
echo PHP_EOL . "💚 FASE 5 — TESTE DE RECUPERAÇÃO (half-open → closed)\n";
attempt($client, '/status/200', $storage);

echo PHP_EOL . "🏁 DEMONSTRAÇÃO FINALIZADA\n";
showState($storage);
