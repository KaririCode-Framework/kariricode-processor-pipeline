<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests\Exception;

use KaririCode\ProcessorPipeline\Exception\ProcessorRuntimeException;
use PHPUnit\Framework\TestCase;

final class ProcessorRuntimeExceptionTest extends TestCase
{
    public function testContextNotFound(): void
    {
        $exception = ProcessorRuntimeException::contextNotFound('payment');

        $this->assertSame(2601, $exception->getCode());
        $this->assertSame('PROCESSOR_CONTEXT_NOT_FOUND', $exception->getErrorCode());
        $this->assertSame("Processor context 'payment' not found", $exception->getMessage());
    }

    public function testProcessorNotFound(): void
    {
        $exception = ProcessorRuntimeException::processorNotFound('validate', 'payment');

        $this->assertSame(2602, $exception->getCode());
        $this->assertSame('PROCESSOR_NOT_FOUND', $exception->getErrorCode());
        $this->assertSame("Processor 'validate' not found in context 'payment'", $exception->getMessage());
    }

    public function testInvalidProcessor(): void
    {
        $exception = ProcessorRuntimeException::invalidProcessor('emailValidator', 'Invalid configuration');

        $this->assertSame(2603, $exception->getCode());
        $this->assertSame('PROCESSOR_INVALID', $exception->getErrorCode());
        $this->assertSame("Invalid processor 'emailValidator': Invalid configuration", $exception->getMessage());
    }

    public function testInvalidContext(): void
    {
        $exception = ProcessorRuntimeException::invalidContext('payment', 'Context not initialized');

        $this->assertSame(2604, $exception->getCode());
        $this->assertSame('PROCESSOR_CONTEXT_INVALID', $exception->getErrorCode());
        $this->assertSame("Invalid processor context 'payment': Context not initialized", $exception->getMessage());
    }

    public function testInvalidConfiguration(): void
    {
        $exception = ProcessorRuntimeException::invalidConfiguration('emailValidator', 'Missing required fields');

        $this->assertSame(2605, $exception->getCode());
        $this->assertSame('PROCESSOR_CONFIG_INVALID', $exception->getErrorCode());
        $this->assertSame(
            "Invalid processor configuration for 'emailValidator': Missing required fields",
            $exception->getMessage()
        );
    }

    public function testProcessingFailed(): void
    {
        $exception = ProcessorRuntimeException::processingFailed('email');

        $this->assertSame(2606, $exception->getCode());
        $this->assertSame('PROCESSOR_PROCESSING_FAILED', $exception->getErrorCode());
        $this->assertSame("Processing failed for property 'email'", $exception->getMessage());
    }
}
