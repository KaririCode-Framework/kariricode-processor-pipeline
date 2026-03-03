<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Exception;

/**
 * Thrown when a processor is not found in the registry.
 *
 * @package   KaririCode\ProcessorPipeline\Exception
 * @author    Walmir Silva <walmir.silva@kariricode.org>
 * @copyright 2025 KaririCode
 * @license   MIT
 * @version   2.0.0
 * @since     2.0.0
 */
final class ProcessorNotFoundException extends ProcessorPipelineException
{
    public static function forNameInContext(string $processorName, string $context): self
    {
        return new self(
            message: "Processor '{$processorName}' not found in context '{$context}'.",
            context: ['processor' => $processorName, 'context' => $context],
        );
    }

    public static function forName(string $processorName): self
    {
        return new self(
            message: "Processor '{$processorName}' not found.",
            context: ['processor' => $processorName],
        );
    }
}
