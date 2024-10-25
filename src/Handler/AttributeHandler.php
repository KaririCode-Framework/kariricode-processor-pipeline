<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Handler;

use KaririCode\Contract\Processor\Attribute\CustomizableMessageAttribute;
use KaririCode\Contract\Processor\Attribute\ProcessableAttribute;
use KaririCode\Contract\Processor\ProcessorBuilder;
use KaririCode\Contract\Processor\ProcessorValidator as ProcessorProcessorContract;
use KaririCode\ProcessorPipeline\Contract\ProcessorConfigBuilder as ProcessorConfigBuilderContract;
use KaririCode\ProcessorPipeline\Processor\ProcessorConfigBuilder;
use KaririCode\ProcessorPipeline\Processor\ProcessorValidator;
use KaririCode\PropertyInspector\Contract\PropertyAttributeHandler;
use KaririCode\PropertyInspector\Contract\PropertyChangeApplier;
use KaririCode\PropertyInspector\Utility\PropertyAccessor;

class AttributeHandler implements PropertyAttributeHandler, PropertyChangeApplier
{
    private array $processedPropertyValues = [];
    private array $processingResultErrors = [];
    private array $processingResultMessages = [];
    private array $processorCache = [];

    public function __construct(
        private readonly string $processorType,
        private readonly ProcessorBuilder $builder,
        private readonly ProcessorProcessorContract $validator = new ProcessorValidator(),
        private readonly ProcessorConfigBuilderContract $configBuilder = new ProcessorConfigBuilder()
    ) {
    }

    public function handleAttribute(string $propertyName, object $attribute, mixed $value): mixed
    {
        if (!$attribute instanceof ProcessableAttribute) {
            return null;
        }

        try {
            return $this->processAttribute($propertyName, $attribute, $value);
        } catch (\Exception $e) {
            $this->processingResultErrors[$propertyName][] = $e->getMessage();

            return $value;
        }
    }

    private function processAttribute(string $propertyName, ProcessableAttribute $attribute, mixed $value): mixed
    {
        $config = $this->configBuilder->build($attribute);
        $messages = [];

        if ($attribute instanceof CustomizableMessageAttribute) {
            foreach ($config as $processorName => &$processorConfig) {
                if ($message = $attribute->getMessage($processorName)) {
                    $processorConfig['customMessage'] = $message;
                    $messages[$processorName] = $message;
                }
            }
        }

        $processedValue = $this->processValue($value, $config);

        if ($errors = $this->validateProcessors($config, $messages)) {
            $this->processingResultErrors[$propertyName] = $errors;
        }

        $this->processedPropertyValues[$propertyName] = [
            'value' => $processedValue,
            'messages' => $messages,
        ];

        $this->processingResultMessages[$propertyName] = $messages;

        return $processedValue;
    }

    private function validateProcessors(array $processorsConfig, array $messages): array
    {
        $errors = [];
        foreach ($processorsConfig as $processorName => $config) {
            // Simplify cache key to processor name
            if (!isset($this->processorCache[$processorName])) {
                $this->processorCache[$processorName] = $this->builder->build(
                    $this->processorType,
                    $processorName,
                    $config
                );
            }

            $processor = $this->processorCache[$processorName];

            if ($error = $this->validator->validate($processor, $processorName, $messages)) {
                $errors[$processorName] = $error;
            }
        }

        return $errors;
    }

    private function processValue(mixed $value, array $config): mixed
    {
        return $this->builder
            ->buildPipeline($this->processorType, $config)
            ->process($value);
    }

    public function applyChanges(object $entity): void
    {
        foreach ($this->processedPropertyValues as $propertyName => $data) {
            (new PropertyAccessor($entity, $propertyName))->setValue($data['value']);
        }
    }

    public function getProcessedPropertyValues(): array
    {
        return $this->processedPropertyValues;
    }

    public function getProcessingResultErrors(): array
    {
        return $this->processingResultErrors;
    }

    public function getProcessingResultMessages(): array
    {
        return $this->processingResultMessages;
    }

    public function reset(): void
    {
        $this->processedPropertyValues = [];
        $this->processingResultErrors = [];
        $this->processingResultMessages = [];
        $this->processorCache = [];
    }
}
