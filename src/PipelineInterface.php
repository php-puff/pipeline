<?php

/*
 * PHP Fiber Framework
 * https://github.com/php-puff/pipeline
 * https://github.com/php-puff/pipeline/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\Pipeline;

interface PipelineInterface
{
    public function send(mixed $passable): self;

    /** @param array<array-key, callable|object|string> $pipes */
    public function through(array $pipes): self;

    public function then(callable $destination): mixed;
}
