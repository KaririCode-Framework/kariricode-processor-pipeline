<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests\Processor;

use KaririCode\Contract\Processor\Processor;
use KaririCode\Contract\Processor\ValidatableProcessor;
use KaririCode\ProcessorPipeline\Processor\ProcessorValidator;
use PHPUnit\Framework\TestCase;

final class ProcessorValidatorTest extends TestCase
{
    private ProcessorValidator $processorValidator;

    protected function setUp(): void
    {
        $this->processorValidator = new ProcessorValidator();
    }

    public function testValidateWithNonValidatableProcessor(): void
    {
        $processor = $this->createMock(Processor::class);

        $result = $this->processorValidator->validate($processor, 'testProcessor', []);

        $this->assertNull($result);
    }

    public function testValidateWithValidValidatableProcessor(): void
    {
        $processor = $this->createMock(ValidatableProcessor::class);
        $processor->method('isValid')->willReturn(true);

        $result = $this->processorValidator->validate($processor, 'testProcessor', []);

        $this->assertNull($result);
    }

    public function testValidateWithInvalidValidatableProcessor(): void
    {
        $processor = $this->createMock(ValidatableProcessor::class);
        $processor->method('isValid')->willReturn(false);
        $processor->method('getErrorKey')->willReturn('testError');

        $result = $this->processorValidator->validate($processor, 'testProcessor', []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('errorKey', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('testError', $result['errorKey']);
        $this->assertEquals('Validation failed for testProcessor', $result['message']);
    }

    public function testValidateWithInvalidValidatableProcessorAndCustomMessage(): void
    {
        $processor = $this->createMock(ValidatableProcessor::class);
        $processor->method('isValid')->willReturn(false);
        $processor->method('getErrorKey')->willReturn('testError');

        $messages = ['testProcessor' => 'Custom error message'];

        $result = $this->processorValidator->validate($processor, 'testProcessor', $messages);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('errorKey', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('testError', $result['errorKey']);
        $this->assertEquals('Custom error message', $result['message']);
    }
}
