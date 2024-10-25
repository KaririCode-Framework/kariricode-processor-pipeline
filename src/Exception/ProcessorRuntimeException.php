<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Exception;

use KaririCode\Exception\Runtime\RuntimeException;

final class ProcessorRuntimeException extends RuntimeException
{
    private const CODE_CONTEXT_NOT_FOUND = 2601;
    private const CODE_PROCESSOR_NOT_FOUND = 2602;
    private const CODE_INVALID_PROCESSOR = 2603;
    private const CODE_INVALID_CONTEXT = 2604;
    private const CODE_PROCESSOR_CONFIG_INVALID = 2605;
    private const CODE_PROCESSING_FAILED = 2606;

    public static function contextNotFound(string $context): self
    {
        return self::createException(
            self::CODE_CONTEXT_NOT_FOUND,
            'PROCESSOR_CONTEXT_NOT_FOUND',
            "Processor context '{$context}' not found"
        );
    }

    public static function processorNotFound(string $processorName, string $context): self
    {
        return self::createException(
            self::CODE_PROCESSOR_NOT_FOUND,
            'PROCESSOR_NOT_FOUND',
            "Processor '{$processorName}' not found in context '{$context}'"
        );
    }

    public static function invalidProcessor(string $processorName, string $details): self
    {
        return self::createException(
            self::CODE_INVALID_PROCESSOR,
            'PROCESSOR_INVALID',
            "Invalid processor '{$processorName}': {$details}"
        );
    }

    public static function invalidContext(string $context, string $details): self
    {
        return self::createException(
            self::CODE_INVALID_CONTEXT,
            'PROCESSOR_CONTEXT_INVALID',
            "Invalid processor context '{$context}': {$details}"
        );
    }

    public static function invalidConfiguration(string $processorName, string $details): self
    {
        return self::createException(
            self::CODE_PROCESSOR_CONFIG_INVALID,
            'PROCESSOR_CONFIG_INVALID',
            "Invalid processor configuration for '{$processorName}': {$details}"
        );
    }

    public static function processingFailed(string $property): self
    {
        return self::createException(
            self::CODE_PROCESSING_FAILED,
            'PROCESSOR_PROCESSING_FAILED',
            "Processing failed for property '{$property}'",
        );
    }
}
