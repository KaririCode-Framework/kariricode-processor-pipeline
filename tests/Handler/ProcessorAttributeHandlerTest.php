<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests\Handler;

use KaririCode\Contract\Processor\Attribute\ProcessableAttribute;
use KaririCode\Contract\Processor\Pipeline;
use KaririCode\Contract\Processor\ProcessorBuilder;
use KaririCode\Contract\Processor\ValidatableProcessor;
use KaririCode\ProcessorPipeline\Handler\ProcessorAttributeHandler;
use KaririCode\ProcessorPipeline\Processor\ProcessorConfigBuilder;
use KaririCode\ProcessorPipeline\Processor\ProcessorValidator;
use KaririCode\ProcessorPipeline\Result\ProcessingResultCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProcessorAttributeHandlerTest extends TestCase
{
    private ProcessorAttributeHandler $handler;
    private ProcessorBuilder|MockObject $builder;
    private ProcessorValidator|MockObject $validator;
    private ProcessorConfigBuilder|MockObject $configBuilder;
    private ValidatableProcessor|MockObject $processor;
    private Pipeline|MockObject $pipeline;

    protected function setUp(): void
    {
        $this->builder = $this->createMock(ProcessorBuilder::class);
        $this->validator = $this->createMock(ProcessorValidator::class);
        $this->configBuilder = $this->createMock(ProcessorConfigBuilder::class);
        $this->processor = $this->createMock(ValidatableProcessor::class);
        $this->pipeline = $this->createMock(Pipeline::class);

        $this->handler = new ProcessorAttributeHandler(
            'validator',
            $this->builder,
            $this->validator,
            $this->configBuilder
        );
    }

    public function testHandleAttributeWithValidProcessor(): void
    {
        $attribute = $this->createMock(ProcessableAttribute::class);

        $this->configureBasicMocks();
        $this->processor->method('isValid')->willReturn(true);
        $this->pipeline->method('process')->willReturn('processed');

        $result = $this->handler->handleAttribute('property', $attribute, 'value');

        $this->assertEquals('processed', $result);
        $this->assertFalse($this->handler->hasErrors());
    }

    public function testHandleAttributeWithValidationError(): void
    {
        $attribute = $this->createMock(ProcessableAttribute::class);
        $attribute->method('getProcessors')
            ->willReturn(['processor1' => []]);

        // Configurar processador para falhar validação
        $processor = $this->createMock(ValidatableProcessor::class);
        $processor->method('isValid')->willReturn(false);
        $processor->method('getErrorKey')->willReturn('validation_failed');

        // Configurar pipeline
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('process')->willReturn('processed');

        // Configurar builder
        $this->builder->method('build')
            ->with('validator', 'processor1')
            ->willReturn($processor);
        $this->builder->method('buildPipeline')
            ->willReturn($pipeline);

        // Configurar config builder
        $this->configBuilder->method('build')
            ->willReturn(['processor1' => []]);

        // Configurar validator para retornar erro
        $this->validator->method('validate')
            ->willReturn([
                'errorKey' => 'validation_failed',
                'message' => 'Validation failed',
            ]);

        $this->handler->handleAttribute('property', $attribute, 'value');

        $this->assertTrue($this->handler->hasErrors());
        $errors = $this->handler->getProcessingResultErrors();
        $this->assertArrayHasKey('property', $errors);
        $this->assertIsArray($errors['property']);
        $this->assertNotEmpty($errors['property']);
    }

    public function testHandleAttributeWithProcessingError(): void
    {
        $attribute = $this->createMock(ProcessableAttribute::class);

        $this->configureBasicMocks();
        $this->pipeline->method('process')
            ->willThrowException(new \Exception('Processing error'));

        $result = $this->handler->handleAttribute('property', $attribute, 'value');

        $this->assertEquals('value', $result);
        $this->assertArrayHasKey('property', $this->handler->getProcessingResultErrors());
    }

    public function testGetProcessingResults(): void
    {
        $attribute = $this->createMock(ProcessableAttribute::class);

        $this->configureBasicMocks();
        $this->pipeline->method('process')->willReturn('processed');

        $this->handler->handleAttribute('property', $attribute, 'value');

        $results = $this->handler->getProcessingResults();
        $this->assertInstanceOf(ProcessingResultCollection::class, $results);
        $processedData = $results->getProcessedData();
        $this->assertArrayHasKey('property', $processedData);
        $this->assertEquals('processed', $processedData['property']);
    }

    public function testReset(): void
    {
        $attribute = $this->createMock(ProcessableAttribute::class);

        $this->configureBasicMocks();
        $this->pipeline->method('process')->willReturn('processed');

        $this->handler->handleAttribute('property', $attribute, 'value');
        $this->handler->reset();

        $this->assertEmpty($this->handler->getProcessedPropertyValues()['values']);
        $this->assertEmpty($this->handler->getProcessingResultErrors());
        $this->assertFalse($this->handler->hasErrors());
    }

    public function testGetProcessedPropertyValues(): void
    {
        $attribute = $this->createMock(ProcessableAttribute::class);

        $this->configureBasicMocks();
        $this->pipeline->method('process')->willReturn('processed');

        $this->handler->handleAttribute('property', $attribute, 'value');

        $values = $this->handler->getProcessedPropertyValues();
        $this->assertArrayHasKey('values', $values);
        $this->assertArrayHasKey('timestamp', $values);
        $this->assertEquals(['property' => 'processed'], $values['values']);
    }

    private function configureBasicMocks(): void
    {
        // ConfigBuilder setup
        $this->configBuilder->method('build')
            ->willReturn(['processor1' => ['config' => 'value']]);

        // Builder setup
        $this->builder->method('build')
            ->willReturn($this->processor);

        $this->builder->method('buildPipeline')
            ->willReturn($this->pipeline);

        // Validator setup
        $this->validator->method('validate')
            ->willReturnCallback(function ($processor) {
                if ($processor instanceof ValidatableProcessor && !$processor->isValid()) {
                    return [
                        'errorKey' => $processor->getErrorKey(),
                        'message' => 'Validation failed',
                    ];
                }

                return null;
            });
    }

    public function testValidateProcessorsWithInvalidProcessor(): void
    {
        $processorsConfig = ['processor1' => []];
        $messages = ['processor1' => 'Validation failed for processor1'];

        $processor = $this->createMock(ValidatableProcessor::class);
        $processor->method('isValid')->willReturn(false);
        $processor->method('getErrorKey')->willReturn('invalid_processor');

        $this->builder->method('build')->willReturn($processor);

        // Usar Reflection para acessar validateProcessors
        $reflection = new \ReflectionClass(ProcessorAttributeHandler::class);
        $method = $reflection->getMethod('validateProcessors');
        $method->setAccessible(true);

        $errors = $method->invoke($this->handler, $processorsConfig, $messages);

        $this->assertArrayHasKey('processor1', $errors);
        $this->assertEquals('invalid_processor', $errors['processor1']['errorKey']);
        $this->assertEquals('Validation failed for processor1', $errors['processor1']['message']);
    }

    public function testProcessValueWithValidPipeline(): void
    {
        $config = ['processor1' => []];
        $this->pipeline->method('process')->willReturn('processed_value');

        $this->builder->method('buildPipeline')->willReturn($this->pipeline);

        // Usar Reflection para acessar processValue
        $reflection = new \ReflectionClass(ProcessorAttributeHandler::class);
        $method = $reflection->getMethod('processValue');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, 'input_value', $config);

        $this->assertEquals('processed_value', $result);
    }
}
