<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests;

use KaririCode\Contract\Processor\ValidatableProcessor;
use KaririCode\ProcessorPipeline\Exception\ProcessingException;
use KaririCode\ProcessorPipeline\ProcessorPipeline;
use PHPUnit\Framework\TestCase;

final class ProcessorPipelineTest extends TestCase
{
    private ProcessorPipeline $pipeline;

    protected function setUp(): void
    {
        $this->pipeline = new ProcessorPipeline();
    }

    public function testAddProcessor(): void
    {
        $processor = $this->createMock(ValidatableProcessor::class);

        $result = $this->pipeline->addProcessor($processor);

        $this->assertSame($this->pipeline, $result);
        $this->assertTrue($this->pipeline->hasProcessors());
        $this->assertSame(1, $this->pipeline->count());
        $this->assertSame([$processor], $this->pipeline->getProcessors());
    }

    public function testProcessWithValidatableProcessor(): void
    {
        $processor = $this->createMock(ValidatableProcessor::class);
        $processor->expects($this->once())
            ->method('reset');
        $processor->expects($this->once())
            ->method('process')
            ->with('input')
            ->willReturn('processed');

        $this->pipeline->addProcessor($processor);
        $result = $this->pipeline->process('input');

        $this->assertSame('processed', $result);
    }

    public function testProcessorWithException(): void
    {
        $processor = $this->createMock(ValidatableProcessor::class);
        $processor->expects($this->once())
            ->method('process')
            ->willThrowException(new \Exception('Processing failed'));

        $this->pipeline->addProcessor($processor);

        $this->expectException(ProcessingException::class);
        $this->expectExceptionMessage('Pipeline processing failed');

        $this->pipeline->process('input');
    }

    public function testClear(): void
    {
        $processor = $this->createMock(ValidatableProcessor::class);
        $this->pipeline->addProcessor($processor);

        $this->assertTrue($this->pipeline->hasProcessors());

        $this->pipeline->clear();

        $this->assertFalse($this->pipeline->hasProcessors());
        $this->assertSame(0, $this->pipeline->count());
        $this->assertEmpty($this->pipeline->getProcessors());
    }

    public function testProcessWithMultipleProcessors(): void
    {
        $processor1 = $this->createMock(ValidatableProcessor::class);
        $processor1->expects($this->once())
            ->method('process')
            ->with('input')
            ->willReturn('processed1');

        $processor2 = $this->createMock(ValidatableProcessor::class);
        $processor2->expects($this->once())
            ->method('process')
            ->with('processed1')
            ->willReturn('processed2');

        $this->pipeline->addProcessor($processor1);
        $this->pipeline->addProcessor($processor2);

        $result = $this->pipeline->process('input');

        $this->assertSame('processed2', $result);
        $this->assertSame(2, $this->pipeline->count());
    }

    public function testProcessWithNoProcessors(): void
    {
        $input = 'test input';
        $result = $this->pipeline->process($input);

        $this->assertSame($input, $result);
        $this->assertFalse($this->pipeline->hasProcessors());
        $this->assertSame(0, $this->pipeline->count());
    }

    public function testPipelineExecutionFailure(): void
    {
        $processor = $this->createMock(ValidatableProcessor::class);
        $processor->method('process')
            ->willThrowException(new \RuntimeException('Internal error'));

        $this->pipeline->addProcessor($processor);

        try {
            $this->pipeline->process('input');
            $this->fail('Expected ProcessingException was not thrown');
        } catch (ProcessingException $e) {
            $this->assertSame('PIPELINE_FAILED', $e->getErrorCode());
            $this->assertSame(3001, $e->getCode());
            $this->assertSame('Pipeline processing failed', $e->getMessage());
        }
    }
}
