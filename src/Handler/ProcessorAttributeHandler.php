<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Handler;

use KaririCode\Contract\Processor\ProcessorBuilder;
use KaririCode\Contract\Processor\ValidatableProcessor;
use KaririCode\ProcessorPipeline\Result\ProcessedData;
use KaririCode\ProcessorPipeline\Result\ProcessingError;
use KaririCode\ProcessorPipeline\Result\ProcessingResultCollection;
use KaririCode\PropertyInspector\AttributeHandler;

class ProcessorAttributeHandler extends AttributeHandler
{
    protected ProcessingResultCollection $results;

    public function __construct(
        private readonly string $identifier,
        private readonly ProcessorBuilder $builder
    ) {
        parent::__construct($identifier, $builder);
        $this->results = new ProcessingResultCollection();
    }

    public function processPropertyValue(string $property, mixed $value): mixed
    {
        $pipeline = $this->builder->buildPipeline(
            $this->identifier,
            $this->getPropertyProcessors($property)
        );

        try {
            $processedValue = $pipeline->process($value);
            $this->storeProcessedValue($property, $processedValue);

            // Verifica se há erros de validação
            $this->checkValidationErrors($property, $pipeline);

            return $processedValue;
        } catch (\Exception $e) {
            $this->storeProcessingError($property, $e);

            return $value;
        }
    }

    protected function checkValidationErrors(string $property, $pipeline): void
    {
        foreach ($pipeline->getProcessors() as $processor) {
            if ($processor instanceof ValidatableProcessor && !$processor->isValid()) {
                $this->storeValidationError(
                    $property,
                    $processor->getErrorKey(),
                    $processor->getErrorMessage()
                );
            }
        }
    }

    protected function storeProcessedValue(string $property, mixed $value): void
    {
        $processedData = new ProcessedData($property, $value);
        $this->results->addProcessedData($processedData);
    }

    protected function storeProcessingError(string $property, \Exception $exception): void
    {
        $error = new ProcessingError(
            $property,
            'processingError',
            $exception->getMessage()
        );
        $this->results->addError($error);
    }

    protected function storeValidationError(string $property, string $errorKey, string $message): void
    {
        $error = new ProcessingError($property, $errorKey, $message);
        $this->results->addError($error);
    }

    public function getProcessingResults(): ProcessingResultCollection
    {
        return $this->results;
    }

    public function getProcessedPropertyValues(): array
    {
        return $this->results->getProcessedDataAsArray();
    }

    public function getProcessingResultErrors(): array
    {
        return $this->results->getErrorsAsArray();
    }

    public function reset(): void
    {
        $this->results = new ProcessingResultCollection();
    }
}
