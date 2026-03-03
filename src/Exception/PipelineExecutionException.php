<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Exception;

/**
 * Thrown when pipeline execution fails at a specific stage.
 *
 * Captures the processor name, stage index, and original exception
 * for structured observability (ARFA 1.3 P5).
 *
 * @package   KaririCode\ProcessorPipeline\Exception
 * @author    Walmir Silva <walmir.silva@kariricode.org>
 * @copyright 2025 KaririCode
 * @license   MIT
 * @version   4.0.0
 * @since     4.0.0
 */
final class PipelineExecutionException extends ProcessorPipelineException
{
    public static function atStage(
        string $processorName,
        int $stageIndex,
        \Throwable $cause,
    ): self {
        return new self(
            message: "Pipeline failed at stage {$stageIndex} (processor: {$processorName}): {$cause->getMessage()}",
            previous: $cause,
            context: [
                'processor' => $processorName,
                'stage' => $stageIndex,
                'causeClass' => $cause::class,
            ],
        );
    }
}
