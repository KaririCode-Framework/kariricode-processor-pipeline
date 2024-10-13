<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests;

use KaririCode\Contract\Processor\Processor;
use KaririCode\DataStructure\Map\HashMap;
use KaririCode\ProcessorPipeline\ProcessorRegistry;
use PHPUnit\Framework\TestCase;

final class ProcessorRegistryTest extends TestCase
{
    private ProcessorRegistry $registry;
    private HashMap $mockHashMap;

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
            ->with('context')
            ->willReturn(false);

        $this->mockHashMap->expects($this->once())
            ->method('put')
            ->with('context', $this->isInstanceOf(HashMap::class));

        $this->mockHashMap->expects($this->once())
            ->method('get')
            ->with('context')
            ->willReturn($contextMap);

        $contextMap->expects($this->once())
            ->method('put')
            ->with('name', $processor);

        $this->registry->register('context', 'name', $processor);
    }

    public function testGet(): void
    {
        $processor = $this->createMock(Processor::class);
        $contextMap = $this->createMock(HashMap::class);

        $this->mockHashMap->expects($this->once())
            ->method('containsKey')
            ->with('context')
            ->willReturn(true);

        $this->mockHashMap->expects($this->once())
            ->method('get')
            ->with('context')
            ->willReturn($contextMap);

        $contextMap->expects($this->once())
            ->method('containsKey')
            ->with('name')
            ->willReturn(true);

        $contextMap->expects($this->once())
            ->method('get')
            ->with('name')
            ->willReturn($processor);

        $result = $this->registry->get('context', 'name');
        $this->assertSame($processor, $result);
    }

    public function testGetContextNotFound(): void
    {
        $this->mockHashMap->expects($this->once())
            ->method('containsKey')
            ->with('context')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Context 'context' not found.");

        $this->registry->get('context', 'name');
    }

    public function testGetProcessorNotFound(): void
    {
        $contextMap = $this->createMock(HashMap::class);

        $this->mockHashMap->expects($this->once())
            ->method('containsKey')
            ->with('context')
            ->willReturn(true);

        $this->mockHashMap->expects($this->once())
            ->method('get')
            ->with('context')
            ->willReturn($contextMap);

        $contextMap->expects($this->once())
            ->method('containsKey')
            ->with('name')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Processor 'name' not found in context 'context'.");

        $this->registry->get('context', 'name');
    }

    public function testGetContextProcessors(): void
    {
        $contextMap = $this->createMock(HashMap::class);

        $this->mockHashMap->expects($this->once())
            ->method('containsKey')
            ->with('context')
            ->willReturn(true);

        $this->mockHashMap->expects($this->once())
            ->method('get')
            ->with('context')
            ->willReturn($contextMap);

        $result = $this->registry->getContextProcessors('context');
        $this->assertSame($contextMap, $result);
    }

    public function testGetContextProcessorsNotFound(): void
    {
        $this->mockHashMap->expects($this->once())
            ->method('containsKey')
            ->with('context')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Context 'context' not found.");

        $this->registry->getContextProcessors('context');
    }
}
