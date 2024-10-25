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
    private const ERROR_PREFIX = 'PROCESSOR';

    public static function contextNotFound(string $context): self
    {
        return self::createException(
            self::CODE_CONTEXT_NOT_FOUND,
            self::ERROR_PREFIX . '_CONTEXT_NOT_FOUND',
            "Processor context '{$context}' not found"
        );
    }

    public static function processorNotFound(string $processorName, string $context): self
    {
        return self::createException(
            self::CODE_PROCESSOR_NOT_FOUND,
            self::ERROR_PREFIX . '_NOT_FOUND',
            "Processor '{$processorName}' not found in context '{$context}'"
        );
    }

    public static function invalidProcessor(string $processorName, string $details): self
    {
        return self::createException(
            self::CODE_INVALID_PROCESSOR,
            self::ERROR_PREFIX . '_INVALID',
            "Invalid processor '{$processorName}': {$details}"
        );
    }

    public static function invalidContext(string $context, string $details): self
    {
        return self::createException(
            self::CODE_INVALID_CONTEXT,
            self::ERROR_PREFIX . '_CONTEXT_INVALID',
            "Invalid processor context '{$context}': {$details}"
        );
    }

    public static function invalidConfiguration(string $processorName, string $details): self
    {
        return self::createException(
            self::CODE_PROCESSOR_CONFIG_INVALID,
            self::ERROR_PREFIX . '_CONFIG_INVALID',
            "Invalid processor configuration for '{$processorName}': {$details}"
        );
    }
}
