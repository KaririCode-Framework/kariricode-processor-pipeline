<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests\Unit\Pipeline;

use KaririCode\ProcessorPipeline\Exception\PipelineExecutionException;
use KaririCode\ProcessorPipeline\Pipeline\Pipeline;
use KaririCode\ProcessorPipeline\Tests\Stubs\StubFailingProcessor;
use KaririCode\ProcessorPipeline\Tests\Stubs\StubProcessor;
use PHPUnit\Framework\TestCase;

final class PipelineTest extends TestCase
{
    // ── Construction ────────────────────────────────────────────────

    public function testEmptyPipelineIsValid(): void
    {
        $pipeline = new Pipeline();

        $this->assertTrue($pipeline->isEmpty());
        $this->assertSame(0, $pipeline->count());
        $this->assertSame([], $pipeline->getProcessors());
    }

    public function testConstructWithProcessors(): void
    {
        $p1 = StubProcessor::trim();
        $p2 = StubProcessor::uppercase();

        $pipeline = new Pipeline([$p1, $p2]);

        $this->assertSame(2, $pipeline->count());
        $this->assertFalse($pipeline->isEmpty());
    }

    public function testConstructNormalizesArrayKeys(): void
    {
        // Associative array keys should be normalized to sequential list
        $p1 = StubProcessor::trim();
        $p2 = StubProcessor::uppercase();

        $pipeline = new Pipeline([5 => $p1, 10 => $p2]);
        $processors = $pipeline->getProcessors();

        $this->assertSame(0, array_key_first($processors));
        $this->assertSame(1, array_key_last($processors));
    }

    // ── Execution ───────────────────────────────────────────────────

    public function testProcessAppliesProcessorsSequentially(): void
    {
        $pipeline = new Pipeline([
            StubProcessor::trim(),
            StubProcessor::uppercase(),
        ]);

        $result = $pipeline->process('  hello world  ');

        $this->assertSame('HELLO WORLD', $result);
    }

    public function testProcessPassthroughOnEmptyPipeline(): void
    {
        $pipeline = new Pipeline();

        $this->assertSame('unchanged', $pipeline->process('unchanged'));
    }

    public function testProcessPreservesTypeForNonStringInput(): void
    {
        $pipeline = new Pipeline([StubProcessor::identity()]);

        $this->assertSame(42, $pipeline->process(42));
        $this->assertSame(['a', 'b'], $pipeline->process(['a', 'b']));
        $this->assertNull($pipeline->process(null));
    }

    public function testProcessChainsMultipleTransformations(): void
    {
        $pipeline = new Pipeline([
            StubProcessor::trim(),
            StubProcessor::lowercase(),
            StubProcessor::append('!'),
        ]);

        $result = $pipeline->process('  HELLO  ');

        $this->assertSame('hello!', $result);
    }

    public function testProcessSingleProcessorPipeline(): void
    {
        $pipeline = new Pipeline([StubProcessor::uppercase()]);

        $this->assertSame('FOO', $pipeline->process('foo'));
    }

    // ── Execution Errors ────────────────────────────────────────────

    public function testProcessWrapsExceptionInPipelineExecutionException(): void
    {
        $pipeline = new Pipeline([
            StubProcessor::trim(),
            StubFailingProcessor::withMessage('boom'),
        ]);

        $this->expectException(PipelineExecutionException::class);
        $this->expectExceptionMessage('Pipeline failed at stage 1');

        $pipeline->process('input');
    }

    public function testProcessPreservesOriginalExceptionAsPrevious(): void
    {
        $original = new \LogicException('root cause');
        $pipeline = new Pipeline([
            StubFailingProcessor::withException($original),
        ]);

        try {
            $pipeline->process('input');
            $this->fail('Expected PipelineExecutionException');
        } catch (PipelineExecutionException $exception) {
            $this->assertSame($original, $exception->getPrevious());
            $this->assertSame(0, $exception->context['stage']);
            $this->assertStringContainsString('root cause', $exception->getMessage());
        }
    }

    public function testProcessReThrowsPipelineExecutionExceptionWithoutWrapping(): void
    {
        // A PipelineExecutionException from a nested pipeline should not double-wrap
        $inner = PipelineExecutionException::atStage('InnerProcessor', 0, new \RuntimeException('inner'));
        $pipeline = new Pipeline([
            StubFailingProcessor::withException($inner),
        ]);

        try {
            $pipeline->process('input');
            $this->fail('Expected PipelineExecutionException');
        } catch (PipelineExecutionException $exception) {
            // Should be the same exception, not a wrapper
            $this->assertSame($inner, $exception);
        }
    }

    public function testProcessReportsCorrectStageIndex(): void
    {
        $pipeline = new Pipeline([
            StubProcessor::identity(),
            StubProcessor::identity(),
            StubFailingProcessor::withMessage('fail at stage 2'),
        ]);

        try {
            $pipeline->process('input');
            $this->fail('Expected PipelineExecutionException');
        } catch (PipelineExecutionException $exception) {
            $this->assertSame(2, $exception->context['stage']);
        }
    }

    // ── Immutability (ARFA P1) ──────────────────────────────────────

    public function testWithProcessorReturnsNewInstance(): void
    {
        $original = new Pipeline([StubProcessor::trim()]);
        $extended = $original->withProcessor(StubProcessor::uppercase());

        $this->assertNotSame($original, $extended);
        $this->assertSame(1, $original->count());
        $this->assertSame(2, $extended->count());
    }

    public function testWithProcessorDoesNotModifyOriginal(): void
    {
        $original = new Pipeline();
        $original->withProcessor(StubProcessor::trim());

        $this->assertTrue($original->isEmpty());
    }

    public function testWithPipelineReturnsNewInstance(): void
    {
        $first = new Pipeline([StubProcessor::trim()]);
        $second = new Pipeline([StubProcessor::uppercase()]);

        $combined = $first->withPipeline($second);

        $this->assertNotSame($first, $combined);
        $this->assertNotSame($second, $combined);
        $this->assertSame(1, $first->count());
        $this->assertSame(1, $second->count());
        $this->assertSame(2, $combined->count());
    }

    public function testWithPipelinePreservesProcessorOrder(): void
    {
        $first = new Pipeline([StubProcessor::trim()]);
        $second = new Pipeline([StubProcessor::uppercase()]);

        $combined = $first->withPipeline($second);
        $result = $combined->process('  hello  ');

        // trim → uppercase
        $this->assertSame('HELLO', $result);
    }

    public function testImmutableChainComposition(): void
    {
        $base = new Pipeline();
        $step1 = $base->withProcessor(StubProcessor::trim());
        $step2 = $step1->withProcessor(StubProcessor::lowercase());
        $step3 = $step2->withProcessor(StubProcessor::append('!'));

        // Base remains empty
        $this->assertTrue($base->isEmpty());
        $this->assertSame(1, $step1->count());
        $this->assertSame(2, $step2->count());
        $this->assertSame(3, $step3->count());

        // Full chain produces correct result
        $this->assertSame('hello!', $step3->process('  HELLO  '));
    }

    // ── Introspection ───────────────────────────────────────────────

    public function testGetProcessorsReturnsList(): void
    {
        $p1 = StubProcessor::trim();
        $p2 = StubProcessor::uppercase();

        $pipeline = new Pipeline([$p1, $p2]);
        $processors = $pipeline->getProcessors();

        $this->assertSame($p1, $processors[0]);
        $this->assertSame($p2, $processors[1]);
    }

    public function testCountReturnsProcessorCount(): void
    {
        $pipeline = new Pipeline([
            StubProcessor::trim(),
            StubProcessor::uppercase(),
            StubProcessor::lowercase(),
        ]);

        $this->assertSame(3, $pipeline->count());
    }

    public function testIsEmptyReturnsTrueForEmptyPipeline(): void
    {
        $this->assertTrue(new Pipeline()->isEmpty());
    }

    public function testIsEmptyReturnsFalseForNonEmptyPipeline(): void
    {
        $pipeline = new Pipeline([StubProcessor::identity()]);

        $this->assertFalse($pipeline->isEmpty());
    }
}
