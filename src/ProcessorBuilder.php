<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline;

use KaririCode\Contract\Processor\ConfigurableProcessor;
use KaririCode\Contract\Processor\Processor;
use KaririCode\ProcessorPipeline\Exception\InvalidProcessorConfigurationException;
use KaririCode\ProcessorPipeline\Exception\ProcessorNotFoundException;
use KaririCode\ProcessorPipeline\Pipeline\Pipeline;

/**
 * Builds Pipeline instances from a ProcessorRegistry and a specification.
 *
 * Overview
 * ========
 *
 * ProcessorBuilder resolves processor names against a {@see ProcessorRegistry},
 * optionally configures {@see ConfigurableProcessor} instances, and assembles
 * them into an immutable {@see Pipeline}.
 *
 * ARFA 1.3 Compliance
 * ===================
 *
 * P1 (Immutable State Transformation):
 *   The built Pipeline is immutable. ProcessorBuilder itself is stateless
 *   beyond its registry reference.
 *
 * P2 (Reactive Flow Composition):
 *   Pipeline construction is the composition step: f₁ ∘ f₂ ∘ ... ∘ fₙ
 *   where each fᵢ is resolved from the registry by name.
 *
 * P3 (Adaptive Context Awareness):
 *   ConfigurableProcessors are configured at build time, enabling
 *   context-dependent processor behaviour per pipeline instance.
 *
 * Usage
 * =====
 *
 * ## Simple Pipeline (name list)
 *
 * ```php
 * $builder = new ProcessorBuilder($registry);
 * $pipeline = $builder->buildPipeline('validator', ['required', 'email']);
 * $result = $pipeline->process($input);
 * ```
 *
 * ## Configured Pipeline (name → options map)
 *
 * ```php
 * $pipeline = $builder->buildPipeline('validator', [
 *     'required' => false,                        // enabled flag
 *     'length' => ['minLength' => 3, 'maxLength' => 50],
 *     'email' => true,                            // enabled, no config
 * ]);
 * ```
 *
 * ## Processor Specification Format
 *
 * The `$processorSpecs` parameter accepts:
 *
 * - `string[]`: Simple name list. All processors are enabled with defaults.
 * - `array<string, bool|array>`: Map where:
 *   - `bool false` → processor is **skipped**
 *   - `bool true`  → processor is enabled with defaults
 *   - `array`      → processor is enabled and configured with the array
 *
 * Design Decisions
 * ================
 *
 * 1. **Specification flexibility**: Both `['required', 'email']` and
 *    `['required' => true, 'email' => ['strict' => true]]` are supported.
 *    This mirrors the Attribute-based DSL used by Validator and Sanitizer.
 *
 * 2. **Configuration is per-build**: Calling `configure()` on a processor
 *    affects that processor globally if it's a shared instance. For isolation,
 *    register separate instances or use a factory pattern.
 *
 * @package   KaririCode\ProcessorPipeline
 * @author    Walmir Silva <walmir.silva@kariricode.org>
 * @copyright 2025 KaririCode
 * @license   MIT
 * @version   2.0.0
 * @since     1.0.0
 *
 * @see \KaririCode\ProcessorPipeline\ProcessorRegistry  Processor source
 * @see \KaririCode\ProcessorPipeline\Pipeline\Pipeline   Built product
 * @see \KaririCode\Contract\Processor\ConfigurableProcessor Configuration
 */
final readonly class ProcessorBuilder
{
    public function __construct(
        private ProcessorRegistry $registry,
    ) {
    }

    /**
     * Build a pipeline from a context and processor specification.
     *
     * @param string                       $context        Registry context name
     * @param array<int|string, mixed>     $processorSpecs Processor specification
     *
     * @return Pipeline Immutable pipeline ready for execution
     *
     * @throws ProcessorNotFoundException             When a processor name is not in the registry
     * @throws InvalidProcessorConfigurationException When configuration fails
     */
    public function buildPipeline(string $context, array $processorSpecs): Pipeline
    {
        $processors = [];

        foreach ($processorSpecs as $key => $value) {
            $resolved = $this->resolveSpec($key, $value);

            if ($resolved === null) {
                continue;
            }

            [$processorName, $configuration] = $resolved;

            $processor = $this->registry->get($context, $processorName);

            if ($configuration !== null) {
                $this->configureProcessor($processor, $processorName, $configuration);
            }

            $processors[] = $processor;
        }

        return new Pipeline($processors);
    }

    /**
     * Resolve a specification entry into a processor name and optional configuration.
     *
     * @return array{0: string, 1: array<string, mixed>|null}|null Null means "skip this processor"
     */
    private function resolveSpec(int|string $key, mixed $value): ?array
    {
        // Format: ['required', 'email'] → sequential list
        if (\is_int($key) && \is_string($value)) {
            return [$value, null];
        }

        // Format: ['required' => false] → disabled
        if (\is_string($key) && $value === false) {
            return null;
        }

        // Format: ['required' => true] → enabled, no config
        if (\is_string($key) && $value === true) {
            return [$key, null];
        }

        // Format: ['length' => ['minLength' => 3]] → enabled with config
        if (\is_string($key) && \is_array($value)) {
            /** @var array<string, mixed> $stringKeyedValue */
            $stringKeyedValue = $value;

            return [$key, $stringKeyedValue];
        }

        return null;
    }

    /**
     * Apply configuration to a ConfigurableProcessor.
     *
     * @param Processor             $processor     Target processor
     * @param string                $processorName Name for error reporting
     * @param array<string, mixed>  $configuration Configuration options
     *
     * @throws InvalidProcessorConfigurationException When processor is not configurable
     */
    private function configureProcessor(
        Processor $processor,
        string $processorName,
        array $configuration,
    ): void {
        if (! $processor instanceof ConfigurableProcessor) {
            throw InvalidProcessorConfigurationException::forProcessor(
                $processorName,
                'Processor does not implement ConfigurableProcessor.',
            );
        }

        $processor->configure($configuration);
    }
}
