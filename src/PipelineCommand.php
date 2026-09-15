<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/pipeline
 * https://github.com/php-puff/pipeline/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\Pipeline;

use Puff\Console\Contract;
use Puff\Console\Generator;
use Puff\Console\Input;
use Puff\Console\Output;

final readonly class PipelineCommand implements Contract
{
    public function __construct(
        private Generator $generator,
        private string $stub,
    ) {
    }

    public function name(): string
    {
        return 'pipeline';
    }

    public function description(): string
    {
        return 'Create a Puff pipeline';
    }

    public function usage(): string
    {
        return 'pipeline <name> [namespace] [options]';
    }

    public function valueOptions(): array
    {
        return ['namespace' => 'N'];
    }

    public function flagOptions(): array
    {
        return ['force' => 'f'];
    }

    public function execute(Input $input, Output $output): int
    {
        $name = (string) $input->argument(0);
        if ($name === '') {
            throw new \InvalidArgumentException('Pipeline name is required.');
        }

        $namespace = (string) ($input->option('namespace') ?: $input->argument(1) ?: 'Pipeline');
        $class = $this->generator->generate(
            $name,
            $namespace,
            $this->stub,
            $input->hasOption('force'),
        );
        $output->write("{$class} created successfully.");

        return 0;
    }
}
