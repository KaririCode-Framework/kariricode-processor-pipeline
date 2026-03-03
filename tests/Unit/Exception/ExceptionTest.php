<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests\Unit\Exception;

use KaririCode\ProcessorPipeline\Exception\InvalidProcessorConfigurationException;
use KaririCode\ProcessorPipeline\Exception\PipelineExecutionException;
use KaririCode\ProcessorPipeline\Exception\ProcessorNotFoundException;
use KaririCode\ProcessorPipeline\Exception\ProcessorPipelineException;
use PHPUnit\Framework\TestCase;

final class ExceptionTest extends TestCase
{
    // ── ProcessorPipelineException (base) ────────────────────────────

    public function testBaseExceptionCarriesContext(): void
    {
        $exception = new ProcessorPipelineException(
            message: 'test',
            context: ['key' => 'value'],
        );

        $this->assertSame('test', $exception->getMessage());
        $this->assertSame(['key' => 'value'], $exception->context);
    }

    public function testBaseExceptionDefaultsToEmptyContext(): void
    {
        $exception = new ProcessorPipelineException('msg');

        $this->assertSame([], $exception->context);
    }

    public function testBaseExceptionAcceptsPreviousThrowable(): void
    {
        $cause = new \RuntimeException('root');
        $exception = new ProcessorPipelineException('wrapped', previous: $cause);

        $this->assertSame($cause, $exception->getPrevious());
    }

    public function testBaseExceptionExtendsRuntimeException(): void
    {
        $exception = new ProcessorPipelineException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    // ── ProcessorNotFoundException ───────────────────────────────────

    public function testForNameInContextCreatesCorrectMessage(): void
    {
        $exception = ProcessorNotFoundException::forNameInContext('trim', 'validator');

        $this->assertStringContainsString('trim', $exception->getMessage());
        $this->assertStringContainsString('validator', $exception->getMessage());
        $this->assertSame('trim', $exception->context['processor']);
        $this->assertSame('validator', $exception->context['context']);
    }

    public function testForNameCreatesCorrectMessage(): void
    {
        $exception = ProcessorNotFoundException::forName('missing');

        $this->assertStringContainsString('missing', $exception->getMessage());
        $this->assertSame('missing', $exception->context['processor']);
        $this->assertArrayNotHasKey('context', $exception->context);
    }

    public function testProcessorNotFoundExtendsBase(): void
    {
        $exception = ProcessorNotFoundException::forName('x');

        $this->assertInstanceOf(ProcessorPipelineException::class, $exception);
    }

    // ── PipelineExecutionException ──────────────────────────────────

    public function testAtStageCreatesWithStageAndProcessor(): void
    {
        $cause = new \InvalidArgumentException('bad input');
        $exception = PipelineExecutionException::atStage('TrimProcessor', 2, $cause);

        $this->assertStringContainsString('stage 2', $exception->getMessage());
        $this->assertStringContainsString('TrimProcessor', $exception->getMessage());
        $this->assertStringContainsString('bad input', $exception->getMessage());
        $this->assertSame($cause, $exception->getPrevious());
    }

    public function testAtStageCarriesStructuredContext(): void
    {
        $cause = new \RuntimeException('fail');
        $exception = PipelineExecutionException::atStage('Proc', 5, $cause);

        $this->assertSame('Proc', $exception->context['processor']);
        $this->assertSame(5, $exception->context['stage']);
        $this->assertSame(\RuntimeException::class, $exception->context['causeClass']);
    }

    public function testAtStageWithStageZero(): void
    {
        $cause = new \RuntimeException('first stage fail');
        $exception = PipelineExecutionException::atStage('First', 0, $cause);

        $this->assertSame(0, $exception->context['stage']);
    }

    public function testPipelineExecutionExtendsBase(): void
    {
        $exception = PipelineExecutionException::atStage('P', 0, new \RuntimeException());

        $this->assertInstanceOf(ProcessorPipelineException::class, $exception);
    }

    // ── InvalidProcessorConfigurationException ──────────────────────

    public function testForProcessorCreatesWithNameAndReason(): void
    {
        $exception = InvalidProcessorConfigurationException::forProcessor(
            'length',
            'Processor does not implement ConfigurableProcessor.',
        );

        $this->assertStringContainsString('length', $exception->getMessage());
        $this->assertStringContainsString('ConfigurableProcessor', $exception->getMessage());
        $this->assertSame('length', $exception->context['processor']);
        $this->assertSame(
            'Processor does not implement ConfigurableProcessor.',
            $exception->context['reason'],
        );
    }

    public function testInvalidConfigExtendsBase(): void
    {
        $exception = InvalidProcessorConfigurationException::forProcessor('p', 'r');

        $this->assertInstanceOf(ProcessorPipelineException::class, $exception);
    }

    // ── Catchability Hierarchy ──────────────────────────────────────

    public function testAllExceptionsAreCatchableByBase(): void
    {
        $exceptions = [
            ProcessorNotFoundException::forName('x'),
            PipelineExecutionException::atStage('P', 0, new \RuntimeException()),
            InvalidProcessorConfigurationException::forProcessor('p', 'r'),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(ProcessorPipelineException::class, $exception);
            $this->assertInstanceOf(\RuntimeException::class, $exception);
        }
    }
}
