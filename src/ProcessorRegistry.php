<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline;

use KaririCode\Contract\DataStructure\Map;
use KaririCode\Contract\Processor\Processor;
use KaririCode\Contract\Processor\ProcessorRegistry as ProcessorRegistryContract;
use KaririCode\DataStructure\Map\HashMap;

class ProcessorRegistry implements ProcessorRegistryContract
{
    public function __construct(private Map $processors = new HashMap())
    {
    }

    public function register(string $context, string $name, Processor $processor): void
    {
        if (!$this->processors->containsKey($context)) {
            $this->processors->put($context, new HashMap());
        }
        $contextMap = $this->processors->get($context);
        $contextMap->put($name, $processor);
    }

    public function get(string $context, string $name): Processor
    {
        if (!$this->processors->containsKey($context)) {
            throw new \RuntimeException("Context '$context' not found.");
        }
        $contextMap = $this->processors->get($context);
        if (!$contextMap->containsKey($name)) {
            throw new \RuntimeException("Processor '$name' not found in context '$context'.");
        }

        return $contextMap->get($name);
    }

    public function getContextProcessors(string $context): Map
    {
        if (!$this->processors->containsKey($context)) {
            throw new \RuntimeException("Context '$context' not found.");
        }

        return $this->processors->get($context);
    }
}
