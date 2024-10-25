<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests\Processor;

use KaririCode\Contract\Processor\Attribute\ProcessableAttribute;
use KaririCode\ProcessorPipeline\Processor\ProcessorConfigBuilder;
use PHPUnit\Framework\TestCase;

final class ProcessorConfigBuilderTest extends TestCase
{
    private ProcessorConfigBuilder $configBuilder;

    protected function setUp(): void
    {
        $this->configBuilder = new ProcessorConfigBuilder();
    }

    public function testBuildWithSimpleProcessors(): void
    {
        $attribute = $this->createMock(ProcessableAttribute::class);
        $attribute->method('getProcessors')->willReturn(['processor1', 'processor2']);

        $result = $this->configBuilder->build($attribute);

        $this->assertEquals(['processor1' => [], 'processor2' => []], $result);
    }

    public function testBuildWithConfigurableProcessors(): void
    {
        $attribute = $this->createMock(ProcessableAttribute::class);
        $attribute->method('getProcessors')->willReturn([
            'processor1' => ['option' => 'value'],
            'processor2' => ['another_option' => 'another_value'],
        ]);

        $result = $this->configBuilder->build($attribute);

        $this->assertEquals([
            'processor1' => ['option' => 'value'],
            'processor2' => ['another_option' => 'another_value'],
        ], $result);
    }

    public function testBuildWithMixedProcessors(): void
    {
        $attribute = $this->createMock(ProcessableAttribute::class);
        $attribute->method('getProcessors')->willReturn([
            'processor1',
            'processor2' => ['option' => 'value'],
            ['unnamed_processor' => []],
        ]);

        $result = $this->configBuilder->build($attribute);

        $this->assertEquals([
            'processor1' => [],
            'processor2' => ['option' => 'value'],
            'unnamed_processor' => ['unnamed_processor' => []],
        ], $result);
    }
}
