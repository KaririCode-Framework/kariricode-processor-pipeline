<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Exception;

/**
 * Thrown when a configurable processor receives invalid options.
 *
 * This occurs when {@see \KaririCode\ProcessorPipeline\ProcessorBuilder}
 * attempts to configure a processor that does not implement
 * {@see \KaririCode\Contract\Processor\ConfigurableProcessor}, or when
 * the configuration array contains invalid values.
 *
 * @package   KaririCode\ProcessorPipeline\Exception
 * @author    Walmir Silva <walmir.silva@kariricode.org>
 * @copyright 2025 KaririCode
 * @license   MIT
 * @version   4.0.0
 * @since     4.0.0
 *
 * @see \KaririCode\Contract\Processor\ConfigurableProcessor
 * @see \KaririCode\ProcessorPipeline\ProcessorBuilder::configureProcessor()
 */
final class InvalidProcessorConfigurationException extends ProcessorPipelineException
{
    public static function forProcessor(string $processorName, string $reason): self
    {
        return new self(
            message: "Invalid configuration for processor '{$processorName}': {$reason}",
            context: ['processor' => $processorName, 'reason' => $reason],
        );
    }
}
