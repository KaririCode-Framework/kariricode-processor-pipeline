<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline;

use KaririCode\Contract\Processor\Pipeline;
use KaririCode\Contract\Processor\Processor;

class ProcessorPipeline implements Pipeline
{
    private array $processors = [];

    public function addProcessor(Processor $processor): self
    {
        $this->processors[] = $processor;

        return $this;
    }

    public function process(mixed $input): mixed
    {
        return array_reduce(
            $this->processors,
            fn ($carry, Processor $processor) => $processor->process($carry),
            $input
        );
    }
}
