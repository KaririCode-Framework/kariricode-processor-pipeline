<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline;

use KaririCode\Contract\Processor\Pipeline;
use KaririCode\Contract\Processor\Processor;
use KaririCode\Contract\Processor\ValidatableProcessor;
use KaririCode\ProcessorPipeline\Exception\ProcessingException;

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
        try {
            return array_reduce(
                $this->processors,
                $this->executeProcessor(...),
                $input
            );
        } catch (\Exception $e) {
            throw ProcessingException::pipelineExecutionFailed();
        }
    }

    public function getProcessors(): array
    {
        return $this->processors;
    }

    public function hasProcessors(): bool
    {
        return !empty($this->processors);
    }

    public function clear(): void
    {
        $this->processors = [];
    }

    public function count(): int
    {
        return count($this->processors);
    }

    private function executeProcessor(mixed $carry, Processor $processor): mixed
    {
        try {
            // Reset the processor state if it's validatable
            if ($processor instanceof ValidatableProcessor) {
                $processor->reset();
            }

            return $processor->process($carry);
        } catch (\Exception $e) {
            throw ProcessingException::processorExecutionFailed($processor::class);
        }
    }
}
