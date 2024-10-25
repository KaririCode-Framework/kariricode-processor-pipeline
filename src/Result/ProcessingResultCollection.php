<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Result;

use KaririCode\Contract\Processor\ProcessingResult;

class ProcessingResultCollection implements ProcessingResult
{
    private array $processedData = [];
    private array $errors = [];

    public function addError(string $property, string $errorKey, string $message): void
    {
        $error = new ProcessingError($property, $errorKey, $message);

        if (!isset($this->errors[$property])) {
            $this->errors[$property] = [];
        }

        $this->errors[$property][$error->getHash()] = $error;
    }

    public function setProcessedData(string $property, mixed $value): void
    {
        $this->processedData[$property] = new ProcessedData($property, $value);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        $result = [];
        foreach ($this->errors as $property => $propertyErrors) {
            $result[$property] = array_values(array_map(
                fn (ProcessingError $error) => [
                    'errorKey' => $error->getErrorKey(),
                    'message' => $error->getMessage(),
                ],
                $propertyErrors
            ));
        }

        return $result;
    }

    public function getProcessedData(): array
    {
        $result = [];
        foreach ($this->processedData as $property => $data) {
            $result[$property] = $data->getValue();
        }

        return $result;
    }

    public function toArray(): array
    {
        return [
            'isValid' => !$this->hasErrors(),
            'errors' => $this->getErrors(),
            'processedData' => $this->getProcessedData(),
        ];
    }

    public function clear(): void
    {
        $this->processedData = [];
        $this->errors = [];
    }

    public function addProcessedData(ProcessedData $data): void
    {
        $this->processedData[$data->getProperty()] = $data;
    }

    public function addProcessingError(ProcessingError $error): void
    {
        if (!isset($this->errors[$error->getProperty()])) {
            $this->errors[$error->getProperty()] = [];
        }

        $this->errors[$error->getProperty()][$error->getHash()] = $error;
    }
}
