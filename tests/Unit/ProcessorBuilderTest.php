<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests\Unit;

use KaririCode\ProcessorPipeline\Exception\InvalidProcessorConfigurationException;
use KaririCode\ProcessorPipeline\Exception\ProcessorNotFoundException;
use KaririCode\ProcessorPipeline\Pipeline\Pipeline;
use KaririCode\ProcessorPipeline\ProcessorBuilder;
use KaririCode\ProcessorPipeline\ProcessorRegistry;
use KaririCode\ProcessorPipeline\Tests\Stubs\StubConfigurableProcessor;
use KaririCode\ProcessorPipeline\Tests\Stubs\StubProcessor;
use PHPUnit\Framework\TestCase;

final class ProcessorBuilderTest extends TestCase
{
    private ProcessorRegistry $registry;
    private ProcessorBuilder $builder;

    protected function setUp(): void
    {
        $this->registry = new ProcessorRegistry();
        $this->builder = new ProcessorBuilder($this->registry);
    }

    // ── Simple Specification (name list) ────────────────────────────

    public function testBuildPipelineFromSimpleNameList(): void
    {
        $this->registry->register('sanitizer', 'trim', StubProcessor::trim());
        $this->registry->register('sanitizer', 'upper', StubProcessor::uppercase());

        $pipeline = $this->builder->buildPipeline('sanitizer', ['trim', 'upper']);

        $this->assertInstanceOf(Pipeline::class, $pipeline);
        $this->assertSame(2, $pipeline->count());
    }

    public function testBuildPipelineExecutesCorrectly(): void
    {
        $this->registry->register('sanitizer', 'trim', StubProcessor::trim());
        $this->registry->register('sanitizer', 'upper', StubProcessor::uppercase());

        $pipeline = $this->builder->buildPipeline('sanitizer', ['trim', 'upper']);
        $result = $pipeline->process('  hello  ');

        $this->assertSame('HELLO', $result);
    }

    public function testBuildPipelinePreservesSpecOrder(): void
    {
        $this->registry->register('ctx', 'upper', StubProcessor::uppercase());
        $this->registry->register('ctx', 'append', StubProcessor::append('!'));

        $pipeline = $this->builder->buildPipeline('ctx', ['upper', 'append']);

        $this->assertSame('HELLO!', $pipeline->process('hello'));
    }

    // ── Enable/Disable Specification ────────────────────────────────

    public function testBuildPipelineSkipsDisabledProcessors(): void
    {
        $this->registry->register('ctx', 'trim', StubProcessor::trim());
        $this->registry->register('ctx', 'upper', StubProcessor::uppercase());

        $pipeline = $this->builder->buildPipeline('ctx', [
            'trim' => true,
            'upper' => false,
        ]);

        $this->assertSame(1, $pipeline->count());
        $this->assertSame('hello', $pipeline->process('  hello  '));
    }

    public function testBuildPipelineWithAllDisabled(): void
    {
        $this->registry->register('ctx', 'trim', StubProcessor::trim());

        $pipeline = $this->builder->buildPipeline('ctx', [
            'trim' => false,
        ]);

        $this->assertTrue($pipeline->isEmpty());
    }

    // ── Configuration Specification ─────────────────────────────────

    public function testBuildPipelineConfiguresConfigurableProcessor(): void
    {
        $configurable = new StubConfigurableProcessor();
        $this->registry->register('validator', 'length', $configurable);

        $this->builder->buildPipeline('validator', [
            'length' => ['minLength' => 3, 'maxLength' => 50],
        ]);

        $this->assertSame(['minLength' => 3, 'maxLength' => 50], $configurable->lastConfig);
    }

    public function testBuildPipelineThrowsWhenConfiguringNonConfigurableProcessor(): void
    {
        $this->registry->register('ctx', 'simple', StubProcessor::identity());

        $this->expectException(InvalidProcessorConfigurationException::class);
        $this->expectExceptionMessage("Invalid configuration for processor 'simple'");

        $this->builder->buildPipeline('ctx', [
            'simple' => ['key' => 'value'],
        ]);
    }

    // ── Mixed Specification ─────────────────────────────────────────

    public function testBuildPipelineMixedSpecificationFormat(): void
    {
        $configurable = new StubConfigurableProcessor();

        $this->registry->register('ctx', 'trim', StubProcessor::trim());
        $this->registry->register('ctx', 'upper', StubProcessor::uppercase());
        $this->registry->register('ctx', 'length', $configurable);
        $this->registry->register('ctx', 'skip', StubProcessor::identity());

        $pipeline = $this->builder->buildPipeline('ctx', [
            'trim',                                // sequential name
            'upper' => true,                       // enabled flag
            'length' => ['minLength' => 1],        // configured
            'skip' => false,                       // disabled
        ]);

        $this->assertSame(3, $pipeline->count());
        $this->assertSame(['minLength' => 1], $configurable->lastConfig);
    }

    // ── Empty Specification ─────────────────────────────────────────

    public function testBuildPipelineFromEmptySpec(): void
    {
        $pipeline = $this->builder->buildPipeline('ctx', []);

        $this->assertTrue($pipeline->isEmpty());
    }

    // ── Unknown Format Handling ─────────────────────────────────────

    public function testBuildPipelineIgnoresUnknownFormats(): void
    {
        $this->registry->register('ctx', 'trim', StubProcessor::trim());

        // int key + non-string value → skipped
        // string key + int value → skipped
        $pipeline = $this->builder->buildPipeline('ctx', [
            'trim',
            0 => 'trim',  // duplicate but valid
            'unknown' => 42,  // unknown format → skipped
        ]);

        // 'trim' appears once (index 0 overwritten by explicit 0 => 'trim')
        $this->assertGreaterThanOrEqual(1, $pipeline->count());
    }

    // ── Error Cases ─────────────────────────────────────────────────

    public function testBuildPipelineThrowsForUnregisteredProcessor(): void
    {
        $this->expectException(ProcessorNotFoundException::class);
        $this->expectExceptionMessage("Processor 'missing' not found in context 'ctx'.");

        $this->builder->buildPipeline('ctx', ['missing']);
    }

    public function testBuildPipelineThrowsForUnregisteredContext(): void
    {
        $this->expectException(ProcessorNotFoundException::class);

        $this->builder->buildPipeline('nonexistent', ['anything']);
    }

    // ── Immutability of Built Pipeline ──────────────────────────────

    public function testBuildPipelineReturnsImmutablePipeline(): void
    {
        $this->registry->register('ctx', 'trim', StubProcessor::trim());

        $pipeline = $this->builder->buildPipeline('ctx', ['trim']);
        $extended = $pipeline->withProcessor(StubProcessor::uppercase());

        $this->assertSame(1, $pipeline->count());
        $this->assertSame(2, $extended->count());
    }
}
