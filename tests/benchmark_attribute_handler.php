<?php

declare(strict_types=1);

namespace KaririCode\PropertyInspector\Tests;

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Contract\Processor\Attribute\CustomizableMessageAttribute;
use KaririCode\Contract\Processor\Attribute\ProcessableAttribute;
use KaririCode\Contract\Processor\Pipeline;
use KaririCode\Contract\Processor\Processor;
use KaririCode\Contract\Processor\ProcessorBuilder;
use KaririCode\Contract\Processor\ProcessorValidator as ProcessorProcessorContract;
use KaririCode\ProcessorPipeline\Processor\ProcessorConfigBuilder;
use KaririCode\ProcessorPipeline\Processor\ProcessorValidator;
use KaririCode\PropertyInspector\AttributeHandler;
use KaririCode\PropertyInspector\Contract\ProcessorConfigBuilder as ProcessorConfigBuilderContract;
use KaririCode\PropertyInspector\Contract\PropertyAttributeHandler;
use KaririCode\PropertyInspector\Contract\PropertyChangeApplier;
use KaririCode\PropertyInspector\Utility\PropertyAccessor;

// Mock implementations
class MockProcessor implements Processor
{
    public function process(mixed $value): mixed
    {
        return $value;
    }
}

class MockPipeline implements Pipeline
{
    public function process(mixed $value): mixed
    {
        return $value;
    }

    public function addProcessor(Processor $processor): self
    {
        return $this;
    }
}

class MockProcessorBuilder implements ProcessorBuilder
{
    public function build(string $context, string $name, array $processorConfig = []): Processor
    {
        return new MockProcessor();
    }

    public function buildPipeline(string $context, array $processorSpecs): Pipeline
    {
        return new MockPipeline();
    }
}

class BenchmarkRunner
{
    private const ITERATIONS = 10000;

    private ProcessorBuilder $builder;

    // ANSI color codes
    private const ANSI_GREEN = "\033[32m";
    private const ANSI_YELLOW = "\033[33m";
    private const ANSI_RED = "\033[31m";
    private const ANSI_RESET = "\033[0m";
    private const ANSI_BOLD = "\033[1m";
    private const ANSI_BLUE = "\033[34m";

    public function __construct()
    {
        $this->builder = new MockProcessorBuilder();
    }

    public function run(): void
    {
        echo self::ANSI_BOLD . "\nATTRIBUTE HANDLER PERFORMANCE BENCHMARK\n" . self::ANSI_RESET;
        echo str_repeat('=', 60) . "\n";
        echo self::ANSI_BLUE . 'Running benchmark with ' . self::ITERATIONS . ' iterations...' . self::ANSI_RESET . "\n\n";

        // Warm up phase
        $this->warmUp();

        // Test original handler
        $originalStats = $this->benchmarkOriginalHandler();

        // Test optimized handler
        $optimizedStats = $this->benchmarkOptimizedHandler();

        // Display results
        $this->displayResults($originalStats, $optimizedStats);
    }

    private function warmUp(): void
    {
        echo self::ANSI_YELLOW . 'Warming up JIT compiler...' . self::ANSI_RESET . "\n";

        for ($i = 0; $i < 1000; ++$i) {
            $handler = new AttributeHandler('validator', $this->builder);
            $this->runTestCase($handler);

            $handler = new AttributeHandlerOtimized('validator', $this->builder);
            $this->runTestCase($handler);
        }

        // Clear any accumulated memory
        gc_collect_cycles();
        echo self::ANSI_GREEN . "Warm-up complete!\n\n" . self::ANSI_RESET;
    }

    private function benchmarkOriginalHandler(): array
    {
        // Reset memory state
        gc_collect_cycles();
        $startMemory = memory_get_usage(true);

        $handler = new AttributeHandler('validator', $this->builder);
        $start = hrtime(true);

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $this->runTestCase($handler);
        }

        $time = (hrtime(true) - $start) / 1e+9;
        $memoryUsed = memory_get_usage(true) - $startMemory;

        return [
            'time' => $time,
            'memory' => $memoryUsed,
            'peak' => memory_get_peak_usage(true),
        ];
    }

    private function benchmarkOptimizedHandler(): array
    {
        // Reset memory state
        gc_collect_cycles();
        $startMemory = memory_get_usage(true);

        $handler = new AttributeHandlerOtimized('validator', $this->builder);
        $start = hrtime(true);

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $this->runTestCase($handler);
        }

        $time = (hrtime(true) - $start) / 1e+9;
        $memoryUsed = memory_get_usage(true) - $startMemory;

        return [
            'time' => $time,
            'memory' => $memoryUsed,
            'peak' => memory_get_peak_usage(true),
        ];
    }

    private function runTestCase($handler): void
    {
        $attribute = new class implements ProcessableAttribute {
            public function getProcessors(): array
            {
                return [
                    'required',
                    'email' => ['pattern' => '/.+@.+/'],
                    'length' => ['min' => 5, 'max' => 50],
                    'trim' => true,
                    'lowercase' => true,
                ];
            }
        };

        $testCases = [
            ['email', 'test@example.com'],
            ['name', 'John Doe'],
            ['age', 25],
            ['description', str_repeat('a', 100)],
            ['date', new \DateTime()],
            ['empty', null],
            ['whitespace', '  trimmed  '],
            ['special', '!@#$%^&*()'],
            ['unicode', 'αβγδε'],
            ['number_string', '12345'],
        ];

        foreach ($testCases as [$property, $value]) {
            $handler->handleAttribute($property, $attribute, $value);
        }

        $handler->getProcessingResultMessages();
        $handler->getProcessedPropertyValues();
        $handler->getProcessingResultErrors();
    }

    private function displayResults(array $originalStats, array $optimizedStats): void
    {
        echo self::ANSI_BOLD . "Performance Results\n" . self::ANSI_RESET;
        echo str_repeat('=', 60) . "\n";

        // Time Performance
        $timeDiff = $this->calculatePercentageDiff($originalStats['time'], $optimizedStats['time']);
        $timeColor = $timeDiff > 0 ? self::ANSI_GREEN : self::ANSI_RED;

        echo self::ANSI_BOLD . "Execution Time\n" . self::ANSI_RESET;
        echo str_repeat('-', 40) . "\n";
        echo sprintf("%sOriginal Handler:  %.6f seconds%s\n", self::ANSI_YELLOW, $originalStats['time'], self::ANSI_RESET);
        echo sprintf("%sOptimized Handler: %.6f seconds%s\n", self::ANSI_YELLOW, $optimizedStats['time'], self::ANSI_RESET);
        echo sprintf(
            "%sTime Difference:   %.2f%% %s%s\n\n",
            $timeColor,
            abs($timeDiff),
            $timeDiff > 0 ? 'faster' : 'slower',
            self::ANSI_RESET
        );

        // Memory Usage
        echo self::ANSI_BOLD . "Memory Usage\n" . self::ANSI_RESET;
        echo str_repeat('-', 40) . "\n";

        $originalMemoryMB = $originalStats['memory'] / 1024 / 1024;
        $optimizedMemoryMB = $optimizedStats['memory'] / 1024 / 1024;
        $memoryDiff = $this->calculatePercentageDiff($originalStats['memory'], $optimizedStats['memory']);
        $memoryColor = $memoryDiff > 0 ? self::ANSI_GREEN : self::ANSI_RED;

        echo sprintf("%sOriginal Handler:  %.2f MB%s\n", self::ANSI_YELLOW, $originalMemoryMB, self::ANSI_RESET);
        echo sprintf("%sOptimized Handler: %.2f MB%s\n", self::ANSI_YELLOW, $optimizedMemoryMB, self::ANSI_RESET);
        echo sprintf(
            "%sMemory Difference: %.2f%% %s%s\n\n",
            $memoryColor,
            abs($memoryDiff),
            $memoryDiff > 0 ? 'less' : 'more',
            self::ANSI_RESET
        );

        // Peak Memory
        echo self::ANSI_BOLD . "Peak Memory Usage\n" . self::ANSI_RESET;
        echo str_repeat('-', 40) . "\n";

        $originalPeakMB = $originalStats['peak'] / 1024 / 1024;
        $optimizedPeakMB = $optimizedStats['peak'] / 1024 / 1024;
        $peakDiff = $this->calculatePercentageDiff($originalStats['peak'], $optimizedStats['peak']);
        $peakColor = $peakDiff > 0 ? self::ANSI_GREEN : self::ANSI_RED;

        echo sprintf("%sOriginal Peak:  %.2f MB%s\n", self::ANSI_YELLOW, $originalPeakMB, self::ANSI_RESET);
        echo sprintf("%sOptimized Peak: %.2f MB%s\n", self::ANSI_YELLOW, $optimizedPeakMB, self::ANSI_RESET);
        echo sprintf(
            "%sPeak Difference: %.2f%% %s%s\n\n",
            $peakColor,
            abs($peakDiff),
            $peakDiff > 0 ? 'less' : 'more',
            self::ANSI_RESET
        );

        // Per Iteration Stats
        echo self::ANSI_BOLD . "Per Iteration Stats\n" . self::ANSI_RESET;
        echo str_repeat('-', 40) . "\n";

        $originalTimePerIteration = ($originalStats['time'] * 1000) / self::ITERATIONS;
        $optimizedTimePerIteration = ($optimizedStats['time'] * 1000) / self::ITERATIONS;

        echo sprintf(
            "%sOriginal Time per Iteration:  %.6f ms%s\n",
            self::ANSI_YELLOW,
            $originalTimePerIteration,
            self::ANSI_RESET
        );
        echo sprintf(
            "%sOptimized Time per Iteration: %.6f ms%s\n",
            self::ANSI_YELLOW,
            $optimizedTimePerIteration,
            self::ANSI_RESET
        );

        echo "\n" . str_repeat('=', 60) . "\n";
    }

    private function calculatePercentageDiff(float $original, float $optimized): float
    {
        if ($original <= 0) {
            return 0;
        }

        return (($original - $optimized) / $original) * 100;
    }
}

final class AttributeHandlerOtimized implements PropertyAttributeHandler, PropertyChangeApplier
{
    private array $processedPropertyValues = [];
    private array $processingResultErrors = [];
    private array $processingResultMessages = [];
    private array $processorCache = [];

    public function __construct(
        private readonly string $processorType,
        private readonly ProcessorBuilder $builder,
        private readonly ProcessorProcessorContract $validator = new ProcessorValidator(),
        private readonly ProcessorConfigBuilderContract $configBuilder = new ProcessorConfigBuilder()
    ) {
    }

    public function handleAttribute(string $propertyName, object $attribute, mixed $value): mixed
    {
        if (!$attribute instanceof ProcessableAttribute) {
            return null;
        }

        try {
            return $this->processAttribute($propertyName, $attribute, $value);
        } catch (\Exception $e) {
            $this->processingResultErrors[$propertyName][] = $e->getMessage();

            return $value;
        }
    }

    private function processAttribute(string $propertyName, ProcessableAttribute $attribute, mixed $value): mixed
    {
        $config = $this->configBuilder->build($attribute);
        $messages = [];

        if ($attribute instanceof CustomizableMessageAttribute) {
            foreach ($config as $processorName => &$processorConfig) {
                if ($message = $attribute->getMessage($processorName)) {
                    $processorConfig['customMessage'] = $message;
                    $messages[$processorName] = $message;
                }
            }
        }

        $processedValue = $this->processValue($value, $config);

        if ($errors = $this->validateProcessors($config, $messages)) {
            $this->processingResultErrors[$propertyName] = $errors;
        }

        $this->processedPropertyValues[$propertyName] = [
            'value' => $processedValue,
            'messages' => $messages,
        ];

        $this->processingResultMessages[$propertyName] = $messages;

        return $processedValue;
    }

    private function validateProcessors(array $processorsConfig, array $messages): array
    {
        $errors = [];
        foreach ($processorsConfig as $processorName => $config) {
            // Simplify cache key to processor name
            if (!isset($this->processorCache[$processorName])) {
                $this->processorCache[$processorName] = $this->builder->build(
                    $this->processorType,
                    $processorName,
                    $config
                );
            }

            $processor = $this->processorCache[$processorName];

            if ($error = $this->validator->validate($processor, $processorName, $messages)) {
                $errors[$processorName] = $error;
            }
        }

        return $errors;
    }

    private function processValue(mixed $value, array $config): mixed
    {
        return $this->builder
            ->buildPipeline($this->processorType, $config)
            ->process($value);
    }

    public function applyChanges(object $entity): void
    {
        foreach ($this->processedPropertyValues as $propertyName => $data) {
            (new PropertyAccessor($entity, $propertyName))->setValue($data['value']);
        }
    }

    public function getProcessedPropertyValues(): array
    {
        return $this->processedPropertyValues;
    }

    public function getProcessingResultErrors(): array
    {
        return $this->processingResultErrors;
    }

    public function getProcessingResultMessages(): array
    {
        return $this->processingResultMessages;
    }
}

// Before
// class AttributeHandler implements PropertyAttributeHandler, PropertyChangeApplier
// {
//     private array $processedPropertyValues = [];
//     private array $processingResultErrors = [];
//     private array $processingResultMessages = [];

//     public function __construct(
//         private readonly string $processorType,
//         private readonly ProcessorBuilder $builder,
//         private readonly ProcessorProcessorContract $validator = new ProcessorValidator(),
//         private readonly ProcessorConfigBuilderContract $configBuilder = new ProcessorConfigBuilder()
//     ) {
//     }

//     public function handleAttribute(string $propertyName, object $attribute, mixed $value): mixed
//     {
//         if (!$attribute instanceof ProcessableAttribute) {
//             return null;
//         }

//         $processorsConfig = $this->configBuilder->build($attribute);
//         $messages = $this->extractCustomMessages($attribute, $processorsConfig);

//         try {
//             $processedValue = $this->processValue($value, $processorsConfig);
//             $errors = $this->validateProcessors($processorsConfig, $messages);

//             $this->storeProcessedPropertyValue($propertyName, $processedValue, $messages);

//             if (!empty($errors)) {
//                 $this->storeProcessingResultErrors($propertyName, $errors);
//             }

//             return $processedValue;
//         } catch (\Exception $e) {
//             $this->storeProcessingResultError($propertyName, $e->getMessage());

//             return $value;
//         }
//     }

//     private function validateProcessors(array $processorsConfig, array $messages): array
//     {
//         $errors = [];
//         foreach ($processorsConfig as $processorName => $config) {
//             $processor = $this->builder->build($this->processorType, $processorName, $config);
//             $validationError = $this->validator->validate(
//                 $processor,
//                 $processorName,
//                 $messages
//             );

//             if ($this->shouldAddValidationError($validationError, $errors, $processorName)) {
//                 $errors[$processorName] = $validationError;
//             }
//         }

//         return $errors;
//     }

//     private function shouldAddValidationError(?array $validationError, array $errors, string $processorName): bool
//     {
//         return null !== $validationError && !isset($errors[$processorName]);
//     }

//     private function storeProcessingResultErrors(string $propertyName, array $errors): void
//     {
//         $this->processingResultErrors[$propertyName] = $errors;
//     }

//     private function extractCustomMessages(ProcessableAttribute $attribute, array &$processorsConfig): array
//     {
//         $messages = [];
//         if ($attribute instanceof CustomizableMessageAttribute) {
//             foreach ($processorsConfig as $processorName => &$config) {
//                 $customMessage = $attribute->getMessage($processorName);
//                 if (null !== $customMessage) {
//                     $config['customMessage'] = $customMessage;
//                     $messages[$processorName] = $customMessage;
//                 }
//             }
//         }

//         return $messages;
//     }

//     private function processValue(mixed $value, array $processorsConfig): mixed
//     {
//         $pipeline = $this->builder->buildPipeline(
//             $this->processorType,
//             $processorsConfig
//         );

//         return $pipeline->process($value);
//     }

//     private function storeProcessedPropertyValue(string $propertyName, mixed $processedValue, array $messages): void
//     {
//         $this->processedPropertyValues[$propertyName] = [
//             'value' => $processedValue,
//             'messages' => $messages,
//         ];
//         $this->processingResultMessages[$propertyName] = $messages;
//     }

//     private function storeProcessingResultError(string $propertyName, string $errorMessage): void
//     {
//         $this->processingResultErrors[$propertyName][] = $errorMessage;
//     }

//     public function applyChanges(object $entity): void
//     {
//         foreach ($this->processedPropertyValues as $propertyName => $data) {
//             (new PropertyAccessor($entity, $propertyName))->setValue($data['value']);
//         }
//     }

//     public function getProcessedPropertyValues(): array
//     {
//         return $this->processedPropertyValues;
//     }

//     public function getProcessingResultErrors(): array
//     {
//         return $this->processingResultErrors;
//     }

//     public function getProcessingResultMessages(): array
//     {
//         return $this->processingResultMessages;
//     }
// }

$benchmark = new BenchmarkRunner();
$benchmark->run();
