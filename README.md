# Puff Pipeline

Lightweight, framework-independent pipeline with optional PSR-11 service resolution.

Installing the component registers its application pipeline generator with Puff Console:

```bash
./puff pipeline Authenticate
```

The generated stage uses `mixed` input and output so the component remains independent of HTTP.

```php
$result = (new Pipeline($container))
    ->send($request)
    ->through([
        Authentication::class,
        static fn ($request, $next) => $next($request),
    ])
    ->then(static fn ($request) => $controller($request));
```

`send()` and `through()` return cloned pipeline instances. A configured pipeline can therefore be reused safely by concurrent Fibers without sharing the current passable or stage list.

## Stages

A stage may be:

- A Closure or another callable.
- An invokable object.
- An object exposing a public `handle()` method.
- A PSR-11 service name resolving to any of the above.

Each stage receives the passable followed by the next Closure:

```php
final class Authentication
{
    public function handle(Request $request, Closure $next): mixed
    {
        return $next($request);
    }
}
```

String stages support a small comma-separated parameter syntax:

```php
Authentication::class . ':admin, enabled'
```

The stage receives `admin` and `enabled` as trimmed strings after the next Closure. Commas and colons cannot be escaped; use a configured object or Closure for structured parameters.

A stage may return without calling `$next` to stop the pipeline. Exceptions are not intercepted and propagate to the caller. `send()` must be called before `then()`.

Pipeline instances are Fiber-safe, but stateful stage objects must still be scoped per Fiber or implement their own synchronization.
