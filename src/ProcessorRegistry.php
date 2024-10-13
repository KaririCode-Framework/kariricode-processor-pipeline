<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline;

use KaririCode\Contract\Processor\Processor;
use KaririCode\DataStructure\Map\HashMap;

class ProcessorRegistry
{
    public function __construct(private HashMap $processors = new HashMap())
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

    public function getContextProcessors(string $context): HashMap
    {
        if (!$this->processors->containsKey($context)) {
            throw new \RuntimeException("Context '$context' not found.");
        }

        return $this->processors->get($context);
    }
}
