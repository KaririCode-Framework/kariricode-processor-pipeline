<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Result;

class ProcessingError
{
    private readonly string $hash;
    private readonly int $timestamp;

    public function __construct(
        private readonly string $property,
        private readonly string $errorKey,
        private readonly string $message
    ) {
        $this->hash = $this->generateHash();
        $this->timestamp = time();
    }

    private function generateHash(): string
    {
        return md5($this->property . $this->errorKey . $this->message);
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getErrorKey(): string
    {
        return $this->errorKey;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function toArray(): array
    {
        return [
            'errorKey' => $this->errorKey,
            'message' => $this->message,
            'timestamp' => $this->timestamp,
        ];
    }
}
