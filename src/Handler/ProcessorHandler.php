<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Handler;

use KaririCode\Contract\Processor\Processor;
use KaririCode\ProcessorPipeline\Result\ProcessingResultCollection;

/**
 * Wraps a Processor to integrate with ProcessingResultCollection.
 *
 * Delegates to the wrapped processor, catches domain-level errors, records them
 * into the result collection, and optionally halts or continues
 * the pipeline based on the halt-on-error flag.
 *
 * ARFA 1.3 Compliance
 * ===================
 *
 * P5 (Continuous Observability):
 *   Every execution is traced via {@see ProcessingResultCollection::recordExecution()}.
 *
 * @package   KaririCode\ProcessorPipeline\Handler
 * @author    Walmir Silva <walmir.silva@kariricode.org>
 * @copyright 2025 KaririCode
 * @license   MIT
 * @version   4.0.0
 * @since     4.0.0
 */
final readonly class ProcessorHandler implements Processor
{
    public function __construct(
        private Processor $processor,
        private ProcessingResultCollection $resultCollection,
        private bool $haltOnError = false,
    ) {
    }

    /**
     * Execute the wrapped processor with error collection.
     *
     * @param mixed $input Data to process
     *
     * @return mixed Processed data (passthrough on error unless halt-on-error)
     *
     * @throws \Throwable Re-thrown if halt-on-error is enabled
     */
    #[\Override]
    public function process(mixed $input): mixed
    {
        $this->resultCollection->recordExecution($this->processor::class);

        try {
            return $this->processor->process($input);
        } catch (\Throwable $throwable) {
            $this->resultCollection->addError(
                $this->processor::class,
                'processingFailed',
                $throwable->getMessage(),
            );

            if ($this->haltOnError) {
                throw $throwable;
            }

            return $input;
        }
    }
}
