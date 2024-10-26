<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline;

use KaririCode\Contract\DataStructure\Map;
use KaririCode\Contract\Processor\Processor;
use KaririCode\Contract\Processor\ProcessorRegistry as ProcessorRegistryContract;
use KaririCode\DataStructure\Map\HashMap;
use KaririCode\ProcessorPipeline\Exception\ProcessorRuntimeException;

class ProcessorRegistry implements ProcessorRegistryContract
{
    public function __construct(
        private readonly Map $processors = new HashMap()
    ) {
    }

    public function register(string $context, string $name, Processor $processor): static
    {
        if (!$this->processors->containsKey($context)) {
            $this->processors->put($context, new HashMap());
        }
        $contextMap = $this->processors->get($context);
        $contextMap->put($name, $processor);

        return $this;
    }

    public function get(string $context, string $name): Processor
    {
        if (!$this->processors->containsKey($context)) {
            throw ProcessorRuntimeException::contextNotFound($context);
        }

        $contextMap = $this->processors->get($context);
        if (!$contextMap->containsKey($name)) {
            throw ProcessorRuntimeException::processorNotFound($name, $context);
        }

        return $contextMap->get($name);
    }

    public function getContextProcessors(string $context): Map
    {
        if (!$this->processors->containsKey($context)) {
            throw ProcessorRuntimeException::contextNotFound($context);
        }

        return $this->processors->get($context);
    }
}
