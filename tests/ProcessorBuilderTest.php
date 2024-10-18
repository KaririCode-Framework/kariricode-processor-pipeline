<?php

declare(strict_types=1);

namespace KaririCode\Tests\ProcessorPipeline;

use KaririCode\Contract\Processor\ConfigurableProcessor;
use KaririCode\Contract\Processor\Pipeline;
use KaririCode\Contract\Processor\Processor;
use KaririCode\ProcessorPipeline\ProcessorBuilder;
use KaririCode\ProcessorPipeline\ProcessorPipeline;
use KaririCode\ProcessorPipeline\ProcessorRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProcessorBuilderTest extends TestCase
{
    private ProcessorRegistry|MockObject $registry;
    private ProcessorBuilder $builder;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ProcessorRegistry::class);
        $this->builder = new ProcessorBuilder($this->registry);
    }

    public function testBuildNonConfigurableProcessor(): void
    {
        $processor = $this->createMock(Processor::class);
        $this->registry->expects($this->once())
            ->method('get')
            ->with('context', 'name')
            ->willReturn($processor);

        $result = $this->builder->build('context', 'name');
        $this->assertSame($processor, $result);
    }

    public function testBuildConfigurableProcessor(): void
    {
        $processor = $this->createMock(ConfigurableProcessor::class);
        $this->registry->expects($this->once())
            ->method('get')
            ->with('context', 'name')
            ->willReturn($processor);

        $processor->expects($this->once())
            ->method('configure')
            ->with(['option' => 'value']);

        $result = $this->builder->build('context', 'name', ['option' => 'value']);
        $this->assertSame($processor, $result);
    }

    public function testBuildConfigurableProcessorWithEmptyConfig(): void
    {
        $processor = $this->createMock(ConfigurableProcessor::class);
        $this->registry->expects($this->once())
            ->method('get')
            ->with('context', 'name')
            ->willReturn($processor);

        $processor->expects($this->never())
            ->method('configure');

        $result = $this->builder->build('context', 'name', []);
        $this->assertSame($processor, $result);
    }

    public function testBuildPipelineWithVariousProcessorTypes(): void
    {
        $processor1 = $this->createMock(Processor::class);
        $processor2 = $this->createMock(ConfigurableProcessor::class);
        $processor3 = $this->createMock(Processor::class);

        $this->registry->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                ['context', 'processor1', $processor1],
                ['context', 'processor2', $processor2],
                ['context', 'processor3', $processor3],
            ]);

        $processor2->expects($this->once())
            ->method('configure')
            ->with(['option' => 'value']);

        $result = $this->builder->buildPipeline('context', [
            'processor1' => true,
            'processor2' => ['option' => 'value'],
            'processor3' => [],
        ]);

        $this->assertInstanceOf(Pipeline::class, $result);
        $this->assertInstanceOf(ProcessorPipeline::class, $result);
    }

    public function testBuildPipelineWithInvalidProcessorSpec(): void
    {
        $processor = $this->createMock(Processor::class);

        $this->registry->expects($this->once())
            ->method('get')
            ->with('context', 'validProcessor')
            ->willReturn($processor);

        $result = $this->builder->buildPipeline('context', [
            'validProcessor' => true,
            'invalidProcessor' => false,
            'anotherInvalidProcessor' => null,
        ]);

        $this->assertInstanceOf(Pipeline::class, $result);
    }

    public function testBuildPipelineWithEmptySpecs(): void
    {
        $result = $this->builder->buildPipeline('context', []);

        $this->assertInstanceOf(Pipeline::class, $result);
        $this->assertInstanceOf(ProcessorPipeline::class, $result);
    }
}
