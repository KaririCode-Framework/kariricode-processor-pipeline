<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests\Result;

use KaririCode\ProcessorPipeline\Result\ProcessedData;
use PHPUnit\Framework\TestCase;

final class ProcessedDataTest extends TestCase
{
    public function testGetProperty(): void
    {
        $data = new ProcessedData('email', 'test@example.com');

        $this->assertEquals('email', $data->getProperty());
    }

    public function testGetValue(): void
    {
        $data = new ProcessedData('email', 'test@example.com');

        $this->assertEquals('test@example.com', $data->getValue());
    }

    public function testToArray(): void
    {
        $data = new ProcessedData('email', 'test@example.com');
        $result = $data->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertEquals('test@example.com', $result['value']);
        $this->assertIsInt($result['timestamp']);
        $this->assertLessThanOrEqual(time(), $result['timestamp']);
    }

    /**
     * @dataProvider valueTypesProvider
     */
    public function testDifferentValueTypes(mixed $value): void
    {
        $data = new ProcessedData('property', $value);

        $this->assertSame($value, $data->getValue());
        $array = $data->toArray();
        $this->assertSame($value, $array['value']);
    }

    public static function valueTypesProvider(): array
    {
        return [
            'string' => ['test'],
            'integer' => [42],
            'float' => [3.14],
            'boolean' => [true],
            'null' => [null],
            'array' => [['test' => 'value']],
            'object' => [new \stdClass()],
        ];
    }
}
