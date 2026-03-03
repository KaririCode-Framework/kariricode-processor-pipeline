<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Exception;

/**
 * Base exception for the ProcessorPipeline component.
 *
 * All exceptions thrown by this component extend from this class,
 * enabling granular catch blocks at any level of specificity.
 *
 * ARFA 1.3 Compliance:
 *   P5 (Continuous Observability) — exceptions carry structured context
 *   for tracing and metrics collection.
 *
 * @package   KaririCode\ProcessorPipeline\Exception
 * @author    Walmir Silva <walmir.silva@kariricode.org>
 * @copyright 2025 KaririCode
 * @license   MIT
 * @version   4.0.0
 * @since     1.0.0
 */
class ProcessorPipelineException extends \RuntimeException
{
    /**
     * @param array<string, mixed> $context Structured context for observability
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
    }
}
