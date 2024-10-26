<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Contract\Processor\Processor;
use KaririCode\ProcessorPipeline\ProcessorBuilder;
use KaririCode\ProcessorPipeline\ProcessorRegistry;
use KaririCode\ProcessorPipeline\Result\ProcessingResultCollection;

// Example of actual processors.
class UpperCaseProcessor implements Processor
{
    public function process(mixed $input): mixed
    {
        return strtoupper((string) $input);
    }
}

class TrimProcessor implements Processor
{
    public function process(mixed $input): mixed
    {
        return trim((string) $input);
    }
}

class EmailTransformerProcessor implements Processor
{
    public function process(mixed $input): mixed
    {
        return strtolower((string) $input);
    }
}

class EmailValidatorProcessor implements Processor
{
    public function __construct(private ProcessingResultCollection $resultCollection)
    {
    }

    public function process(mixed $input): mixed
    {
        if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $this->resultCollection->addError(
                self::class,
                'invalidFormat',
                "Invalid email format: $input"
            );
        }

        return $input;
    }
}

// Function to handle pipeline execution
function executePipeline(ProcessorBuilder $builder, ProcessorRegistry $registry, array $processorSpecs, string $input): void
{
    $resultCollection = new ProcessingResultCollection();
    $context = 'example_context';

    $registry->register($context, 'upper_case', new UpperCaseProcessor())
        ->register($context, 'trim', new TrimProcessor())
        ->register($context, 'email_transform', new EmailTransformerProcessor())
        ->register($context, 'email_validate', new EmailValidatorProcessor($resultCollection));

    try {
        $pipeline = $builder->buildPipeline($context, $processorSpecs);
        $output = $pipeline->process($input);

        // Displaying the results
        echo "Original Input: '$input'\n";
        echo "Pipeline Output: '$output'\n";

        // Display errors if any
        if ($resultCollection->hasErrors()) {
            echo "\nProcessing Errors:\n";
            print_r($resultCollection->getErrors());
        } else {
            echo "\nNo processing errors encountered.\n";
        }
    } catch (Exception $e) {
        echo 'Error executing the pipeline: ' . $e->getMessage() . "\n";
    }
}

// Register processors to a context in the registry.
$registry = new ProcessorRegistry();
$builder = new ProcessorBuilder($registry);

// Execute scenario 1 - Valid input
$processorSpecs = [
    'upper_case' => false,
    'trim' => true,
    'email_transform' => true,
    'email_validate' => true,
];
$input = '   Example@Email.COM   ';

echo "Scenario 1 - Valid Input\n";
executePipeline($builder, $registry, $processorSpecs, $input);

// Execute scenario 2 - Invalid input
$input = '   InvalidEmail@@@   ';

echo "\nScenario 2 - Invalid Input (English)\n";
executePipeline($builder, $registry, $processorSpecs, $input);
