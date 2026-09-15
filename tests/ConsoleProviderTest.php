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
use Puff\Console\Input;
use Puff\Console\Output;
use Puff\Pipeline\ConsoleProvider;

final class ConsoleProviderTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = \sys_get_temp_dir() . '/puff-pipeline-' . \bin2hex(\random_bytes(6));
        self::assertTrue(\mkdir($this->root));
    }

    protected function tearDown(): void
    {
        $file = $this->root . '/pipeline/Auth.php';
        if (\is_file($file)) {
            \unlink($file);
            \rmdir(\dirname($file));
            \rmdir(\dirname($file, 2));

            return;
        }
        \rmdir($this->root);
    }

    public function testRegistersPipelineGenerator(): void
    {
        $commands = \iterator_to_array((new ConsoleProvider())->commands(__DIR__, $this->container()));

        self::assertCount(1, $commands);
        self::assertSame('pipeline', $commands[0]->name());
    }

    public function testGeneratesPipelineInTheDefaultNamespace(): void
    {
        $command = \iterator_to_array((new ConsoleProvider())->commands($this->root, $this->container()))[0];
        $stdout = \fopen('php://memory', 'w+');
        self::assertIsResource($stdout);
        $output = new Output($stdout);

        self::assertSame(0, $command->execute(Input::parse(['Auth']), $output));
        self::assertFileExists($this->root . '/pipeline/Auth.php');
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
