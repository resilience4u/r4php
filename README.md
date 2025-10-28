# 🛡️ R4PHP — Resilience4u for PHP

[![License](https://img.shields.io/badge/license-Apache%202.0-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-%5E8.1-8892BF.svg)](https://www.php.net/)
[![Build](https://img.shields.io/github/actions/workflow/status/resilience4u/r4php/ci.yml?label=build)](https://github.com/resilience4u/r4php/actions)
[![Coverage](https://img.shields.io/codecov/c/github/resilience4u/r4php?label=coverage)](https://codecov.io/gh/resilience4u/r4php)

> **R4PHP** é a biblioteca da família **Resilience4u** para o ecossistema PHP — um conjunto de políticas de resiliência (Retry, Circuit Breaker, Bulkhead, Time Limiter, Rate Limiter) inspiradas no [Resilience4j](https://github.com/resilience4j/resilience4j).

---

## 🚀 Visão geral

O **R4PHP** fornece uma API simples e extensível para aplicar políticas de resiliência a qualquer operação crítica do seu sistema — chamadas HTTP, queries, integrações externas, filas, etc.

Com ele, você pode:
- Controlar **quantas execuções simultâneas** são permitidas (`Bulkhead`)
- **Repetir operações com falha** (`Retry`)
- **Interromper fluxos** quando uma dependência externa estiver instável (`Circuit Breaker`)
- **Limitar o tempo máximo de execução** (`Time Limiter`)
- **Restringir taxa de requisições** (`Rate Limiter`)
- Compor todas as políticas em cadeia, aplicadas de forma declarativa via `Factory::fromConfig()`

---

## 📦 Instalação

```bash
composer require resilience4u/r4php
```

---

## 🧩 Políticas disponíveis

| Policy | Classe | Descrição | Dependência |
|--------|---------|------------|-------------|
| `RetryPolicy` | `Resiliente\R4PHP\Policies\RetryPolicy` | Repete operações com backoff exponencial ou constante | [`sts/backoff`](https://github.com/softonic/backoff) |
| `CircuitBreakerPolicy` | `Resiliente\R4PHP\Policies\CircuitBreakerPolicy` | Abre/fecha circuitos com thresholds configuráveis | [`ackintosh/ganesha`](https://github.com/ackintosh/ganesha) |
| `BulkheadPolicy` | `Resiliente\R4PHP\Policies\BulkheadPolicy` | Limita execuções simultâneas | nativo |
| `RateLimiterPolicy` | `Resiliente\R4PHP\Policies\RateLimiterPolicy` | Controla taxa de requisições por segundo | [`symfony/rate-limiter`](https://symfony.com/doc/current/rate_limiter.html) |
| `TimeLimiterPolicy` | `Resiliente\R4PHP\Policies\TimeLimiterPolicy` | Interrompe operações que excedem o tempo máximo | nativo |

---

## ⚙️ Uso básico

```php
use Resiliente\R4PHP\Contracts\Executable;
use Resiliente\R4PHP\Core\Config;
use Resiliente\R4PHP\Core\Factory;

require __DIR__ . '/../vendor/autoload.php';

// Define as políticas
$cfg = Config::fromArray([
    'rateLimiter' => ['tokensPerSecond' => 20, 'limit' => 40],
    'bulkhead'    => ['maxConcurrent' => 32],
    'timeLimiter' => ['timeoutMs' => 1500],
    'retry'       => [
        'maxAttempts' => 5,
        'initialMs'   => 200,
        'maxMs'       => 3000,
        'multiplier'  => 2.0,
        'jitter'      => true
    ],
    'circuitBreaker' => [
        'name' => 'core',
        'failureRatePct' => 50,
        'minSamples' => 10,
        'openMs' => 30000,
        'timeWindowSec' => 10,
    ],
]);

$policy = Factory::fromConfig($cfg);

// Executa uma operação resiliente
$result = $policy->execute(new class implements Executable {
    public function __invoke(): mixed {
        if (random_int(0, 1)) {
            throw new \RuntimeException('falhou');
        }
        return "ok";
    }
});

echo $result, PHP_EOL;
```

---

## 🌐 Integração com Guzzle

O **R4PHP** inclui middleware pronto para o [Guzzle HTTP Client](https://docs.guzzlephp.org/):

```php
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Resiliente\R4PHP\Core\Factory;
use Resiliente\R4PHP\Core\Config;
use Resiliente\R4PHP\Integrations\Guzzle\R4Middleware;

require __DIR__ . '/../vendor/autoload.php';

$policy = Factory::fromConfig(Config::fromArray([
    'retry' => ['maxAttempts' => 3, 'initialMs' => 100, 'maxMs' => 500],
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

$response = $client->get('/status/200');
echo $response->getStatusCode(), PHP_EOL;
```

---

## 🧠 Design

O design segue princípios inspirados em **Resilience4j**:
- Cada política implementa `Resiliente\R4PHP\Contracts\Policy`
- Operações encapsulam a execução via `Executable`
- As políticas podem ser compostas dinamicamente via `Factory`
- Suporte a configuração declarativa (`Config::fromArray` ou YAML/JSON)

---

## 🧪 Testes

Para executar a suíte de testes (PHPUnit):

```bash
composer install
composer test
```

---

## 🗺️ Roadmap

- [ ] Policy: **Fallback**
- [ ] Policy: **Cache / Result Caching**
- [ ] Integração com **OpenTelemetry**
- [ ] Exportadores de métricas (Prometheus / StatsD)
- [ ] Benchmarks de performance
- [ ] Console CLI (`r4 monitor`)
- [ ] Adaptação para frameworks (Laravel / Symfony)

---

## 👥 Contribuindo

Contribuições são muito bem-vindas!

1. Faça um fork do repositório
2. Crie uma branch com sua feature (`git checkout -b feat/nova-policy`)
3. Envie um PR descrevendo a motivação e uso

Consulte [`CONTRIBUTING.md`](CONTRIBUTING.md) e siga os padrões **PSR-12** e **SemVer**.

---

## 📜 Licença

Licenciado sob [Apache 2.0](LICENSE).  
Parte do ecossistema **[Resilience4u](https://github.com/resilience4u)**.

---

## 🌎 Projetos irmãos

| Projeto | Linguagem | Repositório |
|----------|------------|-------------|
| R4Go | Go | [resilience4u/r4go](https://github.com/resilience4u/r4go) |
| R4Js | JavaScript / Node.js | [resilience4u/r4js](https://github.com/resilience4u/r4js) |
| R4PHP | PHP | [resilience4u/r4php](https://github.com/resilience4u/r4php) |
