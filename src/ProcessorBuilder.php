<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline;

use KaririCode\Contract\Processor\ConfigurableProcessor;
use KaririCode\Contract\Processor\Pipeline;
use KaririCode\Contract\Processor\Processor;
use KaririCode\Contract\Processor\ProcessorBuilder as ProcessorBuilderContract;
use KaririCode\Contract\Processor\ProcessorRegistry;

class ProcessorBuilder implements ProcessorBuilderContract
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

    public function buildPipeline(string $context, array $processorSpecs): Pipeline
    {
        $pipeline = new ProcessorPipeline();

        foreach ($processorSpecs as $name => $config) {
            if (!$this->isValidProcessorSpec($config)) {
                continue;
            }

            $processorConfig = $this->normalizeProcessorConfig($config);
            $processor = $this->build($context, $name, $processorConfig);
            $pipeline->addProcessor($processor);
        }

        return $pipeline;
    }

    private function isValidProcessorSpec(mixed $spec): bool
    {
        return is_array($spec) || true === $spec;
    }

    private function normalizeProcessorConfig(mixed $config): array
    {
        if (is_array($config)) {
            return $config;
        }

        return [];
    }
}
