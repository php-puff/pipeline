<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/pipeline
 * https://github.com/php-puff/pipeline/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\Pipeline;

use Puff\Console\CommandProvider;
use Puff\Console\Contract;
use Puff\Console\Generator;
use Psr\Container\ContainerInterface;

final class ConsoleProvider implements CommandProvider
{
    /** @return iterable<Contract> */
    public function commands(string $root, ContainerInterface $container): iterable
    {
        yield new PipelineCommand(new Generator($root), \dirname(__DIR__) . '/stub/pipeline.stub');
    }
}
