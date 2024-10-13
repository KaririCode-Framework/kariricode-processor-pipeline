<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests;

use KaririCode\Contract\Processor\Processor;
use KaririCode\ProcessorPipeline\ProcessorPipeline;
use PHPUnit\Framework\TestCase;

final class ProcessorPipelineTest extends TestCase
{
    public function testAddProcessor(): void
    {
        $pipeline = new ProcessorPipeline();
        $processor = $this->createMock(Processor::class);

        $result = $pipeline->addProcessor($processor);

        $this->assertSame($pipeline, $result);
    }

    public function testProcessWithNoProcessors(): void
    {
        $pipeline = new ProcessorPipeline();
        $input = 'test';

        $result = $pipeline->process($input);

        $this->assertSame($input, $result);
    }

    public function testProcessWithMultipleProcessors(): void
    {
        $pipeline = new ProcessorPipeline();

        $processor1 = $this->createMock(Processor::class);
        $processor1->method('process')->willReturnCallback(fn ($input) => $input . '1');

        $processor2 = $this->createMock(Processor::class);
        $processor2->method('process')->willReturnCallback(fn ($input) => $input . '2');

        $pipeline->addProcessor($processor1)->addProcessor($processor2);

        $result = $pipeline->process('test');

        $this->assertSame('test12', $result);
    }
}
