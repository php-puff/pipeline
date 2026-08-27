<?php

/*
 * PHP Fiber Framework
 * https://github.com/php-puff/pipeline
 * https://github.com/php-puff/pipeline/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\Pipeline\Tests;

use Closure;
use Fiber;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Puff\Pipeline\Pipeline;

final class PipelineTest extends TestCase
{
    public function testRunsResolvedAndCallableStagesInOrder(): void
    {
        $container = new TestContainer([
            'stage' => new class () {
                public function handle(string $value, Closure $next, string $suffix): string
                {
                    return $next($value . 'A' . $suffix);
                }
            },
        ]);

        $result = (new Pipeline($container))->send('x')->through([
            'stage: B',
            static fn (string $value, Closure $next): string => $next($value . 'C'),
        ])->then(static fn (string $value): string => $value . 'D');

        self::assertSame('xABCD', $result);
    }

    public function testRunsCallableResolvedFromContainer(): void
    {
        $pipeline = new Pipeline(new TestContainer([
            'callable' => static fn (string $value, Closure $next, string $suffix): string => $next($value . $suffix),
        ]));

        self::assertSame('AB', $pipeline->send('A')->through(['callable:B'])->then(
            static fn (string $value): string => $value,
        ));
    }

    public function testRunsInvokableStageAndDestination(): void
    {
        $stage = new class () {
            public function __invoke(string $value, Closure $next): string
            {
                return $next($value . 'B');
            }
        };
        $destination = new class () {
            public function __invoke(string $value): string
            {
                return $value . 'C';
            }
        };

        self::assertSame('ABC', (new Pipeline(new TestContainer()))
            ->send('A')
            ->through([$stage])
            ->then($destination));
    }

    public function testStageCanStopPipeline(): void
    {
        $result = (new Pipeline(new TestContainer()))
            ->send('start')
            ->through([static fn (): string => 'stopped'])
            ->then(static fn (): string => 'destination');

        self::assertSame('stopped', $result);
    }

    public function testRejectsObjectWithoutPublicHandle(): void
    {
        $stage = new class () {
            public function execute(): void
            {
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('public handle()');
        (new Pipeline(new TestContainer()))->send('value')->through([$stage])->then(static fn (): null => null);
    }

    public function testRequiresPassableBeforeExecution(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('send() must be called');
        (new Pipeline(new TestContainer()))->then(static fn (): null => null);
    }

    public function testPropagatesStageExceptions(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('failed');
        (new Pipeline(new TestContainer()))
            ->send('value')
            ->through([static function (): never {
                throw new \RuntimeException('failed');
            }])
            ->then(static fn (): null => null);
    }

    public function testBasePipelineIsIsolatedAcrossFibers(): void
    {
        $base = (new Pipeline(new TestContainer()))->through([
            static function (string $value, Closure $next): string {
                Fiber::suspend($value);
                return $next($value);
            },
        ]);
        $first = new Fiber(fn (): string => $base->send('first')->then(static fn (string $value): string => $value));
        $second = new Fiber(fn (): string => $base->send('second')->then(static fn (string $value): string => $value));

        self::assertSame('first', $first->start());
        self::assertSame('second', $second->start());
        $first->resume();
        $second->resume();
        self::assertSame('first', $first->getReturn());
        self::assertSame('second', $second->getReturn());
    }
}

final readonly class TestContainer implements ContainerInterface
{
    /** @param array<string, mixed> $services */
    public function __construct(private array $services = [])
    {
    }

    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new TestNotFoundException("Service [{$id}] is not defined.");
        }
        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return \array_key_exists($id, $this->services);
    }
}

final class TestNotFoundException extends \RuntimeException implements NotFoundExceptionInterface
{
}
