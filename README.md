# KaririCode Framework: Processor Pipeline Component

[![en](https://img.shields.io/badge/lang-en-red.svg)](README.md) [![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](README.pt-br.md)

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white) ![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white) ![PHPUnit](https://img.shields.io/badge/PHPUnit-3776AB?style=for-the-badge&logo=php&logoColor=white)

A robust and flexible component for creating and managing processing pipelines in the KaririCode Framework, providing advanced features for handling complex data processing tasks in PHP applications.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
  - [Basic Usage](#basic-usage)
  - [Advanced Usage](#advanced-usage)
- [Integration with Other KaririCode Components](#integration-with-other-kariricode-components)
- [Development and Testing](#development-and-testing)
- [License](#license)
- [Support and Community](#support-and-community)
- [Acknowledgements](#acknowledgements)

## Features

- Easy creation and management of processing pipelines
- Support for both simple and configurable processors
- Context-based processor registry for organized processor management
- Seamless integration with other KaririCode components (Serializer, Validator, Normalizer)
- Extensible architecture allowing custom processors
- Built on top of the KaririCode\Contract interfaces for maximum flexibility

## Installation

The ProcessorPipeline component can be easily installed via Composer, which is the recommended dependency manager for PHP projects.

To install the ProcessorPipeline component in your project, run the following command in your terminal:

```bash
composer require kariricode/processor-pipeline
```

This command will automatically add ProcessorPipeline to your project and install all necessary dependencies.

### Requirements

- PHP 8.1 or higher
- Composer

### Manual Installation

If you prefer not to use Composer, you can download the source code directly from the [GitHub repository](https://github.com/KaririCode-Framework/kariricode-processor-pipeline) and include it manually in your project. However, we strongly recommend using Composer for easier dependency management and updates.

After installation, you can start using ProcessorPipeline in your PHP project immediately. Make sure to include the Composer autoloader in your script:

```php
require_once 'vendor/autoload.php';
```

## Usage

### Basic Usage

1. Define your processors:

```php
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
    } catch (\Exception $e) {
        echo "Error executing the pipeline: " . $e->getMessage() . "\n";
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
$input = "   Example@Email.COM   ";

echo "Scenario 1 - Valid Input\n";
executePipeline($builder, $registry, $processorSpecs, $input);

// Execute scenario 2 - Invalid input
$input = "   InvalidEmail@@@   ";

echo "\nScenario 2 - Invalid Input:\n";
executePipeline($builder, $registry, $processorSpecs, $input);
```

### Test Output

```bash
php ./tests/application.php
Scenario 1 - Valid Input
Original Input: '   Example@Email.COM   '
Pipeline Output: 'example@email.com'

No processing errors encountered.

Scenario 2 - Invalid Input:
Original Input: '   InvalidEmail@@@   '
Pipeline Output: 'invalidemail@@@'

Processing Errors:
Array
(
    [EmailValidatorProcessor] => Array
        (
            [0] => Array
                (
                    [errorKey] => invalidFormat
                    [message] => Invalid email format: invalidemail@@@
                )

        )
)
```

### Advanced Usage

#### Configurable Processors

Create configurable processors for more flexibility:

```php
use KaririCode\Contract\Processor\ConfigurableProcessor;

class AgeValidator implements ConfigurableProcessor
{
    private int $minAge = 0;
    private int $maxAge = 120;

    public function configure(array $options): void
    {
        if (isset($options['minAge'])) {
            $this->minAge = $options['minAge'];
        }
        if (isset($options['maxAge'])) {
            $this->maxAge = $options['maxAge'];
        }
    }

    public function process(mixed $input): bool
    {
        return is_numeric($input) && $input >= $this->minAge && $input <= $this->maxAge;
    }
}

$registry->register('user', 'ageValidator', new AgeValidator());
$pipeline = $builder->buildPipeline('user', ['ageValidator' => ['minAge' => 18, 'maxAge' => 100]]);
```

## Integration with Other KaririCode Components

The ProcessorPipeline component is designed to work seamlessly with other KaririCode components:

- **KaririCode\Serializer**: Use processors to transform data before or after serialization.
- **KaririCode\Validator**: Create validation pipelines for complex data structures.
- **KaririCode\Normalizer**: Build normalization pipelines for data cleaning and standardization.

Example using ProcessorPipeline with Validator:

1. Define your data class with validation attributes:

```php
use KaririCode\Validator\Attribute\Validate;

class UserProfile
{
    #[Validate(
        processors: [
            'required',
            'length' => ['minLength' => 3, 'maxLength' => 20],
        ],
        messages: [
            'required' => 'Username is required',
            'length' => 'Username must be between 3 and 20 characters',
        ]
    )]
    private string $username = '';

    #[Validate(
        processors: ['required', 'email'],
        messages: [
            'required' => 'Email is required',
            'email' => 'Invalid email format',
        ]
    )]
    private string $email = '';

    // Getters and setters...
}
```

2. Set up the validator and use it:

```php
use KaririCode\ProcessorPipeline\ProcessorRegistry;
use KaririCode\Validator\Validator;
use KaririCode\Validator\Processor\Logic\RequiredValidator;
use KaririCode\Validator\Processor\Input\LengthValidator;
use KaririCode\Validator\Processor\Input\EmailValidator;

$registry = new ProcessorRegistry();
$registry->register('validator', 'required', new RequiredValidator())
         ->register('validator', 'length', new LengthValidator())
         ->register('validator', 'email', new EmailValidator());

$validator = new Validator($registry);

$userProfile = new UserProfile();
$userProfile->setUsername('wa');  // Too short
$userProfile->setEmail('invalid-email');  // Invalid format

$result = $validator->validate($userProfile);

if ($result->hasErrors()) {
    foreach ($result->getErrors() as $property => $errors) {
        foreach ($errors as $error) {
            echo "$property: {$error['message']}\n";
        }
    }
}
```

## Development and Testing

For development and testing purposes, this package uses Docker and Docker Compose to ensure consistency across different environments. A Makefile is provided for convenience.

### Prerequisites

- Docker
- Docker Compose
- Make (optional, but recommended for easier command execution)

### Development Setup

1. Clone the repository:

   ```bash
   git clone https://github.com/KaririCode-Framework/kariricode-processor-pipeline.git
   cd kariricode-processor-pipeline
   ```

2. Set up the environment:

   ```bash
   make setup-env
   ```

3. Start the Docker containers:

   ```bash
   make up
   ```

4. Install dependencies:
   ```bash
   make composer-install
   ```

### Available Make Commands

- `make up`: Start all services in the background
- `make down`: Stop and remove all containers
- `make build`: Build Docker images
- `make shell`: Access the PHP container shell
- `make test`: Run tests
- `make coverage`: Run test coverage with visual formatting
- `make cs-fix`: Run PHP CS Fixer to fix code style
- `make quality`: Run all quality commands (cs-check, test, security-check)

For a full list of available commands, run:

```bash
make help
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support and Community

- **Documentation**: [https://kariricode.org/docs/processor-pipeline](https://kariricode.org/docs/processor-pipeline)
- **Issue Tracker**: [GitHub Issues](https://github.com/KaririCode-Framework/kariricode-processor-pipeline/issues)
- **Community**: [KaririCode Club Community](https://kariricode.club)

## Acknowledgements

- The KaririCode Framework team and contributors.
- Inspired by pipeline patterns and processing chains in software architecture.

---

Built with ❤️ by the KaririCode team. Empowering developers to create more robust and flexible PHP applications.
