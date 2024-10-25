<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Handler;

use KaririCode\Contract\Processor\ProcessorBuilder;
use KaririCode\Contract\Processor\ValidatableProcessor;
use KaririCode\ProcessorPipeline\Exception\ProcessorRuntimeException;
use KaririCode\ProcessorPipeline\Processor\ProcessorConfigBuilder;
use KaririCode\ProcessorPipeline\Processor\ProcessorValidator;
use KaririCode\ProcessorPipeline\Result\ProcessingResultCollection;

/**
 * Handler for processing attributes with configured processors.
 */
final class ProcessorAttributeHandler extends AttributeHandler
{
    private ProcessingResultCollection $results;

    public function __construct(
        private readonly string $identifier,
        private readonly ProcessorBuilder $builder,
        private readonly ProcessorValidator $validator = new ProcessorValidator(),
        private readonly ProcessorConfigBuilder $configBuilder = new ProcessorConfigBuilder()
    ) {
        parent::__construct($identifier, $builder);
        $this->results = new ProcessingResultCollection();
    }

    public function handleAttribute(string $propertyName, object $attribute, mixed $value): mixed
    {
        $result = parent::handleAttribute($propertyName, $attribute, $value);

        if (null !== $result) {
            $this->transferResults($propertyName);
        }

        return $result;
    }

    /**
     * Transfers results from parent handler to ProcessingResultCollection.
     */
    private function transferResults(string $propertyName): void
    {
        $processedValues = parent::getProcessedPropertyValues();
        $errors = parent::getProcessingResultErrors();

        if (isset($processedValues[$propertyName])) {
            $this->results->setProcessedData(
                $propertyName,
                $processedValues[$propertyName]['value']
            );
        }

        if (isset($errors[$propertyName])) {
            foreach ($errors[$propertyName] as $processorName => $error) {
                $this->results->addError(
                    $propertyName,
                    $error['errorKey'] ?? 'processing_error',
                    $error['message'] ?? 'Unknown error'
                );
            }
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

    /**
     * Checks if there are any processing errors.
     */
    public function hasErrors(): bool
    {
        return $this->results->hasErrors();
    }

    /**
     * Gets the processing results collection.
     */
    public function getProcessingResults(): ProcessingResultCollection
    {
        return $this->results;
    }

    /**
     * Resets the processing state.
     */
    public function reset(): void
    {
        parent::reset();
        $this->results = new ProcessingResultCollection();
    }

    protected function validateProcessors(array $processorsConfig, array $messages): array
    {
        $errors = [];

        foreach ($processorsConfig as $processorName => $config) {
            $processor = $this->builder->build(
                $this->identifier,
                $processorName,
                $config
            );

            if ($processor instanceof ValidatableProcessor && !$processor->isValid()) {
                $errorKey = $processor->getErrorKey();
                $message = $messages[$processorName] ?? "Validation failed for $processorName";

                $errors[$processorName] = [
                    'errorKey' => $errorKey,
                    'message' => $message,
                ];

                $this->results->addError($processorName, $errorKey, $message);
            }
        }

        return $errors;
    }

    protected function processValue(mixed $value, array $config): mixed
    {
        try {
            return $this->builder
                ->buildPipeline($this->identifier, $config)
                ->process($value);
        } catch (\Exception $e) {
            throw ProcessorRuntimeException::processingFailed($value);
        }
    }
}
