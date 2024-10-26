<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests\Handler;

use KaririCode\Contract\Processor\Attribute\CustomizableMessageAttribute;
use KaririCode\Contract\Processor\Attribute\ProcessableAttribute;
use KaririCode\Contract\Processor\Pipeline;
use KaririCode\Contract\Processor\ProcessorBuilder;
use KaririCode\ProcessorPipeline\Exception\ProcessorRuntimeException;
use KaririCode\ProcessorPipeline\Handler\AttributeHandler;
use KaririCode\ProcessorPipeline\Processor\ProcessorConfigBuilder;
use KaririCode\ProcessorPipeline\Processor\ProcessorValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

interface CombinedAttribute extends
    ProcessableAttribute,
    CustomizableMessageAttribute
{
}

final class AttributeHandlerTest extends TestCase
{
    private AttributeHandler $attributeHandler;
    private ProcessorBuilder|MockObject $processorBuilder;
    private ProcessorValidator|MockObject $processorValidator;
    private ProcessorConfigBuilder|MockObject $configBuilder;

    protected function setUp(): void
    {
        $this->processorBuilder = $this->createMock(ProcessorBuilder::class);
        $this->processorValidator = $this->createMock(ProcessorValidator::class);
        $this->configBuilder = $this->createMock(ProcessorConfigBuilder::class);
        $this->attributeHandler = new AttributeHandler(
            'testProcessor',
            $this->processorBuilder,
            $this->processorValidator,
            $this->configBuilder
        );
    }

    public function testHandleAttributeProcessesValue(): void
    {
        $mockAttribute = $this->createMock(ProcessableAttribute::class);
        $mockPipeline = $this->createMock(Pipeline::class);

        $this->configBuilder->expects($this->once())
            ->method('build')
            ->willReturn(['processor1' => []]);

        $mockPipeline->expects($this->once())
            ->method('process')
            ->with('initialValue')
            ->willReturn('processedValue');

        $this->processorBuilder->expects($this->once())
            ->method('buildPipeline')
            ->with($this->equalTo('testProcessor'), $this->equalTo(['processor1' => []]))
            ->willReturn($mockPipeline);

        $this->processorValidator->expects($this->once())
            ->method('validate')
            ->willReturn(null);

        $result = $this->attributeHandler->handleAttribute('testProperty', $mockAttribute, 'initialValue');

        $this->assertSame('processedValue', $result);
    }

    public function testHandleAttributeWithValidationError(): void
    {
        $mockAttribute = $this->createMock(ProcessableAttribute::class);
        $mockPipeline = $this->createMock(Pipeline::class);

        $this->configBuilder->expects($this->once())
            ->method('build')
            ->willReturn(['processor1' => []]);

        $mockPipeline->expects($this->once())
            ->method('process')
            ->willReturn('processedValue');

        $this->processorBuilder->expects($this->once())
            ->method('buildPipeline')
            ->willReturn($mockPipeline);

        $this->processorValidator->expects($this->once())
            ->method('validate')
            ->willReturn(['errorKey' => 'testError', 'message' => 'Test error message']);

        $result = $this->attributeHandler->handleAttribute('testProperty', $mockAttribute, 'initialValue');

        $this->assertSame('processedValue', $result);

        $errors = $this->attributeHandler->getProcessingResultErrors();
        $this->assertArrayHasKey('testProperty', $errors);
        $this->assertArrayHasKey('processor1', $errors['testProperty']);
        $this->assertEquals('testError', $errors['testProperty']['processor1']['errorKey']);
        $this->assertEquals('Test error message', $errors['testProperty']['processor1']['message']);
    }

    public function testHandleAttributeReturnsNullWhenAttributeNotProcessable(): void
    {
        $nonProcessableAttribute = new \stdClass();
        $result = $this->attributeHandler->handleAttribute('testProperty', $nonProcessableAttribute, 'initialValue');
        $this->assertNull($result);
    }

    public function testApplyChangesSetsProcessedValues(): void
    {
        $mockEntity = new class {
            public string $testProperty = 'originalValue';
        };

        $mockAttribute = $this->createMock(ProcessableAttribute::class);
        $mockPipeline = $this->createMock(Pipeline::class);

        $this->configBuilder->expects($this->once())
            ->method('build')
            ->willReturn(['processor1' => []]);

        $mockPipeline->expects($this->once())
            ->method('process')
            ->with('initialValue')
            ->willReturn('processedValue');

        $this->processorBuilder->expects($this->once())
            ->method('buildPipeline')
            ->willReturn($mockPipeline);

        $this->processorValidator->expects($this->once())
            ->method('validate')
            ->willReturn(null);

        $this->attributeHandler->handleAttribute('testProperty', $mockAttribute, 'initialValue');
        $this->attributeHandler->applyChanges($mockEntity);

        $this->assertSame('processedValue', $mockEntity->testProperty);
    }

    public function testGetProcessedPropertyValuesReturnsProcessedData(): void
    {
        $mockAttribute = $this->createMock(ProcessableAttribute::class);
        $mockPipeline = $this->createMock(Pipeline::class);

        $this->configBuilder->expects($this->once())
            ->method('build')
            ->willReturn(['processor1' => []]);

        $mockPipeline->expects($this->once())
            ->method('process')
            ->with('initialValue')
            ->willReturn('processedValue');

        $this->processorBuilder->expects($this->once())
            ->method('buildPipeline')
            ->willReturn($mockPipeline);

        $this->processorValidator->expects($this->once())
            ->method('validate')
            ->willReturn(null);

        $this->attributeHandler->handleAttribute('testProperty', $mockAttribute, 'initialValue');
        $processedValues = $this->attributeHandler->getProcessedPropertyValues();

        $this->assertArrayHasKey('testProperty', $processedValues);
        $this->assertIsArray($processedValues['testProperty']);
        $this->assertArrayHasKey('value', $processedValues['testProperty']);
        $this->assertArrayHasKey('messages', $processedValues['testProperty']);
        $this->assertSame('processedValue', $processedValues['testProperty']['value']);
        $this->assertIsArray($processedValues['testProperty']['messages']);
    }

    public function testHandleAttributeWithCustomizableMessageAttribute(): void
    {
        $mockAttribute = $this->createMock(CombinedAttribute::class);
        $mockPipeline = $this->createMock(Pipeline::class);

        $this->configBuilder->expects($this->once())
            ->method('build')
            ->willReturn(['processor1' => ['option' => 'value']]);

        $mockAttribute->expects($this->once())
            ->method('getMessage')
            ->with('processor1')
            ->willReturn('Custom message');

        $mockPipeline->method('process')->willReturn('processedValue');

        $this->processorBuilder->expects($this->once())
            ->method('buildPipeline')
            ->willReturn($mockPipeline);

        $this->processorValidator->expects($this->once())
            ->method('validate')
            ->willReturn(null);

        $result = $this->attributeHandler->handleAttribute('testProperty', $mockAttribute, 'initialValue');
        $this->assertSame('processedValue', $result);

        $processedValues = $this->attributeHandler->getProcessedPropertyValues();
        $this->assertArrayHasKey('testProperty', $processedValues);
        $this->assertArrayHasKey('messages', $processedValues['testProperty']);
        $this->assertArrayHasKey('processor1', $processedValues['testProperty']['messages']);
        $this->assertEquals('Custom message', $processedValues['testProperty']['messages']['processor1']);
    }

    public function testHandleAttributeWithProcessingException(): void
    {
        $mockAttribute = $this->createMock(ProcessableAttribute::class);
        $mockPipeline = $this->createMock(Pipeline::class);

        $this->configBuilder->expects($this->once())
            ->method('build')
            ->willReturn(['processor1' => []]);

        $mockPipeline->expects($this->once())
            ->method('process')
            ->willThrowException(
                ProcessorRuntimeException::contextNotFound('payment')
            );

        $this->processorBuilder->expects($this->once())
            ->method('buildPipeline')
            ->willReturn($mockPipeline);

        $result = $this->attributeHandler->handleAttribute('testProperty', $mockAttribute, 'initialValue');
        $this->assertSame('initialValue', $result);

        $errors = $this->attributeHandler->getProcessingResultErrors();

        $this->assertStringContainsString("Processor context 'payment' not found", $errors['testProperty'][0]);
    }
}
