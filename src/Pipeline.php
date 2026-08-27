<?php

/*
 * PHP Fiber Framework
 * https://github.com/php-puff/pipeline
 * https://github.com/php-puff/pipeline/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\Pipeline;

use Closure;
use Psr\Container\ContainerInterface;

final class Pipeline implements PipelineInterface
{
    private mixed $passable = null;
    private bool $sent = false;

    /** @var list<callable|object|string> */
    private array $pipes = [];

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function send(mixed $passable): self
    {
        $pipeline = clone $this;
        $pipeline->passable = $passable;
        $pipeline->sent = true;
        return $pipeline;
    }

    /** @param array<array-key, callable|object|string> $pipes */
    public function through(array $pipes): self
    {
        $pipeline = clone $this;
        $pipeline->pipes = \array_values($pipes);
        return $pipeline;
    }

    public function then(callable $destination): mixed
    {
        if (!$this->sent) {
            throw new \LogicException('Pipeline::send() must be called before Pipeline::then().');
        }

        $next = Closure::fromCallable($destination);
        for ($index = \count($this->pipes) - 1; $index >= 0; --$index) {
            $pipe = $this->pipes[$index];
            $next = fn (mixed $passable): mixed => $this->carry($pipe, $passable, $next);
        }
        return $next($this->passable);
    }

    private function carry(callable|object|string $pipe, mixed $passable, Closure $next): mixed
    {
        $parameters = [];
        if (\is_string($pipe) && !\is_callable($pipe)) {
            [$name, $arguments] = \array_pad(\explode(':', $pipe, 2), 2, '');
            $name = \trim($name);
            if ($name === '') {
                throw new \InvalidArgumentException('Pipeline service name must not be empty.');
            }
            $parameters = $arguments === ''
                ? []
                : \array_map(\trim(...), \explode(',', $arguments));
            $pipe = $this->container->get($name);
        }

        if (\is_callable($pipe)) {
            return $pipe($passable, $next, ...$parameters);
        }
        $handler = [$pipe, 'handle'];
        if (!\is_callable($handler)) {
            throw new \InvalidArgumentException('Pipeline stage must be callable or expose a public handle() method.');
        }
        return $handler($passable, $next, ...$parameters);
    }
}
