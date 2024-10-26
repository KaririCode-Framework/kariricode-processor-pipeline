# KaririCode Framework: Processor Pipeline Component

[![en](https://img.shields.io/badge/lang-en-red.svg)](README.md) [![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](README.pt-br.md)

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white) ![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white) ![PHPUnit](https://img.shields.io/badge/PHPUnit-3776AB?style=for-the-badge&logo=php&logoColor=white)

Um componente robusto e flexível para criar e gerenciar pipelines de processamento no KaririCode Framework, oferecendo recursos avançados para lidar com tarefas complexas de processamento de dados em aplicações PHP.

## Índice

- [Funcionalidades](#funcionalidades)
- [Instalação](#instalação)
- [Uso](#uso)
  - [Uso Básico](#uso-básico)
  - [Uso Avançado](#uso-avançado)
- [Integração com Outros Componentes do KaririCode](#integração-com-outros-componentes-do-kariricode)
- [Desenvolvimento e Testes](#desenvolvimento-e-testes)
- [Licença](#licença)
- [Suporte e Comunidade](#suporte-e-comunidade)
- [Agradecimentos](#agradecimentos)

## Funcionalidades

- Criação e gestão fáceis de pipelines de processamento
- Suporte para processadores simples e configuráveis
- Registro de processadores baseado em contexto para uma gestão organizada
- Integração transparente com outros componentes do KaririCode (Serializer, Validator, Normalizer)
- Arquitetura extensível que permite processadores personalizados
- Construído sobre as interfaces do KaririCode\Contract para máxima flexibilidade

## Instalação

O componente ProcessorPipeline pode ser facilmente instalado via Composer, que é o gerenciador de dependências recomendado para projetos PHP.

Para instalar o componente ProcessorPipeline no seu projeto, execute o seguinte comando no terminal:

```bash
composer require kariricode/processor-pipeline
```

Esse comando adicionará automaticamente o ProcessorPipeline ao seu projeto e instalará todas as dependências necessárias.

### Requisitos

- PHP 8.1 ou superior
- Composer

### Instalação Manual

Se preferir não usar o Composer, você pode baixar o código-fonte diretamente do [repositório GitHub](https://github.com/KaririCode-Framework/kariricode-processor-pipeline) e incluí-lo manualmente no seu projeto. No entanto, recomendamos fortemente o uso do Composer para facilitar o gerenciamento de dependências e atualizações.

Após a instalação, você pode começar a usar o ProcessorPipeline no seu projeto PHP imediatamente. Certifique-se de incluir o autoloader do Composer em seu script:

```php
require_once 'vendor/autoload.php';
```

## Uso

### Uso Básico

1. Defina seus processadores:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Contract\Processor\Processor;
use KaririCode\ProcessorPipeline\ProcessorBuilder;
use KaririCode\ProcessorPipeline\ProcessorRegistry;
use KaririCode\ProcessorPipeline\Result\ProcessingResultCollection;

// Exemplo de processadores reais.
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
                "Formato de email inválido: $input"
            );
        }
        return $input;
    }
}

// Função para executar o pipeline
function executePipeline(ProcessorBuilder $builder, ProcessorRegistry $registry, array $processorSpecs, string $input): void
{
    $resultCollection = new ProcessingResultCollection();
    $context = 'exemplo_contexto';

    $registry->register($context, 'upper_case', new UpperCaseProcessor())
             ->register($context, 'trim', new TrimProcessor())
             ->register($context, 'email_transform', new EmailTransformerProcessor())
             ->register($context, 'email_validate', new EmailValidatorProcessor($resultCollection));

    try {
        $pipeline = $builder->buildPipeline($context, $processorSpecs);
        $output = $pipeline->process($input);

        // Exibindo os resultados
        echo "Entrada Original: '$input'\n";
        echo "Saída do Pipeline: '$output'\n";

        // Exibir erros, se houver
        if ($resultCollection->hasErrors()) {
            echo "\nErros de Processamento:\n";
            print_r($resultCollection->getErrors());
        } else {
            echo "\nNenhum erro de processamento encontrado.\n";
        }
    } catch (\Exception $e) {
        echo "Erro ao executar o pipeline: " . $e->getMessage() . "\n";
    }
}

// Registrar processadores em um contexto no registro.
$registry = new ProcessorRegistry();
$builder = new ProcessorBuilder($registry);

// Executar cenário 1 - Entrada válida
$processorSpecs = [
    'upper_case' => false,
    'trim' => true,
    'email_transform' => true,
    'email_validate' => true,
];
$input = "   Exemplo@Email.COM   ";

echo "Cenário 1 - Entrada Válida\n";
executePipeline($builder, $registry, $processorSpecs, $input);

// Executar cenário 2 - Entrada inválida
$input = "   EmailInválido@@@   ";

echo "\nCenário 2 - Entrada Inválida:\n";
executePipeline($builder, $registry, $processorSpecs, $input);
```

### Saída de Teste

```bash
php ./tests/application.php
Cenário 1 - Entrada Válida
Entrada Original: '   Exemplo@Email.COM   '
Saída do Pipeline: 'exemplo@email.com'

Nenhum erro de processamento encontrado.

Cenário 2 - Entrada Inválida:
Entrada Original: '   EmailInválido@@@   '
Saída do Pipeline: 'emailinvalido@@@'

Erros de Processamento:
Array
(
    [EmailValidatorProcessor] => Array
        (
            [0] => Array
                (
                    [chaveErro] => formatoInvalido
                    [mensagem] => Formato de email inválido: emailinvalido@@@
                )

        )
)
```

### Uso Avançado

#### Processadores Configuráveis

Crie processadores configuráveis para mais flexibilidade:

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

$registry->register('usuario', 'ageValidator', new AgeValidator());
$pipeline = $builder->buildPipeline('usuario', ['ageValidator' => ['minAge' => 18, 'maxAge' => 100]]);
```

## Integração com Outros Componentes do KaririCode

O componente ProcessorPipeline foi projetado para funcionar perfeitamente com outros componentes do KaririCode:

- **KaririCode\Serializer**: Use processadores para transformar dados antes ou depois da serialização.
- **KaririCode\Validator**: Crie pipelines de validação para estruturas de dados complexas.
- **KaririCode\Normalizer**: Construa pipelines de normalização para limpeza e padronização de dados.

Exemplo de uso do ProcessorPipeline com Validator:

1. Defina sua classe de dados com atributos de validação:

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
            'required' => 'Nome de usuário é obrigatório',
            'length' => 'Nome de usuário deve ter entre 3 e 20 caracteres',
        ]
    )]
    private string $username = '';

    #[Validate(
        processors: ['required', 'email'],
        messages: [
            'required' => 'Email é obrigatório',
            'email' => 'Formato de email inválido',
        ]
    )

]
    private string $email = '';

    // Getters e setters...
}
```

2. Configure o validador e utilize-o:

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
$userProfile->setUsername('wa');  // Muito curto
$userProfile->setEmail('email-invalido');  // Formato inválido

$result = $validator->validate($userProfile);

if ($result->hasErrors()) {
    foreach ($result->getErrors() as $property => $errors) {
        foreach ($errors as $error) {
            echo "$property: {$error['message']}\n";
        }
    }
}
```

## Desenvolvimento e Testes

Para fins de desenvolvimento e teste, este pacote usa Docker e Docker Compose para garantir a consistência entre diferentes ambientes. Um Makefile é fornecido para conveniência.

### Pré-requisitos

- Docker
- Docker Compose
- Make (opcional, mas recomendado para execução mais fácil de comandos)

### Configuração de Desenvolvimento

1. Clone o repositório:

   ```bash
   git clone https://github.com/KaririCode-Framework/kariricode-processor-pipeline.git
   cd kariricode-processor-pipeline
   ```

2. Configure o ambiente:

   ```bash
   make setup-env
   ```

3. Inicie os contêineres Docker:

   ```bash
   make up
   ```

4. Instale as dependências:
   ```bash
   make composer-install
   ```

### Comandos Disponíveis no Make

- `make up`: Iniciar todos os serviços em segundo plano
- `make down`: Parar e remover todos os contêineres
- `make build`: Construir as imagens Docker
- `make shell`: Acessar o shell do contêiner PHP
- `make test`: Executar os testes
- `make coverage`: Executar cobertura de teste com formatação visual
- `make cs-fix`: Executar o PHP CS Fixer para corrigir o estilo de código
- `make quality`: Executar todos os comandos de qualidade (cs-check, test, security-check)

Para uma lista completa dos comandos disponíveis, execute:

```bash
make help
```

## Licença

Este projeto é licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## Suporte e Comunidade

- **Documentação**: [https://kariricode.org/docs/processor-pipeline](https://kariricode.org/docs/processor-pipeline)
- **Relatório de Problemas**: [GitHub Issues](https://github.com/KaririCode-Framework/kariricode-processor-pipeline/issues)
- **Comunidade**: [Comunidade KaririCode Club](https://kariricode.club)

## Agradecimentos

- Equipe e colaboradores do KaririCode Framework.
- Inspirado por padrões de pipeline e cadeias de processamento em arquitetura de software.

---

Feito com ❤️ pela equipe KaririCode. Capacitando desenvolvedores a criar aplicações PHP mais robustas e flexíveis.
