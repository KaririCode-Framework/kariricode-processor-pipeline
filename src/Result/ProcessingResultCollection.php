<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Result;

/**
 * Collects processing errors and metadata during pipeline execution.
 *
 * ARFA 1.3 Compliance
 * ===================
 *
 * P1 (Immutable State Transformation):
 *   Results accumulate via append-only operations. Once recorded,
 *   an error entry is never mutated.
 *
 * P5 (Continuous Observability):
 *   Every error carries processor class, error key, and human-readable
 *   message — enabling structured logging and metrics collection.
 *   Execution trace records processor ordering for distributed tracing.
 *
 * PHP 8.4 Features
 * ================
 *
 * Uses property hooks for computed read-only access to internal state:
 *   - `$results->hasErrors`   (computed bool)
 *   - `$results->hasWarnings` (computed bool)
 *   - `$results->errorCount`  (computed int)
 *   - `$results->errors`      (read-only array)
 *   - `$results->warnings`    (read-only array)
 *   - `$results->executionTrace` (read-only list)
 *
 * Usage
 * =====
 *
 * ```php
 * $results = new ProcessingResultCollection();
 *
 * // Inside a processor:
 * $results->addError(self::class, 'invalidFormat', 'Email is not valid.');
 *
 * // After pipeline — read via property hooks:
 * if ($results->hasErrors) {
 *     foreach ($results->errors as $processorClass => $errorList) {
 *         // $errorList is list<array{errorKey: string, message: string}>
 *     }
 * }
 *
 * // Trace inspection:
 * $trace = $results->executionTrace; // list<string>
 * $count = $results->errorCount;     // int (computed)
 * ```
 *
 * Thread Safety
 * =============
 *
 * This class is NOT thread-safe. Each pipeline execution should use its
 * own instance. For async processing, use per-fiber/per-promise instances.
 *
 * @package   KaririCode\ProcessorPipeline\Result
 * @author    Walmir Silva <walmir.silva@kariricode.org>
 * @copyright 2025 KaririCode
 * @license   MIT
 * @version   4.0.0
 * @since     1.0.0
 *
 * @see \KaririCode\ProcessorPipeline\Pipeline\Pipeline      Pipeline integration
 * @see \KaririCode\ProcessorPipeline\Handler\ProcessorHandler Error collection handler
 */
final class ProcessingResultCollection
{
    /** @var array<string, list<array{errorKey: string, message: string}>> */
    private array $errorEntries = [];

    /** @var array<string, list<array{errorKey: string, message: string}>> */
    private array $warningEntries = [];

    /** @var list<string> Processor execution order for tracing */
    private array $traceEntries = [];

    // ── Property Hooks (PHP 8.4) ─────────────────────────────────────

    /**
     * Read-only access to collected errors.
     *
     * @var array<string, list<array{errorKey: string, message: string}>>
     */
    public array $errors {
        get => $this->errorEntries;
    }

    /**
     * Read-only access to collected warnings.
     *
     * @var array<string, list<array{errorKey: string, message: string}>>
     */
    public array $warnings {
        get => $this->warningEntries;
    }

    /** @var list<string> */
    public array $executionTrace {
        get => $this->traceEntries;
    }

    /** Whether any errors have been recorded (computed). */
    public bool $hasErrors {
        get => $this->errorEntries !== [];
    }

    /** Whether any warnings have been recorded (computed). */
    public bool $hasWarnings {
        get => $this->warningEntries !== [];
    }

    /** Total individual error entries across all processors (computed). */
    public int $errorCount {
        get {
            $count = 0;
            foreach ($this->errorEntries as $entries) {
                $count += \count($entries);
            }

            return $count;
        }
    }

    // ── Mutation Methods ─────────────────────────────────────────────

    /**
     * Record a processing error.
     *
     * @param string $processorClass Fully-qualified processor class name
     * @param string $errorKey       Machine-readable error identifier
     * @param string $message        Human-readable error description
     */
    public function addError(string $processorClass, string $errorKey, string $message): void
    {
        $shortName = $this->extractShortName($processorClass);
        $this->errorEntries[$shortName][] = ['errorKey' => $errorKey, 'message' => $message];
    }

    /**
     * Record a processing warning (non-fatal).
     *
     * @param string $processorClass Fully-qualified processor class name
     * @param string $errorKey       Machine-readable warning identifier
     * @param string $message        Human-readable warning description
     */
    public function addWarning(string $processorClass, string $errorKey, string $message): void
    {
        $shortName = $this->extractShortName($processorClass);
        $this->warningEntries[$shortName][] = ['errorKey' => $errorKey, 'message' => $message];
    }

    /**
     * Record a processor execution for tracing (ARFA P5).
     *
     * @param string $processorClass Fully-qualified processor class name
     */
    public function recordExecution(string $processorClass): void
    {
        $this->traceEntries[] = $processorClass;
    }

    // ── Legacy Accessors (V1.x compat) ───────────────────────────────

    /** @return array<string, list<array{errorKey: string, message: string}>> */
    public function getErrors(): array
    {
        return $this->errorEntries;
    }

    /** @return array<string, list<array{errorKey: string, message: string}>> */
    public function getWarnings(): array
    {
        return $this->warningEntries;
    }

    /** @return list<string> */
    public function getExecutionTrace(): array
    {
        return $this->traceEntries;
    }

    /** @deprecated Use property hook $results->hasErrors instead. */
    public function hasErrors(): bool
    {
        return $this->errorEntries !== [];
    }

    /** @deprecated Use property hook $results->hasWarnings instead. */
    public function hasWarnings(): bool
    {
        return $this->warningEntries !== [];
    }

    // ── Collection Operations ────────────────────────────────────────

    /**
     * Merge another collection into this one (for parallel pipeline results).
     */
    public function merge(self $other): void
    {
        foreach ($other->errorEntries as $processor => $entries) {
            foreach ($entries as $entry) {
                $this->errorEntries[$processor][] = $entry;
            }
        }

        foreach ($other->warningEntries as $processor => $entries) {
            foreach ($entries as $entry) {
                $this->warningEntries[$processor][] = $entry;
            }
        }

        $this->traceEntries = [...$this->traceEntries, ...$other->traceEntries];
    }

    public function reset(): void
    {
        $this->errorEntries = [];
        $this->warningEntries = [];
        $this->traceEntries = [];
    }

    // ── Internal ─────────────────────────────────────────────────────

    private function extractShortName(string $fullyQualifiedName): string
    {
        $parts = explode('\\', $fullyQualifiedName);

        return end($parts);
    }
}
