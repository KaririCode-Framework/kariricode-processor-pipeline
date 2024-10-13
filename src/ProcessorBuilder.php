<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline;

use KaririCode\Contract\Processor\ConfigurableProcessor;
use KaririCode\Contract\Processor\Pipeline;
use KaririCode\Contract\Processor\Processor;

class ProcessorBuilder
{
    public function __construct(private ProcessorRegistry $registry)
    {
    }

    public function build(string $context, string $name, array $config = []): Processor
    {
        $processor = $this->registry->get($context, $name);
        if ($processor instanceof ConfigurableProcessor && !empty($config)) {
            $processor->configure($config);
        }

        return $processor;
    }

    public function buildPipeline(string $context, array $processorSpecs): Pipeline
    {
        $pipeline = new ProcessorPipeline();
        foreach ($processorSpecs as $name => $config) {
            if (is_int($name)) {
                $name = $config;
                $config = [];
            }
            $processor = $this->build($context, $name, $config);
            $pipeline->addProcessor($processor);
        }

        return $pipeline;
    }
}
