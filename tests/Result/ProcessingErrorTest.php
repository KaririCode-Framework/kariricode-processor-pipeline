<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests\Result;

use KaririCode\ProcessorPipeline\Result\ProcessingError;
use PHPUnit\Framework\TestCase;

final class ProcessingErrorTest extends TestCase
{
    public function testGetHash(): void
    {
        $error = new ProcessingError('email', 'invalid_email', 'Invalid email format');

        $expectedHash = md5('emailinvalid_emailInvalid email format');
        $this->assertEquals($expectedHash, $error->getHash());
    }

    public function testGetProperty(): void
    {
        $error = new ProcessingError('email', 'invalid_email', 'Invalid email format');

        $this->assertEquals('email', $error->getProperty());
    }

    public function testGetErrorKey(): void
    {
        $error = new ProcessingError('email', 'invalid_email', 'Invalid email format');

        $this->assertEquals('invalid_email', $error->getErrorKey());
    }

    public function testGetMessage(): void
    {
        $error = new ProcessingError('email', 'invalid_email', 'Invalid email format');

        $this->assertEquals('Invalid email format', $error->getMessage());
    }

    public function testToArray(): void
    {
        $error = new ProcessingError('email', 'invalid_email', 'Invalid email format');
        $result = $error->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('errorKey', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertEquals('invalid_email', $result['errorKey']);
        $this->assertEquals('Invalid email format', $result['message']);
        $this->assertIsInt($result['timestamp']);
        $this->assertLessThanOrEqual(time(), $result['timestamp']);
    }

    public function testHashUniqueness(): void
    {
        $error1 = new ProcessingError('email', 'invalid_email', 'Invalid email format');
        $error2 = new ProcessingError('email', 'invalid_email', 'Invalid email format');
        $error3 = new ProcessingError('email', 'different_error', 'Invalid email format');

        $this->assertEquals($error1->getHash(), $error2->getHash());
        $this->assertNotEquals($error1->getHash(), $error3->getHash());
    }

    /**
     * @dataProvider specialCharactersProvider
     */
    public function testHashWithSpecialCharacters(string $property, string $errorKey, string $message): void
    {
        $error = new ProcessingError($property, $errorKey, $message);

        $this->assertNotEmpty($error->getHash());
        $this->assertEquals(32, strlen($error->getHash()));
    }

    public static function specialCharactersProvider(): array
    {
        return [
            'unicode' => ['émáil', 'error_key', 'Test message'],
            'symbols' => ['email@test', '!error_key!', 'Test message!'],
            'spaces' => ['email test', 'error key', 'Test message with spaces'],
            'empty' => ['', '', ''],
            'mixed' => ['email#123', 'error_key!@#', 'Test message 123!@#'],
        ];
    }
}
