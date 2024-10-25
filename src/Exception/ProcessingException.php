<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Exception;

use KaririCode\Exception\AbstractException;

final class ProcessingException extends AbstractException
{
    private const CODE_PIPELINE_FAILED = 3001;
    private const CODE_PROCESSOR_FAILED = 3002;

    public static function pipelineExecutionFailed(): self
    {
        return self::createException(
            self::CODE_PIPELINE_FAILED,
            'PIPELINE_FAILED',
            'Pipeline processing failed'
        );
    }

    public static function processorExecutionFailed(string $processorClass): self
    {
        $message = sprintf('Processor %s execution failed', $processorClass);

        return self::createException(
            self::CODE_PROCESSOR_FAILED,
            'PROCESSOR_FAILED',
            $message
        );
    }
}
