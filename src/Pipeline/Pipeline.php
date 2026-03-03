<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Pipeline;

use KaririCode\Contract\Processor\Processor;
use KaririCode\ProcessorPipeline\Exception\PipelineExecutionException;

/**
 * Immutable sequential processor pipeline.
 *
 * Overview
 * ========
 *
 * Pipeline is the execution engine of the ProcessorPipeline component. It chains
 * an ordered list of {@see Processor} instances and feeds data through them
 * sequentially.  Each processor receives the output of its predecessor.
 *
 * ARFA 1.3 Compliance
 * ===================
 *
 * P1 (Immutable State Transformation):
 *   Pipeline is immutable after construction. Adding a processor returns a
 *   **new** Pipeline instance via {@see withProcessor()}.  The original
 *   pipeline is never modified.
 *
 * P2 (Reactive Flow Composition):
 *   Processors compose as f₁ ∘ f₂ ∘ ... ∘ fₙ.  Each fᵢ receives the
 *   output of fᵢ₋₁ and produces input for fᵢ₊₁.
 *
 * P5 (Continuous Observability):
 *   Execution failures are wrapped in {@see PipelineExecutionException}
 *   with stage index and processor name for structured tracing.
 *
 * Usage
 * =====
 *
 * ```php
 * // Constructed via ProcessorBuilder (preferred):
 * $pipeline = $builder->buildPipeline('validator', ['required', 'email']);
 * $result = $pipeline->process($input);
 *
 * // Manual construction:
 * $pipeline = new Pipeline([new TrimProcessor(), new EmailValidator()]);
 * $result = $pipeline->process('  foo@bar.com  ');
 *
 * // Immutable extension:
 * $extended = $pipeline->withProcessor(new UpperCaseProcessor());
 * // $pipeline is unchanged; $extended has 3 processors
 * ```
 *
 * Algorithm
 * =========
 *
 * ```
 * Algorithm: Sequential Pipeline Execution
 * Input:  pipeline π (n processors), data d
 * Output: transformed data d'
 *
 * 1. state ← d
 * 2. FOR i = 0 TO n-1 DO
 * 3.   TRY
 * 4.     state ← π.processors[i].process(state)
 * 5.   CATCH error
 * 6.     THROW PipelineExecutionException(stage=i, processor=π.processors[i])
 * 7. END FOR
 * 8. RETURN state
 * ```
 *
 * Complexity: O(n·p) where n = processor count, p = average processor cost.
 *
 * Theorem (Termination):
 *   Pipeline execution terminates if every processor terminates.
 *   Proof: Finite processor count n; sequential iteration; no loops. ∎
 *
 * @package   KaririCode\ProcessorPipeline\Pipeline
 * @author    Walmir Silva <walmir.silva@kariricode.org>
 * @copyright 2025 KaririCode
 * @license   MIT
 * @version   4.0.0
 * @since     1.0.0
 *
 * @see \KaririCode\ProcessorPipeline\ProcessorBuilder Construction
 * @see \KaririCode\ProcessorPipeline\ProcessorRegistry Registration
 */
final readonly class Pipeline
{
    /** @var list<Processor> */
    private array $processors;

    /**
     * @param list<Processor> $processors Ordered processor list
     */
    public function __construct(array $processors = [])
    {
        /** @psalm-suppress RedundantFunctionCall — input may have non-sequential keys */
        $this->processors = array_values($processors);
    }

    /**
     * Execute all processors sequentially on the input data.
     *
     * @param mixed $input Data to process
     *
     * @return mixed Processed result after all stages
     *
     * @throws PipelineExecutionException When any processor throws
     */
    public function process(mixed $input): mixed
    {
        $state = $input;

        foreach ($this->processors as $index => $processor) {
            try {
                $state = $processor->process($state);
            } catch (PipelineExecutionException $executionException) {
                throw $executionException;
            } catch (\Throwable $throwable) {
                throw PipelineExecutionException::atStage(
                    processorName: $processor::class,
                    stageIndex: $index,
                    cause: $throwable,
                );
            }
        }

        return $state;
    }

    /**
     * Create a new pipeline with an additional processor appended.
     *
     * IMMUTABLE — returns a new Pipeline instance.
     * The current instance is never modified (ARFA 1.3 P1).
     */
    public function withProcessor(Processor $processor): self
    {
        return new self([...$this->processors, $processor]);
    }

    /**
     * Create a new pipeline with another pipeline's processors appended.
     *
     * IMMUTABLE — returns a new Pipeline instance.
     */
    public function withPipeline(self $other): self
    {
        return new self([...$this->processors, ...$other->processors]);
    }

    /** @return list<Processor> */
    public function getProcessors(): array
    {
        return $this->processors;
    }

    public function count(): int
    {
        return \count($this->processors);
    }

    public function isEmpty(): bool
    {
        return $this->processors === [];
    }
}
