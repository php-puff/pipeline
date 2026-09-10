<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/pipeline
 * https://github.com/php-puff/pipeline/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\Pipeline\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Puff\Pipeline\ConsoleProvider;

final class ConsoleProviderTest extends TestCase
{
    public function testRegistersPipelineGenerator(): void
    {
        $commands = \iterator_to_array((new ConsoleProvider())->commands(__DIR__, $this->container()));

        self::assertCount(1, $commands);
        self::assertSame('pipeline', $commands[0]->name());
    }

    private function container(): ContainerInterface
    {
        return new class implements ContainerInterface {
            public function get(string $id): mixed
            {
                throw new \RuntimeException("Service [{$id}] is not available.");
            }

            public function has(string $id): bool
            {
                return false;
            }
        };
    }
}
