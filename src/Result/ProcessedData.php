<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Result;

class ProcessedData
{
    private readonly int $timestamp;

    public function __construct(
        private readonly string $property,
        private readonly mixed $value
    ) {
        $this->timestamp = time();
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'timestamp' => $this->timestamp,
        ];
    }
}
