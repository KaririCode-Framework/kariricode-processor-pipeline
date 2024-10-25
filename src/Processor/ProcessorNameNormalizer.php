<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Processor;

final readonly class ProcessorNameNormalizer
{
    public function normalize(string|int $key, array $processor): string
    {
        return $this->isNamedProcessor($key) ? (string) $key : $this->extractProcessorName($processor);
    }

    private function isNamedProcessor(string|int $key): bool
    {
        return is_string($key);
    }

    private function extractProcessorName(array $processor): string
    {
        $firstKey = array_key_first($processor);

        return is_string($firstKey) ? $firstKey : '';
    }
}
