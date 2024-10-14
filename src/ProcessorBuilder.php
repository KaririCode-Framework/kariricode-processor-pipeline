<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline;

use KaririCode\Contract\Processor\ConfigurableProcessor;
use KaririCode\Contract\Processor\Pipeline;
use KaririCode\Contract\Processor\Processor;
use KaririCode\Contract\Processor\ProcessorRegistry;

class ProcessorBuilder
{
    public function __construct(private readonly ProcessorRegistry $registry)
    {
    }

    public function build(string $context, string $name, array $processorConfig = []): Processor
    {
        $processor = $this->registry->get($context, $name);
        if ($processor instanceof ConfigurableProcessor && !empty($processorConfig)) {
            $processor->configure($processorConfig);
        }

        return $processor;
    }

    /**
     * @param array<int|string, string|array<string, mixed>> $processorSpecs
     */
    public function buildPipeline(string $context, array $processorSpecs): Pipeline
    {
        $pipeline = new ProcessorPipeline();
        foreach ($processorSpecs as $key => $spec) {
            $processorName = $this->resolveProcessorName($key, $spec);
            $processorConfig = $this->resolveProcessorConfig($key, $spec);
            $processor = $this->build($context, $processorName, $processorConfig);
            $pipeline->addProcessor($processor);
        }

        return $pipeline;
    }

    private function isUnnamedProcessor(int|string $key): bool
    {
        return is_int($key);
    }

    private function resolveProcessorName(int|string $key, string|array $spec): string
    {
        return $this->isUnnamedProcessor($key) ? (string) $spec : (string) $key;
    }

    private function resolveProcessorConfig(int|string $key, string|array $spec): array
    {
        return $this->isUnnamedProcessor($key) ? [] : (array) $spec;
    }
}
