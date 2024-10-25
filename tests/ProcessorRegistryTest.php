<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests;

use KaririCode\Contract\Processor\Processor;
use KaririCode\DataStructure\Map\HashMap;
use KaririCode\ProcessorPipeline\Exception\ProcessorRuntimeException;
use KaririCode\ProcessorPipeline\ProcessorRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProcessorRegistryTest extends TestCase
{
    private ProcessorRegistry $registry;
    private HashMap|MockObject $mockHashMap;

    protected function setUp(): void
    {
        $this->mockHashMap = $this->createMock(HashMap::class);
        $this->registry = new ProcessorRegistry($this->mockHashMap);
    }

    public function testRegister(): void
    {
        $processor = $this->createMock(Processor::class);
        $contextMap = $this->createMock(HashMap::class);

        $this->mockHashMap->expects($this->once())
            ->method('containsKey')
            ->with('payment')
            ->willReturn(false);

        $this->mockHashMap->expects($this->once())
            ->method('put')
            ->with('payment', $this->isInstanceOf(HashMap::class));

        $this->mockHashMap->expects($this->once())
            ->method('get')
            ->with('payment')
            ->willReturn($contextMap);

        $contextMap->expects($this->once())
            ->method('put')
            ->with('validate', $processor);

        $this->registry->register('payment', 'validate', $processor);
    }

    public function testGet(): void
    {
        $processor = $this->createMock(Processor::class);
        $contextMap = $this->createMock(HashMap::class);

        $this->mockHashMap->expects($this->once())
            ->method('containsKey')
            ->with('payment')
            ->willReturn(true);

        $this->mockHashMap->expects($this->once())
            ->method('get')
            ->with('payment')
            ->willReturn($contextMap);

        $contextMap->expects($this->once())
            ->method('containsKey')
            ->with('validate')
            ->willReturn(true);

        $contextMap->expects($this->once())
            ->method('get')
            ->with('validate')
            ->willReturn($processor);

        $result = $this->registry->get('payment', 'validate');
        $this->assertSame($processor, $result);
    }

    public function testGetContextNotFound(): void
    {
        $this->mockHashMap->expects($this->once())
            ->method('containsKey')
            ->with('payment')
            ->willReturn(false);

        $this->expectException(ProcessorRuntimeException::class);
        $this->expectExceptionMessage("Processor context 'payment' not found");

        $this->registry->get('payment', 'validate');
    }

    public function testGetProcessorNotFound(): void
    {
        $contextMap = $this->createMock(HashMap::class);

        $this->mockHashMap->expects($this->once())
            ->method('containsKey')
            ->with('payment')
            ->willReturn(true);

        $this->mockHashMap->expects($this->once())
            ->method('get')
            ->with('payment')
            ->willReturn($contextMap);

        $contextMap->expects($this->once())
            ->method('containsKey')
            ->with('validate')
            ->willReturn(false);

        $this->expectException(ProcessorRuntimeException::class);
        $this->expectExceptionMessage("Processor 'validate' not found in context 'payment'");

        $this->registry->get('payment', 'validate');
    }

    public function testGetContextProcessors(): void
    {
        $contextMap = $this->createMock(HashMap::class);

        $this->mockHashMap->expects($this->once())
            ->method('containsKey')
            ->with('payment')
            ->willReturn(true);

        $this->mockHashMap->expects($this->once())
            ->method('get')
            ->with('payment')
            ->willReturn($contextMap);

        $result = $this->registry->getContextProcessors('payment');
        $this->assertSame($contextMap, $result);
    }

    public function testGetContextProcessorsNotFound(): void
    {
        $this->mockHashMap->expects($this->once())
            ->method('containsKey')
            ->with('payment')
            ->willReturn(false);

        $this->expectException(ProcessorRuntimeException::class);
        $this->expectExceptionMessage("Processor context 'payment' not found");

        $this->registry->getContextProcessors('payment');
    }
}
