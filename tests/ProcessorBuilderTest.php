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

    public function testBuildPipeline(): void
    {
        $processor1 = $this->createMock(Processor::class);
        $processor2 = $this->createMock(ConfigurableProcessor::class);

        $this->registry->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function ($context, $name) use ($processor1, $processor2) {
                if ('context' === $context && 'processor1' === $name) {
                    return $processor1;
                }
                if ('context' === $context && 'processor2' === $name) {
                    return $processor2;
                }
                $this->fail('Unexpected get() call');
            });

        $processor2->expects($this->once())
            ->method('configure')
            ->with(['option' => 'value']);

        $result = $this->builder->buildPipeline('context', [
            'processor1',
            'processor2' => ['option' => 'value'],
        ]);

        $this->assertInstanceOf(Pipeline::class, $result);
        $this->assertInstanceOf(ProcessorPipeline::class, $result);
    }
}
