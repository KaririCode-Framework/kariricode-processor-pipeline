<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Contract\Processor\ConfigurableProcessor;
use KaririCode\Contract\Processor\Processor;
use KaririCode\ProcessorPipeline\ProcessorBuilder;
use KaririCode\ProcessorPipeline\ProcessorRegistry;

// Defining the User entity
class User
{
    public function __construct(
        private string $email,
        private string $name,
        private int $age
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }
}

// Defining specific processors
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

class NameCapitalizer implements Processor
{
    public function process(mixed $input): string
    {
        return ucwords(strtolower($input));
    }
}

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

// Configuring the processor registry
$registry = new ProcessorRegistry();
$registry->register('user', 'emailNormalizer', new EmailNormalizer());
$registry->register('user', 'emailValidator', new EmailValidator());
$registry->register('user', 'nameCapitalizer', new NameCapitalizer());
$registry->register('user', 'ageValidator', new AgeValidator());

// Creating the ProcessorBuilder
$builder = new ProcessorBuilder($registry);

// Function to process user data
function processUser(User $user, ProcessorBuilder $builder): array
{
    $result = [];

    // Processing the email
    $emailNormalizerPipeline = $builder->buildPipeline('user', ['emailNormalizer']);
    $normalizedEmail = $emailNormalizerPipeline->process($user->getEmail());
    $user->setEmail($normalizedEmail);
    $emailValidatorPipeline = $builder->buildPipeline('user', ['emailValidator']);
    $result['email'] = [
        'value' => $user->getEmail(),
        'isValid' => $emailValidatorPipeline->process($user->getEmail()),
    ];

    // Processing the name
    $namePipeline = $builder->buildPipeline('user', ['nameCapitalizer']);
    $capitalizedName = $namePipeline->process($user->getName());
    $user->setName($capitalizedName);
    $result['name'] = $user->getName();

    // Processing the age
    $agePipeline = $builder->buildPipeline('user', ['ageValidator' => ['minAge' => 18, 'maxAge' => 100]]);
    $result['age'] = [
        'value' => $user->getAge(),
        'isValid' => $agePipeline->process($user->getAge()),
    ];

    return $result;
}

// Usage example
$user = new User(
    email: '   JOHN.DOE@example.com  ',
    name: 'jOHn doE',
    age: 25
);

$processedData = processUser($user, $builder);
print_r($processedData);

// Printing the modified user
echo "\nUser after processing:\n";
echo 'Email: ' . $user->getEmail() . "\n";
echo 'Name: ' . $user->getName() . "\n";
echo 'Age: ' . $user->getAge() . "\n";
