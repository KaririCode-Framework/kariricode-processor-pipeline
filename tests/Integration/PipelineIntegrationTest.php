<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests\Integration;

use KaririCode\ProcessorPipeline\Exception\PipelineExecutionException;
use KaririCode\ProcessorPipeline\Handler\ProcessorHandler;
use KaririCode\ProcessorPipeline\Pipeline\Pipeline;
use KaririCode\ProcessorPipeline\ProcessorBuilder;
use KaririCode\ProcessorPipeline\ProcessorRegistry;
use KaririCode\ProcessorPipeline\Result\ProcessingResultCollection;
use KaririCode\ProcessorPipeline\Tests\Stubs\StubConfigurableProcessor;
use KaririCode\ProcessorPipeline\Tests\Stubs\StubFailingProcessor;
use KaririCode\ProcessorPipeline\Tests\Stubs\StubProcessor;
use PHPUnit\Framework\TestCase;

final class PipelineIntegrationTest extends TestCase
{
    private ProcessorRegistry $registry;
    private ProcessorBuilder $builder;

    protected function setUp(): void
    {
        $this->registry = new ProcessorRegistry();
        $this->builder = new ProcessorBuilder($this->registry);
    }

    // ── Full Lifecycle: Registry → Builder → Pipeline → Execution ───

    public function testFullSanitizerPipeline(): void
    {
        $this->registry
            ->register('sanitizer', 'trim', StubProcessor::trim())
            ->register('sanitizer', 'lowercase', StubProcessor::lowercase())
            ->register('sanitizer', 'append', StubProcessor::append('!'));

        $pipeline = $this->builder->buildPipeline('sanitizer', [
            'trim',
            'lowercase',
            'append',
        ]);

        $result = $pipeline->process('  HELLO WORLD  ');

        $this->assertSame('hello world!', $result);
    }

    public function testFullConfiguredValidatorPipeline(): void
    {
        $lengthValidator = new StubConfigurableProcessor();

        $this->registry
            ->register('validator', 'trim', StubProcessor::trim())
            ->register('validator', 'length', $lengthValidator);

        $pipeline = $this->builder->buildPipeline('validator', [
            'trim',
            'length' => ['minLength' => 3, 'maxLength' => 20],
        ]);

        // Valid input
        $result = $pipeline->process('  hello  ');
        $this->assertSame('hello', $result);

        // Invalid input — too short after trim
        $this->expectException(PipelineExecutionException::class);
        $pipeline->process('  ab  ');
    }

    // ── Multi-Context Isolation ─────────────────────────────────────

    public function testMultiContextProcessorsOperateIndependently(): void
    {
        // Same name, different contexts
        $this->registry->register('sanitizer', 'transform', StubProcessor::lowercase());
        $this->registry->register('formatter', 'transform', StubProcessor::uppercase());

        $sanitizerPipeline = $this->builder->buildPipeline('sanitizer', ['transform']);
        $formatterPipeline = $this->builder->buildPipeline('formatter', ['transform']);

        $this->assertSame('hello', $sanitizerPipeline->process('HELLO'));
        $this->assertSame('HELLO', $formatterPipeline->process('hello'));
    }

    // ── Pipeline Composition (ARFA P1 — Immutability) ───────────────

    public function testPipelineCompositionProducesCorrectResults(): void
    {
        $this->registry
            ->register('ctx', 'trim', StubProcessor::trim())
            ->register('ctx', 'lower', StubProcessor::lowercase())
            ->register('ctx', 'upper', StubProcessor::uppercase());

        $basePipeline = $this->builder->buildPipeline('ctx', ['trim']);

        // Extend base in two different directions
        $lowerPipeline = $basePipeline->withProcessor(StubProcessor::lowercase());
        $upperPipeline = $basePipeline->withProcessor(StubProcessor::uppercase());

        $input = '  HeLLo  ';

        // Base only trims
        $this->assertSame('HeLLo', $basePipeline->process($input));

        // Lower extends with lowercase
        $this->assertSame('hello', $lowerPipeline->process($input));

        // Upper extends with uppercase
        $this->assertSame('HELLO', $upperPipeline->process($input));

        // Base is unchanged
        $this->assertSame(1, $basePipeline->count());
    }

    public function testPipelineMergingPreservesOrder(): void
    {
        $first = new Pipeline([
            StubProcessor::trim(),
            StubProcessor::lowercase(),
        ]);

        $second = new Pipeline([
            StubProcessor::append('!'),
        ]);

        $merged = $first->withPipeline($second);

        $this->assertSame('hello!', $merged->process('  HELLO  '));
    }

    // ── Error Collection with ProcessorHandler ──────────────────────

    public function testHandlerCollectsErrorsWithoutHalting(): void
    {
        $results = new ProcessingResultCollection();

        $handlers = [
            new ProcessorHandler(
                processor: StubProcessor::trim(),
                resultCollection: $results,
            ),
            new ProcessorHandler(
                processor: StubFailingProcessor::withMessage('validation error'),
                resultCollection: $results,
                haltOnError: false,
            ),
            new ProcessorHandler(
                processor: StubProcessor::uppercase(),
                resultCollection: $results,
            ),
        ];

        $pipeline = new Pipeline($handlers);
        $result = $pipeline->process('  hello  ');

        // Pipeline continues despite error in handler 2
        // Handler 2 returns input passthrough → 'hello' (trimmed by handler 1)
        // Handler 3 uppercases → 'HELLO'
        $this->assertSame('HELLO', $result);

        // Error was recorded
        $this->assertTrue($results->hasErrors);
        $this->assertSame(1, $results->errorCount);

        // All 3 were traced
        $this->assertCount(3, $results->executionTrace);
    }

    public function testHandlerHaltsOnErrorWhenConfigured(): void
    {
        $results = new ProcessingResultCollection();

        $handlers = [
            new ProcessorHandler(
                processor: StubProcessor::trim(),
                resultCollection: $results,
            ),
            new ProcessorHandler(
                processor: StubFailingProcessor::withMessage('halt here'),
                resultCollection: $results,
                haltOnError: true,
            ),
            new ProcessorHandler(
                processor: StubProcessor::uppercase(),
                resultCollection: $results,
            ),
        ];

        $pipeline = new Pipeline($handlers);

        // PipelineExecutionException wraps the re-thrown RuntimeException
        $this->expectException(PipelineExecutionException::class);
        $pipeline->process('  hello  ');
    }

    // ── Merge Results from Parallel Pipelines ───────────────────────

    public function testMergeResultsFromParallelPipelines(): void
    {
        $results1 = new ProcessingResultCollection();
        $results2 = new ProcessingResultCollection();

        $pipeline1 = new Pipeline([
            new ProcessorHandler(
                processor: StubFailingProcessor::withMessage('err1'),
                resultCollection: $results1,
                haltOnError: false,
            ),
        ]);

        $pipeline2 = new Pipeline([
            new ProcessorHandler(
                processor: StubFailingProcessor::withMessage('err2'),
                resultCollection: $results2,
                haltOnError: false,
            ),
        ]);

        $pipeline1->process('input1');
        $pipeline2->process('input2');

        // Merge into results1
        $results1->merge($results2);

        $this->assertSame(2, $results1->errorCount);
    }

    // ── Selective Spec with Builder ─────────────────────────────────

    public function testBuilderSelectiveSpecProducesCorrectPipeline(): void
    {
        $this->registry
            ->register('ctx', 'trim', StubProcessor::trim())
            ->register('ctx', 'lower', StubProcessor::lowercase())
            ->register('ctx', 'upper', StubProcessor::uppercase())
            ->register('ctx', 'append', StubProcessor::append('!'));

        $pipeline = $this->builder->buildPipeline('ctx', [
            'trim' => true,
            'lower' => false,           // disabled
            'upper' => true,
            'append' => false,          // disabled
        ]);

        $this->assertSame(2, $pipeline->count());
        $this->assertSame('HELLO', $pipeline->process('  hello  '));
    }

    // ── Large Pipeline Stress ───────────────────────────────────────

    public function testLargePipelineExecutesCorrectly(): void
    {
        $processors = [];
        for ($i = 0; $i < 100; $i++) {
            $processors[] = StubProcessor::identity();
        }

        $pipeline = new Pipeline($processors);
        $result = $pipeline->process('data');

        $this->assertSame('data', $result);
        $this->assertSame(100, $pipeline->count());
    }

    // ── Empty Processing ────────────────────────────────────────────

    public function testEmptyPipelineFromBuilderProcessesCorrectly(): void
    {
        $pipeline = $this->builder->buildPipeline('ctx', []);

        $this->assertSame('untouched', $pipeline->process('untouched'));
    }

    // ── Registry Cleanup Doesn't Affect Built Pipelines ─────────────

    public function testRegistryClearDoesNotAffectExistingPipelines(): void
    {
        $this->registry->register('ctx', 'trim', StubProcessor::trim());

        $pipeline = $this->builder->buildPipeline('ctx', ['trim']);

        // Clear the registry
        $this->registry->clear();

        // Pipeline still works — it holds processor references
        $this->assertSame('hello', $pipeline->process('  hello  '));
    }
}
