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

        $this->assertInstanceOf(ProcessorRuntimeException::class, $exception);
        $this->assertEquals(2601, $exception->getCode());
        $this->assertEquals('PROCESSOR_CONTEXT_NOT_FOUND', $exception->getErrorCode());
        $this->assertEquals("Processor context 'payment' not found", $exception->getMessage());
        $this->assertNull($exception->getPrevious());
    }

    public function testProcessorNotFound(): void
    {
        $exception = ProcessorRuntimeException::processorNotFound('validate', 'payment');

        $this->assertInstanceOf(ProcessorRuntimeException::class, $exception);
        $this->assertEquals(2602, $exception->getCode());
        $this->assertEquals('PROCESSOR_NOT_FOUND', $exception->getErrorCode());
        $this->assertEquals("Processor 'validate' not found in context 'payment'", $exception->getMessage());
        $this->assertNull($exception->getPrevious());
    }

    public function testProcessingFailed(): void
    {
        $exception = ProcessorRuntimeException::processingFailed('email');

        $this->assertInstanceOf(ProcessorRuntimeException::class, $exception);
        $this->assertEquals(2606, $exception->getCode());
        $this->assertEquals('PROCESSOR_PROCESSING_FAILED', $exception->getErrorCode());
        $this->assertEquals(
            "Processing failed for property 'email'",
            $exception->getMessage()
        );
        $this->assertNull($exception->getPrevious());
    }

    /**
     * @dataProvider specialValuesProvider
     */
    public function testWithSpecialValues(string $context, string $processor, string $details): void
    {
        $exceptionContext = ProcessorRuntimeException::contextNotFound($context);
        $this->assertStringContainsString($context, $exceptionContext->getMessage());

        $exceptionProcessor = ProcessorRuntimeException::processorNotFound($processor, $context);
        $this->assertStringContainsString($processor, $exceptionProcessor->getMessage());
        $this->assertStringContainsString($context, $exceptionProcessor->getMessage());
    }

    public static function specialValuesProvider(): array
    {
        return [
            'empty values' => ['', '', ''],
            'special characters' => ['payment!@#', 'validator$%^', 'error&*()'],
            'unicode characters' => ['pagaménto', 'validação', 'erro'],
            'very long values' => [
                str_repeat('a', 100),
                str_repeat('b', 100),
                str_repeat('c', 100),
            ],
        ];
    }

    public function testExceptionHierarchy(): void
    {
        $exception = ProcessorRuntimeException::contextNotFound('payment');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testExceptionWithPreviousException(): void
    {
        $previous = new \Exception('Original error');

        $reflection = new \ReflectionClass(ProcessorRuntimeException::class);
        $method = $reflection->getMethod('createException');
        $method->setAccessible(true);

        $exception = $method->invokeArgs(null, [
            2601,
            'PROCESSOR_CONTEXT_NOT_FOUND',
            'Test message',
            $previous,
        ]);

        $this->assertInstanceOf(ProcessorRuntimeException::class, $exception);
        $this->assertEquals(2601, $exception->getCode());
        $this->assertEquals('PROCESSOR_CONTEXT_NOT_FOUND', $exception->getErrorCode());
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * @dataProvider invalidPropertyValuesProvider
     */
    public function testProcessingFailedWithDifferentPropertyTypes($property): void
    {
        $exception = ProcessorRuntimeException::processingFailed($property);
        $message = $exception->getMessage();

        $this->assertIsString($message);
        $this->assertStringContainsString((string) $property, $message);
    }

    public static function invalidPropertyValuesProvider(): array
    {
        return [
            'valid string' => ['email'],
        ];
    }
}
