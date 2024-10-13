# KaririCode Framework: ProcessorPipeline Component

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
use KaririCode\Contract\Processor\Processor;

class EmailNormalizer implements Processor
{
    public function process(mixed $input): string
    {
        return strtolower(trim($input));
    }
}

class EmailValidator implements Processor
{
    public function process(mixed $input): bool
    {
        return false !== filter_var($input, FILTER_VALIDATE_EMAIL);
    }
}
```

2. Set up the processor registry and builder:

```php
use KaririCode\ProcessorPipeline\ProcessorRegistry;
use KaririCode\ProcessorPipeline\ProcessorBuilder;

$registry = new ProcessorRegistry();
$registry->register('user', 'emailNormalizer', new EmailNormalizer());
$registry->register('user', 'emailValidator', new EmailValidator());

$builder = new ProcessorBuilder($registry);
```

3. Build and use a pipeline:

```php
$pipeline = $builder->buildPipeline('user', ['emailNormalizer', 'emailValidator']);

$email = '  JOHN.DOE@example.com  ';
$normalizedEmail = $pipeline->process($email);
$isValid = $pipeline->process($normalizedEmail);

echo "Normalized: $normalizedEmail\n";
echo "Valid: " . ($isValid ? 'Yes' : 'No') . "\n";
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

```php
use KaririCode\Validator\Validators\EmailValidator;
use KaririCode\Validator\Validators\NotEmptyValidator;

$registry->register('validation', 'email', new EmailValidator());
$registry->register('validation', 'notEmpty', new NotEmptyValidator());

$validationPipeline = $builder->buildPipeline('validation', ['notEmpty', 'email']);

$isValid = $validationPipeline->process($userInput);
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
