# KaririCode Framework: ProcessorPipeline V4.0

[![en](https://img.shields.io/badge/lang-en-red.svg)](README.md)
[![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](README.pt-br.md)
[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4+-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![MIT License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![ARFA 1.3](https://img.shields.io/badge/ARFA-1.3-orange.svg)](docs/specs/SPEC-001-processor-pipeline.md)

A robust, immutable processor pipeline component for the KaririCode Framework. Enables modular, configurable processing chains for data transformation, validation, and sanitization — fully compliant with ARFA 1.3.

## Features

- **Immutable pipelines** — `withProcessor()` returns new instances (ARFA P1)
- **Context-based registry** — collision-free processor management across domains
- **Flexible specification format** — simple lists, enable/disable flags, and configuration arrays
- **Configurable processors** — runtime configuration via `ConfigurableProcessor` interface
- **Error collection** — `ProcessingResultCollection` for non-halting error aggregation
- **PHP 8.4+** — `readonly class`, asymmetric visibility, constructor promotion
- **Zero external dependencies** — depends only on `kariricode/contract`
- **Structured exceptions** — all exceptions carry context arrays for observability

## Installation

```bash
composer require kariricode/processor-pipeline
```

**Requirements:** PHP 8.4+, Composer

## Quick Start

```php
use KaririCode\Contract\Processor\Processor;
use KaririCode\ProcessorPipeline\ProcessorRegistry;
use KaririCode\ProcessorPipeline\ProcessorBuilder;

// 1. Define processors
class TrimProcessor implements Processor
{
    public function process(mixed $input): mixed
    {
        return trim((string) $input);
    }
}

class LowercaseProcessor implements Processor
{
    public function process(mixed $input): mixed
    {
        return strtolower((string) $input);
    }
}

// 2. Register
$registry = new ProcessorRegistry();
$registry
    ->register('sanitizer', 'trim', new TrimProcessor())
    ->register('sanitizer', 'lowercase', new LowercaseProcessor());

// 3. Build & Execute
$builder = new ProcessorBuilder($registry);
$pipeline = $builder->buildPipeline('sanitizer', ['trim', 'lowercase']);

$result = $pipeline->process('  HELLO WORLD  ');
// Result: 'hello world'
```

## Usage

### Configurable Processors

```php
use KaririCode\Contract\Processor\ConfigurableProcessor;

class LengthValidator implements ConfigurableProcessor
{
    private int $minLength = 0;
    private int $maxLength = PHP_INT_MAX;

    public function configure(array $options): void
    {
        $this->minLength = $options['minLength'] ?? $this->minLength;
        $this->maxLength = $options['maxLength'] ?? $this->maxLength;
    }

    public function process(mixed $input): mixed
    {
        $length = mb_strlen((string) $input);

        if ($length < $this->minLength || $length > $this->maxLength) {
            throw new \InvalidArgumentException(
                "Length must be between {$this->minLength} and {$this->maxLength}."
            );
        }

        return $input;
    }
}

$registry->register('validator', 'length', new LengthValidator());

$pipeline = $builder->buildPipeline('validator', [
    'length' => ['minLength' => 3, 'maxLength' => 50],
]);
```

### Processor Specification Format

```php
$pipeline = $builder->buildPipeline('validator', [
    'required',                  // Simple: enabled
    'trim' => true,              // Explicit: enabled
    'optional' => false,         // Explicit: disabled (skipped)
    'length' => ['min' => 3],    // Configured
]);
```

### Immutable Pipeline Composition

```php
$basePipeline = $builder->buildPipeline('sanitizer', ['trim']);
$extendedPipeline = $basePipeline->withProcessor(new LowercaseProcessor());

// $basePipeline still has 1 processor
// $extendedPipeline has 2 processors
```

### Error Collection

```php
use KaririCode\ProcessorPipeline\Handler\ProcessorHandler;
use KaririCode\ProcessorPipeline\Result\ProcessingResultCollection;

$results = new ProcessingResultCollection();

// Wrap processors with error collection
$handler = new ProcessorHandler(
    processor: new EmailValidator(),
    resultCollection: $results,
    haltOnError: false,
);

$output = $handler->process('invalid-email');

if ($results->hasErrors()) {
    foreach ($results->getErrors() as $processor => $errors) {
        foreach ($errors as $error) {
            echo "{$processor}: {$error['message']}\n";
        }
    }
}
```

### PHP 8.4 Attributes

```php
use KaririCode\ProcessorPipeline\Attribute\Process;

class UserProfile
{
    #[Process(
        processors: ['required', 'length' => ['minLength' => 3]],
        messages: ['required' => 'Username is required.'],
    )]
    public private(set) string $username = '';

    #[Process(
        processors: ['trim', 'lowercase', 'email'],
        messages: ['email' => 'Invalid email.'],
    )]
    public private(set) string $email = '';
}
```

## Architecture

```
Consumer Code
      │
      ▼
ProcessorBuilder ──→ ProcessorRegistry
      │                  (context → name → Processor)
      │ creates
      ▼
   Pipeline (immutable, readonly)
   [P₁, P₂, ..., Pₙ]
      │
      ▼
   process(input) → output
```

### ARFA 1.3 Compliance

| Principle | Implementation |
|-----------|---------------|
| P1: Immutable State | `Pipeline` is `readonly class`; `withProcessor()` returns new instance |
| P2: Reactive Flow | Sequential composition: f₁ ∘ f₂ ∘ ... ∘ fₙ |
| P3: Adaptive Context | Context-based registry; ConfigurableProcessor |
| P4: Protocol Agnostic | No protocol coupling; works with HTTP, gRPC, CLI, etc. |
| P5: Observability | Structured exceptions; ProcessingResultCollection tracing |

## Integration with KaririCode Components

| Component | Role |
|-----------|------|
| `kariricode/contract` | `Processor`, `ConfigurableProcessor` interfaces |
| `kariricode/validator` | Validation pipelines via `#[Validate]` |
| `kariricode/sanitizer` | Sanitization pipelines via `#[Sanitize]` |
| `kariricode/transformer` | Transformation pipelines via `#[Transform]` |
| `kariricode/property-inspector` | Discovers `#[Process]` attributes on properties |

## Development

```bash
git clone https://github.com/KaririCode-Framework/kariricode-processor-pipeline.git
cd kariricode-processor-pipeline
make setup-env && make up && make composer-install
make test          # Run tests
make coverage      # Coverage report
make cs-fix        # Code style
make quality       # Full quality check
```

## Documentation

- **[Technical Specification](docs/specs/SPEC-001-processor-pipeline.md)** — algorithms, type definitions, complexity analysis
- **[ADR-001: Immutable Pipeline](docs/adrs/ADR-001-immutable-pipeline.md)** — why pipelines are readonly
- **[ADR-002: Context Registry](docs/adrs/ADR-002-context-based-registry.md)** — two-level registry design
- **[ADR-003: Specification Format](docs/adrs/ADR-003-processor-specification-format.md)** — flexible processor specs

## License

MIT License — see [LICENSE](LICENSE) for details.

## Support

- **Documentation**: [kariricode.org/docs/processor-pipeline](https://kariricode.org/docs/processor-pipeline)
- **Issues**: [GitHub Issues](https://github.com/KaririCode-Framework/kariricode-processor-pipeline/issues)
- **Community**: [KaririCode Club](https://kariricode.club)

---

Built with ❤️ by the KaririCode team. Empowering developers to create robust PHP applications.
