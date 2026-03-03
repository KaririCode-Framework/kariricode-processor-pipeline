<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests\Stubs;

use KaririCode\Contract\Processor\Processor;

final class StubProcessor implements Processor
{
    public function __construct(
        private readonly \Closure $transform,
    ) {
    }

    public function process(mixed $input): mixed
    {
        return ($this->transform)($input);
    }

    public static function trim(): self
    {
        return new self(static fn (mixed $v): string => trim((string) $v));
    }

    public static function uppercase(): self
    {
        return new self(static fn (mixed $v): string => strtoupper((string) $v));
    }

    public static function lowercase(): self
    {
        return new self(static fn (mixed $v): string => strtolower((string) $v));
    }

    public static function append(string $suffix): self
    {
        return new self(static fn (mixed $v): string => (string) $v . $suffix);
    }

    public static function identity(): self
    {
        return new self(static fn (mixed $v): mixed => $v);
    }
}
