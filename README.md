[![Latest Stable Version](http://poser.pugx.org/kipchak/core/v)](https://packagist.org/packages/kipchak/core) [![Total Downloads](http://poser.pugx.org/kipchak/core/downloads)](https://packagist.org/packages/kipchak/core) [![Latest Unstable Version](http://poser.pugx.org/kipchak/core/v/unstable)](https://packagist.org/packages/kipchak/core) [![License](http://poser.pugx.org/kipchak/core/license)](https://packagist.org/packages/kipchak/core) [![PHP Version Require](http://poser.pugx.org/kipchak/core/require/php)](https://packagist.org/packages/kipchak/core)

<img src=".mamluk/logo.png" width="96" alt="Kipchak by Mamluk" title="Kipchak by Mamluk - an API Development Kit" />

# Kipchak by Mamluk

Kipchak is a PHP 8.4+ API Development Kit (ADK) built on [Slim Framework v4](https://www.slimframework.com), designed for rapid API development and optimised for [FrankenPHP](https://frankenphp.dev/) in worker mode.

The core package provides the bootstrap layer, base classes, interfaces, and HTTP helpers. Drivers and middlewares are installed as separate packages alongside it.

**Full documentation: <https://kipchak.dev>**

---

## Install

```bash
composer require kipchak/core
```

To get started with a complete working project, use the [starter template](https://kipchak.dev/docs/getting-started):

```bash
composer create-project kipchak/starter [api-name] -s dev
```

---

## What's in the Core

| Class / Interface | Description |
|---|---|
| `Kipchak\Core\Api` | Bootstrap entry point |
| `Kipchak\Core\Components\Controllers\Base` | Base controller with container and logger |
| `Kipchak\Core\Components\Http\Response` | JSON, redirect, and raw response helpers |
| `Kipchak\Core\Components\Http\Request` | Query param and attribute helpers |
| `Kipchak\Core\Interfaces\Driver` | Interface all drivers must implement |
| `Kipchak\Core\Interfaces\Middleware` | Interface all middlewares must implement |
| `Kipchak\Core\Functions` | Global helper functions: `env()`, `config()`, `dump()` |

---

## Bootstrapping

### `Api::boot()`

Bootstraps a full HTTP API. Loads drivers, middlewares, and routes from the project root — then returns a configured `App` instance ready to run.

```php
// html/index.php (FrankenPHP worker mode)
$app = Kipchak\Core\Api::boot();
$app->addBodyParsingMiddleware();

frankenphp_handle_request(function () use ($app) {
    $app->run();
});
```

### Work after the response

`App::onTerminate()` registers work to run after the response has been sent, such as exporting telemetry, so
that it does not delay the client. The worker loop calls `$app->terminate()` after each request (the starter
project does from 3.19); a callback that throws does not stop the others, and its error goes to `error_log()`.

```php
$app->onTerminate(fn () => $metrics->flush());

for (;;) {
    $keepRunning = frankenphp_handle_request($handler);
    $app->terminate();
    if (!$keepRunning) break;
}
```

### `Api::bootCli()`

Bootstraps a CLI application. Loads drivers only — no middlewares or routes — and returns the `App` instance.

```php
$app = Kipchak\Core\Api::bootCli();
```

### Boot sequence

`Api::boot()` performs these steps in order:

1. Creates a `php-di/php-di` DI container
2. Creates the Slim `App` instance and adds routing middleware
3. Loads all `.php` files from `drivers/` (your driver registrations)
4. Loads all `.php` files from `middlewares/` (your middleware registrations)
5. Loads all `.php` files from `routes/` (your route definitions)

---

## HTTP Helpers

### `Http\Response`

All response methods set `Content-Type`, `X-Powered-By`, and the HTTP status code.

**JSON response** — wraps data in the standard [response envelope](#response-envelope):

```php
use Kipchak\Core\Components\Http;

return Http\Response::json($response, ['key' => 'value'], 200);

// With HTTP caching headers:
return Http\Response::json($response, $data, 200, cache: true, cacheTTL: 3600);
```

**Redirect:**

```php
return Http\Response::redirect($response, 'https://example.com', 302);
```

**Raw text response:**

```php
return Http\Response::raw($response, 'plain text', 200);
```

### `Http\Request`

```php
use Kipchak\Core\Components\Http;

// Read a query parameter (?name=value), returns null if absent
$value = Http\Request::getQueryParam($request, 'name');

// Read a request attribute (set by middleware)
$token = Http\Request::getAttribute($request, 'token');
```

### Response Envelope

`Http\Response::json()` always wraps data in this envelope:

```json
{
    "code": 200,
    "status": "OK",
    "data": { ... }
}
```

Pass an optional `meta` array (available since 3.0) for pagination, counts, or other endpoint metadata. It sits between `status` and `data`:

```php
return Http\Response::json($response, $items, 200, meta: ['total' => 128, 'page' => 1]);
```

```json
{
    "code": 200,
    "status": "OK",
    "meta": { "total": 128, "page": 1 },
    "data": [ ... ]
}
```

---

## Controllers

Controllers extend `Kipchak\Core\Components\Controllers\Base`, which injects:

- `$this->container` — the DI container (`Psr\Container\ContainerInterface`)
- `$this->logger` — a `Monolog\Logger` instance

```php
namespace Api\Controllers\v1;

use Kipchak\Core\Components\Controllers;
use Kipchak\Core\Components\Http;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Example extends Controllers\Base
{
    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->logger->info('Handling request');

        return Http\Response::json($response, ['hello' => 'world'], 200);
    }
}
```

---

## Helper Functions

Defined in `Kipchak\Core\Functions` and available globally once `Api::boot()` is called.

### `env(string $name, string $default): string`

Reads an environment variable, returning `$default` if it is not set.

```php
use function Kipchak\Core\env;

$host = env('DB_HOST', 'localhost');
```

### `config(string $file): array`

Loads a PHP config file from the project's `config/` directory by name (without the `.php` extension). Returns an empty array if the file does not exist.

```php
use function Kipchak\Core\config;

$settings = config('kipchak.api'); // loads config/kipchak.api.php
```

### `dump(mixed $data): ResponseInterface`

Renders a variable to the HTTP response. Use this instead of `var_dump()` or `print_r()`, which do not work in FrankenPHP worker mode. Can only be called from inside a controller or middleware.

```php
use function Kipchak\Core\dump;

return dump($someVariable);
```

---

## Driver Interface

All drivers implement `Kipchak\Core\Interfaces\Driver`:

```php
interface Driver
{
    public static function initialise(ContainerInterface $container, ?string $configFileName = null);
    public static function set(?string $configFileName = null);
    public static function get(?string $configKey = null);
}
```

- `initialise()` — stores the container reference and calls `set()`
- `set()` — reads config and registers service(s) into the DI container
- `get()` — retrieves a registered service from the container by key

Container keys follow the convention `drivers.<category>.<name>.<connection>`, for example `drivers.cache.memcached.default`.

### Minimal driver example

```php
namespace My\Driver;

use Kipchak\Core\Interfaces;
use Kipchak\Driver\Config\Config;
use Psr\Container\ContainerInterface;

class MyDriver implements Interfaces\Driver
{
    protected static ContainerInterface $container;

    public static function initialise(ContainerInterface $container, ?string $configFileName = null): void
    {
        self::$container = $container;
        self::set($configFileName);
    }

    public static function set(?string $configFileName = null): void
    {
        $config = Config::get($configFileName ?? 'kipchak.mydriver');
        self::$container->set('drivers.mydriver.default', fn() => new MyService($config));
    }

    public static function get(?string $configKey = null): MyService
    {
        return self::$container->get('drivers.mydriver.' . ($configKey ?? 'default'));
    }
}
```

Register it in `drivers/drivers.php`:

```php
MyDriver::initialise($container);
```

---

## Middleware Interface

All middlewares implement `Kipchak\Core\Interfaces\Middleware`:

```php
interface Middleware
{
    public static function initialise(App $app);
}
```

`initialise()` receives the `App` instance and calls `$app->add()` to register the middleware globally, or conditionally based on config.

### Minimal middleware example

```php
namespace My\Middleware;

use Kipchak\Core\Interfaces;
use Kipchak\Core\Components\App;

class MyMiddleware implements Interfaces\Middleware
{
    public static function initialise(App $app): void
    {
        $app->add(new MyMiddlewareHandler());
    }
}
```

Register it in `middlewares/middlewares.php`:

```php
MyMiddleware::initialise($app);
```

---

## Available Drivers

The following driver packages are available. Install any of them with `composer require`:

### Community (MIT)

| Package | Description |
|---|---|
| `kipchak/driver-config` | Loads PHP config files from `config/`. Always required. |
| `kipchak/driver-logger` | PSR-3 logger (Monolog) |
| `kipchak/driver-http` | PSR-18 HTTP client (Laravel HTTP) |
| `kipchak/driver-filecache` | File-based cache (Symfony Cache) |
| `kipchak/driver-memcached` | Memcached pools (Symfony Cache) |
| `kipchak/driver-couchdb` | Apache CouchDB 3.x document store |
| `kipchak/driver-doctrine` | Doctrine DBAL and ORM for MySQL / MariaDB |
| `kipchak/driver-rabbitmq` | RabbitMQ publisher and subscriber |
| `kipchak/driver-s3` | S3-compatible object storage |
| `kipchak/driver-git` | Git repository operations over HTTPS |
| `kipchak/driver-openai` | OpenAI chat completions with default model injection |
| `kipchak/driver-anthropic` | Anthropic Claude messages with default model injection |
| `kipchak/driver-cloudflare-dns` | Cloudflare DNS zone and record management |
| `kipchak/driver-cloudns` | ClouDNS zone and record management |

### Enterprise

| Package | Description |
|---|---|
| `kipchak/driver-valkey` | Redis / Valkey cache (standalone, sentinel, cluster, replica) |
| `kipchak/driver-opensearch` | OpenSearch client (multi-connection, multiple auth types) |
| `kipchak/driver-kafka` | Kafka producer and consumer (`ext-rdkafka`) |
| `kipchak/driver-oauth2-token` | OAuth 2.0 client credentials token management |

---

## Available Middlewares

| Package | Description |
|---|---|
| `kipchak/middleware-error` | Standardised JSON error responses for unhandled exceptions |
| `kipchak/middleware-auth-key` | API key validation via header or query parameter |
| `kipchak/middleware-auth-jwks` | Bearer JWT validation against a remote JWKS URI |
| `kipchak/middleware-auth-mtls` | Mutual TLS client certificate validation |
| `kipchak/middleware-subashi` | WAF with OWASP rules, rate limiting, IP/header allow/blocklists |
| `kipchak/middleware-subashi-pro` | Enterprise WAF with RBAC, AuthN/AuthZ, and JWKS |
| `kipchak/middleware-auth-jwt` | JWT validation with a local public key (Enterprise) |

---

## Enterprise Edition

Enterprise drivers and middlewares are available with a commercial licence. To enquire, contact us at <https://mamluk.net/contact>.

---

## Technical Stack

| Component       | Provider                |
|-----------------|-------------------------|
| Framework       | Slim Framework          |
| DI Container    | PHP-DI                  |
| ORM             | Doctrine                |
| HTTP Client     | Laravel HTTP            |
| Logging         | Monolog                 |
| Object Mapping  | CuyZ Valinor            |
| OpenAPI Docs    | Zircote Swagger PHP     |
| CouchDB Client  | Mamluk CouchDB Client   |
| RabbitMQ Client | Tavshan by Mamluk       |
| Cache           | Symfony Cache           |

---

## Migrating from 2.x

- Adds support for PHP 8.5; PHP 8.4 remains supported
- `cuyz/valinor` upgraded from 1.x to 2.x, and `zircote/swagger-php` from 5.x to 6.x — only affects you if you use these libraries directly (the standard Valinor mapping flow and Swagger PHP attributes are unchanged)
- `Http\Response::json()` gains an optional `meta` argument (additive, backward compatible)
- Drivers and middlewares now accept `kipchak/core: ^2 || ^3`, so core 3.x can be adopted incrementally
- See the [full upgrade guide](https://kipchak.dev/docs/upgrades) for the breaking `driver-http`, `driver-anthropic`, and `driver-openai` 2.0 releases

## Migrating from 1.x

- Package name changed from `mamluk/kipchak` to `kipchak/core`
- Namespace changed from `Mamluk\Kipchak` to `Kipchak`
- The project is now modular: drivers and middlewares are separate packages
- Interfaces (`Driver`, `Middleware`) have been added for writing extensions
- The starter project has been updated to reflect the new structure

## Migrating from 0.x

- Session management has been removed (APIs should be stateless)
- Zircote Swagger PHP added for generating OpenAPI documentation from PHP attributes
- Doctrine upgraded to 3.5+, which may introduce breaking changes if you extended core Doctrine classes without explicit return types

---

## About

- **Company**: [Mamluk](https://mamluk.net)
- **Name origin**: Named after the Turkic Kipchak family which founded the Mamluk Sultanate of Cairo in 1250
- **Documentation**: <https://kipchak.dev>
- **License**: MIT
