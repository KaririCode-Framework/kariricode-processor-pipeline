<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests\Result;

use KaririCode\ProcessorPipeline\Result\ProcessedData;
use KaririCode\ProcessorPipeline\Result\ProcessingError;
use KaririCode\ProcessorPipeline\Result\ProcessingResultCollection;
use PHPUnit\Framework\TestCase;

final class ProcessingResultCollectionTest extends TestCase
{
    private ProcessingResultCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new ProcessingResultCollection();
    }

    public function testAddError(): void
    {
        $this->collection->addError('email', 'invalid_email', 'Invalid email format');

        $errors = $this->collection->getErrors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertCount(1, $errors['email']);
        $this->assertEquals('invalid_email', $errors['email'][0]['errorKey']);
        $this->assertEquals('Invalid email format', $errors['email'][0]['message']);
    }

    public function testAddMultipleErrorsForSameProperty(): void
    {
        $this->collection->addError('email', 'invalid_email', 'Invalid email format');
        $this->collection->addError('email', 'required', 'Email is required');

        $errors = $this->collection->getErrors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertCount(2, $errors['email']);
    }

    public function testAddDuplicateError(): void
    {
        $this->collection->addError('email', 'invalid_email', 'Invalid email format');
        $this->collection->addError('email', 'invalid_email', 'Invalid email format');

        $errors = $this->collection->getErrors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertCount(1, $errors['email']);
    }

    public function testSetProcessedData(): void
    {
        $this->collection->setProcessedData('email', 'test@example.com');

        $data = $this->collection->getProcessedData();
        $this->assertArrayHasKey('email', $data);
        $this->assertEquals('test@example.com', $data['email']);
    }

    public function testOverwriteProcessedData(): void
    {
        $this->collection->setProcessedData('email', 'old@example.com');
        $this->collection->setProcessedData('email', 'new@example.com');

        $data = $this->collection->getProcessedData();
        $this->assertEquals('new@example.com', $data['email']);
    }

    public function testHasErrorsWithNoErrors(): void
    {
        $this->assertFalse($this->collection->hasErrors());
    }

    public function testHasErrorsWithErrors(): void
    {
        $this->collection->addError('email', 'invalid_email', 'Invalid email format');
        $this->assertTrue($this->collection->hasErrors());
    }

    public function testToArrayWithNoData(): void
    {
        $result = $this->collection->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('isValid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('processedData', $result);
        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
        $this->assertEmpty($result['processedData']);
    }

    public function testToArrayWithDataAndErrors(): void
    {
        $this->collection->setProcessedData('email', 'test@example.com');
        $this->collection->addError('name', 'required', 'Name is required');

        $result = $this->collection->toArray();

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('name', $result['errors']);
        $this->assertArrayHasKey('email', $result['processedData']);
        $this->assertEquals('test@example.com', $result['processedData']['email']);
    }

    public function testClear(): void
    {
        $this->collection->setProcessedData('email', 'test@example.com');
        $this->collection->addError('name', 'required', 'Name is required');

        $this->collection->clear();

        $this->assertFalse($this->collection->hasErrors());
        $this->assertEmpty($this->collection->getErrors());
        $this->assertEmpty($this->collection->getProcessedData());
    }

    public function testAddProcessedData(): void
    {
        $processedData = new ProcessedData('email', 'test@example.com');
        $this->collection->addProcessedData($processedData);

        $data = $this->collection->getProcessedData();
        $this->assertArrayHasKey('email', $data);
        $this->assertEquals('test@example.com', $data['email']);
    }

    public function testAddProcessingError(): void
    {
        $error = new ProcessingError('email', 'invalid_email', 'Invalid email format');
        $this->collection->addProcessingError($error);

        $errors = $this->collection->getErrors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals('invalid_email', $errors['email'][0]['errorKey']);
        $this->assertEquals('Invalid email format', $errors['email'][0]['message']);
    }

    public function testMultiplePropertiesWithErrors(): void
    {
        $this->collection->addError('email', 'invalid_email', 'Invalid email format');
        $this->collection->addError('name', 'required', 'Name is required');
        $this->collection->addError('age', 'min_value', 'Age must be at least 18');

        $errors = $this->collection->getErrors();
        $this->assertCount(3, $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('age', $errors);
    }

    public function testMultiplePropertiesWithProcessedData(): void
    {
        $this->collection->setProcessedData('email', 'test@example.com');
        $this->collection->setProcessedData('name', 'John Doe');
        $this->collection->setProcessedData('age', 25);

        $data = $this->collection->getProcessedData();
        $this->assertCount(3, $data);
        $this->assertEquals('test@example.com', $data['email']);
        $this->assertEquals('John Doe', $data['name']);
        $this->assertEquals(25, $data['age']);
    }

    public function testMixedDataTypes(): void
    {
        $values = [
            'string' => 'test',
            'integer' => 42,
            'float' => 3.14,
            'boolean' => true,
            'array' => ['test' => 'value'],
            'null' => null,
            'object' => new \stdClass(),
        ];

        foreach ($values as $key => $value) {
            $this->collection->setProcessedData($key, $value);
        }

        $data = $this->collection->getProcessedData();
        foreach ($values as $key => $value) {
            $this->assertArrayHasKey($key, $data);
            $this->assertEquals($value, $data[$key]);
        }
    }

    public function testErrorCollectionWithSameHashButDifferentProperties(): void
    {
        $this->collection->addError('email1', 'invalid', 'Invalid format');
        $this->collection->addError('email2', 'invalid', 'Invalid format');

        $errors = $this->collection->getErrors();
        $this->assertArrayHasKey('email1', $errors);
        $this->assertArrayHasKey('email2', $errors);
        $this->assertCount(1, $errors['email1']);
        $this->assertCount(1, $errors['email2']);
    }

    public function testToArrayWithAllPossibleStates(): void
    {
        $processedData = new ProcessedData('email', 'test@example.com');
        $this->collection->addProcessedData($processedData);

        $error = new ProcessingError('password', 'required', 'Password is required');
        $this->collection->addProcessingError($error);

        $result = $this->collection->toArray();

        $this->assertArrayHasKey('isValid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('processedData', $result);

        $this->assertArrayHasKey('email', $result['processedData']);
        $this->assertEquals('test@example.com', $result['processedData']['email']);

        $this->assertArrayHasKey('password', $result['errors']);
        $this->assertCount(1, $result['errors']['password']);
        $this->assertEquals('required', $result['errors']['password'][0]['errorKey']);
        $this->assertEquals('Password is required', $result['errors']['password'][0]['message']);

        $this->assertFalse($result['isValid']);
    }

    public function testAddProcessedDataWithExistingProperty(): void
    {
        $data1 = new ProcessedData('email', 'old@example.com');
        $data2 = new ProcessedData('email', 'new@example.com');

        $this->collection->addProcessedData($data1);
        $this->collection->addProcessedData($data2);

        $result = $this->collection->getProcessedData();
        $this->assertArrayHasKey('email', $result);
        $this->assertEquals('new@example.com', $result['email']);
        $this->assertCount(1, $result);
    }

    public function testAddProcessingErrorWithExistingError(): void
    {
        $error1 = new ProcessingError('email', 'required', 'Email is required');
        $error2 = new ProcessingError('email', 'required', 'Email is required');
        $error3 = new ProcessingError('email', 'invalid', 'Invalid email format');

        $this->collection->addProcessingError($error1);
        $this->collection->addProcessingError($error2);
        $this->collection->addProcessingError($error3);

        $errors = $this->collection->getErrors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertCount(2, $errors['email']);

        $errorMessages = array_column($errors['email'], 'message');
        $this->assertContains('Email is required', $errorMessages);
        $this->assertContains('Invalid email format', $errorMessages);
    }

    public function testClearRemovesAllDataAndErrors(): void
    {
        $data = new ProcessedData('email', 'test@example.com');
        $this->collection->addProcessedData($data);

        $error = new ProcessingError('email', 'required', 'Email is required');
        $this->collection->addProcessingError($error);

        $this->assertNotEmpty($this->collection->getProcessedData());
        $this->assertNotEmpty($this->collection->getErrors());
        $this->assertTrue($this->collection->hasErrors());

        $this->collection->clear();

        $this->assertEmpty($this->collection->getProcessedData());
        $this->assertEmpty($this->collection->getErrors());
        $this->assertFalse($this->collection->hasErrors());

        $result = $this->collection->toArray();
        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
        $this->assertEmpty($result['processedData']);
    }

    public function testAddProcessedDataAndProcessingErrorWithMultipleProperties(): void
    {
        $data1 = new ProcessedData('email', 'test@example.com');
        $data2 = new ProcessedData('name', 'John Doe');
        $data3 = new ProcessedData('age', 25);

        $this->collection->addProcessedData($data1);
        $this->collection->addProcessedData($data2);
        $this->collection->addProcessedData($data3);

        $error1 = new ProcessingError('email', 'invalid', 'Invalid email');
        $error2 = new ProcessingError('email', 'required', 'Email required');
        $error3 = new ProcessingError('password', 'required', 'Password required');

        $this->collection->addProcessingError($error1);
        $this->collection->addProcessingError($error2);
        $this->collection->addProcessingError($error3);

        $processedData = $this->collection->getProcessedData();
        $this->assertCount(3, $processedData);
        $this->assertEquals('test@example.com', $processedData['email']);
        $this->assertEquals('John Doe', $processedData['name']);
        $this->assertEquals(25, $processedData['age']);

        $errors = $this->collection->getErrors();
        $this->assertCount(2, $errors);
        $this->assertCount(2, $errors['email']);
        $this->assertCount(1, $errors['password']);

        $result = $this->collection->toArray();
        $this->assertFalse($result['isValid']);
        $this->assertCount(2, $result['errors']);
        $this->assertCount(3, $result['processedData']);
    }
}
