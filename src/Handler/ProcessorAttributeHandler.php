<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Handler;

use KaririCode\Contract\Processor\ProcessorBuilder;
use KaririCode\ProcessorPipeline\AttributeHandler;
use KaririCode\ProcessorPipeline\Exception\ProcessorRuntimeException;
use KaririCode\ProcessorPipeline\Result\ProcessingResultCollection;

final class ProcessorAttributeHandler extends AttributeHandler
{
    private ProcessingResultCollection $results;

    public function __construct(
        string $identifier,
        ProcessorBuilder $builder
    ) {
        parent::__construct($identifier, $builder);
        $this->results = new ProcessingResultCollection();
    }

    public function processPropertyValue(string $property, mixed $value): mixed
    {
        $processorSpecs = $this->getPropertyProcessors($property);

        if (empty($processorSpecs)) {
            return $value;
        }

        try {
            $pipeline = $this->builder->buildPipeline(
                $this->identifier,
                $processorSpecs
            );

            $processedValue = $pipeline->process($value);
            $this->results->setProcessedData($property, $processedValue);

            return $processedValue;
        } catch (\Exception $e) {
            throw ProcessorRuntimeException::processingFailed($property, $e);
        }
    }

    public function getProcessedPropertyValues(): array
    {
        return [
            'values' => $this->results->getProcessedData(),
            'timestamp' => time(),
        ];
    }

    public function getProcessingResultErrors(): array
    {
        return $this->results->getErrors();
    }

    public function hasErrors(): bool
    {
        return $this->results->hasErrors();
    }

    public function getProcessingResults(): ProcessingResultCollection
    {
        return $this->results;
    }

    public function reset(): void
    {
        $this->results = new ProcessingResultCollection();
    }
}
