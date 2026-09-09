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
use Puff\Console\GenerateCommand;
use Puff\Console\Generator;

final class ConsoleProvider implements CommandProvider
{
    /** @return iterable<Contract> */
    public function commands(string $root): iterable
    {
        yield new GenerateCommand('pipeline', 'App\\Pipeline', \dirname(__DIR__) . '/stub/pipeline.stub', new Generator($root));
    }
}
