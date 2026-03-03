<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline;

use KaririCode\Contract\Processor\Processor;
use KaririCode\ProcessorPipeline\Exception\ProcessorNotFoundException;

/**
 * Context-based registry for processor instances.
 *
 * Overview
 * ========
 *
 * ProcessorRegistry organises processors into named contexts (e.g. 'validator',
 * 'sanitizer', 'transformer'), enabling multiple independent processing domains
 * within a single application.  Each processor is identified by its context +
 * name pair, ensuring collision-free registration across domains.
 *
 * ARFA 1.3 Compliance
 * ===================
 *
 * P1 (Immutable State Transformation):
 *   The registry itself is mutable (it accumulates registrations), but
 *   registered processor references are never replaced silently — last-write
 *   wins with deterministic behaviour.
 *
 * P3 (Adaptive Context Awareness):
 *   Context-based partitioning allows the same processor class to behave
 *   differently under separate contexts with different configurations.
 *
 * P4 (Protocol Agnosticism):
 *   The registry is protocol-agnostic — processors registered here can
 *   serve HTTP, gRPC, GraphQL, WebSocket, SSE, or CLI protocols.
 *
 * P5 (Continuous Observability):
 *   Registration is introspectable via {@see getContextNames()},
 *   {@see getProcessorNames()}, and {@see count()}.
 *
 * Usage
 * =====
 *
 * ## Basic Registration
 *
 * ```php
 * use KaririCode\ProcessorPipeline\ProcessorRegistry;
 *
 * $registry = new ProcessorRegistry();
 *
 * $registry->register('validator', 'required', new RequiredValidator());
 * $registry->register('validator', 'email', new EmailValidator());
 * $registry->register('sanitizer', 'trim', new TrimSanitizer());
 * $registry->register('sanitizer', 'htmlEscape', new HtmlEscapeSanitizer());
 * ```
 *
 * ## Retrieval
 *
 * ```php
 * $processor = $registry->get('validator', 'required');
 * $allValidators = $registry->getByContext('validator');
 * ```
 *
 * ## Fluent API
 *
 * ```php
 * $registry = (new ProcessorRegistry())
 *     ->register('validator', 'required', new RequiredValidator())
 *     ->register('validator', 'email', new EmailValidator())
 *     ->register('sanitizer', 'trim', new TrimSanitizer());
 * ```
 *
 * ## Integration with ProcessorBuilder
 *
 * ```php
 * $builder = new ProcessorBuilder($registry);
 * $pipeline = $builder->buildPipeline('validator', ['required', 'email']);
 * $result = $pipeline->process($input);
 * ```
 *
 * Design Decisions
 * ================
 *
 * 1. **Two-level map** (`context → name → Processor`) avoids name collisions
 *    across domains. A 'trim' sanitizer and a 'trim' transformer coexist.
 *
 * 2. **Last-write-wins** for re-registration — no exception on overwrite.
 *    This enables test doubles and environment-specific processor swaps.
 *
 * 3. **No lazy loading** — processors are registered as fully-constructed
 *    instances. For deferred instantiation, integrate with a PSR-11
 *    container via {@see \KaririCode\Contract\DependencyInjection\Container}.
 *
 * Complexity
 * ==========
 *
 * | Operation            | Time    | Space  |
 * |----------------------|---------|--------|
 * | register()           | O(1)    | O(1)   |
 * | get()                | O(1)    | O(1)   |
 * | getByContext()       | O(n)    | O(n)   |
 * | has()                | O(1)    | O(1)   |
 * | count()              | O(c)    | O(1)   |
 *
 * where n = processors in context, c = number of contexts.
 *
 * @package   KaririCode\ProcessorPipeline
 * @author    Walmir Silva <walmir.silva@kariricode.org>
 * @copyright 2025 KaririCode
 * @license   MIT
 * @version   4.0.0
 * @since     1.0.0
 *
 * @see \KaririCode\ProcessorPipeline\ProcessorBuilder      Pipeline construction
 * @see \KaririCode\ProcessorPipeline\Pipeline\Pipeline      Execution engine
 * @see \KaririCode\Contract\Processor\Processor             Processor contract
 * @see \KaririCode\Contract\Processor\ConfigurableProcessor Configurable contract
 */
final class ProcessorRegistry
{
    /**
     * Two-level map: context → processorName → Processor.
     *
     * @var array<string, array<string, Processor>>
     */
    private array $processors = [];

    // ── Property Hooks (PHP 8.4) ─────────────────────────────────────

    /** @var list<string> All registered context names (computed). */
    public array $contextNames {
        get => array_keys($this->processors);
    }

    /** Total processor count across all contexts (computed). */
    public int $totalCount {
        get {
            $total = 0;
            foreach ($this->processors as $contextProcessors) {
                $total += \count($contextProcessors);
            }

            return $total;
        }
    }

    /** Whether the registry contains any processors (computed). */
    public bool $isEmpty {
        get => $this->processors === [];
    }

    // ── Registration ─────────────────────────────────────────────────

    /**
     * Register a processor under a context and name.
     *
     * @param string    $context       Domain context (e.g. 'validator', 'sanitizer')
     * @param string    $processorName Unique name within the context
     * @param Processor $processor     Processor instance
     *
     * @return self Fluent interface
     */
    public function register(string $context, string $processorName, Processor $processor): self
    {
        $this->processors[$context][$processorName] = $processor;

        return $this;
    }

    // ── Retrieval ────────────────────────────────────────────────────

    /**
     * Retrieve a processor by context and name.
     *
     * @throws ProcessorNotFoundException When processor is not registered
     */
    public function get(string $context, string $processorName): Processor
    {
        if (! $this->has($context, $processorName)) {
            throw ProcessorNotFoundException::forNameInContext($processorName, $context);
        }

        return $this->processors[$context][$processorName];
    }

    /**
     * Check whether a processor exists in the registry.
     */
    public function has(string $context, string $processorName): bool
    {
        return isset($this->processors[$context][$processorName]);
    }

    /**
     * Retrieve all processors registered under a context.
     *
     * @return array<string, Processor>
     */
    public function getByContext(string $context): array
    {
        return $this->processors[$context] ?? [];
    }

    // ── Introspection ────────────────────────────────────────────────

    /**
     * List all registered context names.
     *
     * @return list<string>
     */
    public function getContextNames(): array
    {
        return array_keys($this->processors);
    }

    /**
     * List all processor names within a context.
     *
     * @return list<string>
     */
    public function getProcessorNames(string $context): array
    {
        if (! isset($this->processors[$context])) {
            return [];
        }

        return array_keys($this->processors[$context]);
    }

    /**
     * Total processor count across all contexts.
     */
    public function count(): int
    {
        $total = 0;

        foreach ($this->processors as $contextProcessors) {
            $total += \count($contextProcessors);
        }

        return $total;
    }

    // ── Mutation ─────────────────────────────────────────────────────

    /**
     * Remove a processor from the registry.
     *
     * @return bool True if the processor existed and was removed
     */
    public function remove(string $context, string $processorName): bool
    {
        if (! $this->has($context, $processorName)) {
            return false;
        }

        unset($this->processors[$context][$processorName]);

        // Clean up empty contexts
        if ($this->processors[$context] === []) {
            unset($this->processors[$context]);
        }

        return true;
    }

    /**
     * Remove all processors from the registry.
     */
    public function clear(): void
    {
        $this->processors = [];
    }
}
