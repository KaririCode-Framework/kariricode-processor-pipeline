<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline;

use KaririCode\Contract\Processor\Pipeline;
use KaririCode\Contract\Processor\Processor;
use KaririCode\Contract\Processor\ValidatableProcessor;

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
            static function ($carry, Processor $processor): mixed {
                // Reset the processor's state if it's a ValidatableProcessor
                if ($processor instanceof ValidatableProcessor) {
                    $processor->reset();
                }

                return $processor->process($carry);
            },
            $input
        );
    }
}
