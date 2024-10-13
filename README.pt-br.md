# KaririCode Framework: Componente ProcessorPipeline

[![en](https://img.shields.io/badge/lang-en-red.svg)](README.md) [![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](README.pt-br.md)

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white) ![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white) ![PHPUnit](https://img.shields.io/badge/PHPUnit-3776AB?style=for-the-badge&logo=php&logoColor=white)

Um componente robusto e flexível para criar e gerenciar pipelines de processamento no KaririCode Framework, fornecendo recursos avançados para lidar com tarefas complexas de processamento de dados em aplicações PHP.

## Índice

- [Funcionalidades](#funcionalidades)
- [Instalação](#instalação)
- [Uso](#uso)
  - [Uso Básico](#uso-básico)
  - [Uso Avançado](#uso-avançado)
- [Integração com Outros Componentes KaririCode](#integração-com-outros-componentes-kariricode)
- [Desenvolvimento e Testes](#desenvolvimento-e-testes)
- [Licença](#licença)
- [Suporte e Comunidade](#suporte-e-comunidade)
- [Agradecimentos](#agradecimentos)

## Funcionalidades

- Criação e gerenciamento fácil de pipelines de processamento
- Suporte para processadores simples e configuráveis
- Registro de processadores baseado em contexto para gerenciamento organizado
- Integração perfeita com outros componentes KaririCode (Serializer, Validator, Normalizer)
- Arquitetura extensível permitindo processadores personalizados
- Construído sobre as interfaces KaririCode\Contract para máxima flexibilidade

## Instalação

O componente ProcessorPipeline pode ser facilmente instalado via Composer, que é o gerenciador de dependências recomendado para projetos PHP.

Para instalar o componente ProcessorPipeline em seu projeto, execute o seguinte comando no terminal:

```bash
composer require kariricode/processor-pipeline
```

Este comando adicionará automaticamente o ProcessorPipeline ao seu projeto e instalará todas as dependências necessárias.

### Requisitos

- PHP 8.1 ou superior
- Composer

### Instalação Manual

Se você preferir não usar o Composer, você pode fazer o download do código-fonte diretamente do [repositório GitHub](https://github.com/KaririCode-Framework/kariricode-processor-pipeline) e incluí-lo manualmente em seu projeto. No entanto, recomendamos fortemente o uso do Composer para uma gestão mais fácil de dependências e atualizações.

Após a instalação, você pode começar a usar o ProcessorPipeline em seu projeto PHP imediatamente. Certifique-se de incluir o autoloader do Composer em seu script:

```php
require_once 'vendor/autoload.php';
```

## Uso

### Uso Básico

1. Defina seus processadores:

```php
use KaririCode\Contract\Processor\Processor;

class NormalizadorEmail implements Processor
{
    public function process(mixed $input): string
    {
        return strtolower(trim($input));
    }
}

class ValidadorEmail implements Processor
{
    public function process(mixed $input): bool
    {
        return false !== filter_var($input, FILTER_VALIDATE_EMAIL);
    }
}
```

2. Configure o registro de processadores e o construtor:

```php
use KaririCode\ProcessorPipeline\ProcessorRegistry;
use KaririCode\ProcessorPipeline\ProcessorBuilder;

$registro = new ProcessorRegistry();
$registro->register('usuario', 'normalizadorEmail', new NormalizadorEmail());
$registro->register('usuario', 'validadorEmail', new ValidadorEmail());

$construtor = new ProcessorBuilder($registro);
```

3. Construa e use um pipeline:

```php
$pipeline = $construtor->buildPipeline('usuario', ['normalizadorEmail', 'validadorEmail']);

$email = '  JOAO.SILVA@exemplo.com  ';
$emailNormalizado = $pipeline->process($email);
$ehValido = $pipeline->process($emailNormalizado);

echo "Normalizado: $emailNormalizado\n";
echo "Válido: " . ($ehValido ? 'Sim' : 'Não') . "\n";
```

### Uso Avançado

#### Processadores Configuráveis

Crie processadores configuráveis para maior flexibilidade:

```php
use KaririCode\Contract\Processor\ConfigurableProcessor;

class ValidadorIdade implements ConfigurableProcessor
{
    private int $idadeMinima = 0;
    private int $idadeMaxima = 120;

    public function configure(array $opcoes): void
    {
        if (isset($opcoes['idadeMinima'])) {
            $this->idadeMinima = $opcoes['idadeMinima'];
        }
        if (isset($opcoes['idadeMaxima'])) {
            $this->idadeMaxima = $opcoes['idadeMaxima'];
        }
    }

    public function process(mixed $input): bool
    {
        return is_numeric($input) && $input >= $this->idadeMinima && $input <= $this->idadeMaxima;
    }
}

$registro->register('usuario', 'validadorIdade', new ValidadorIdade());
$pipeline = $construtor->buildPipeline('usuario', ['validadorIdade' => ['idadeMinima' => 18, 'idadeMaxima' => 100]]);
```

## Integração com Outros Componentes KaririCode

O componente ProcessorPipeline é projetado para trabalhar perfeitamente com outros componentes KaririCode:

- **KaririCode\Serializer**: Use processadores para transformar dados antes ou depois da serialização.
- **KaririCode\Validator**: Crie pipelines de validação para estruturas de dados complexas.
- **KaririCode\Normalizer**: Construa pipelines de normalização para limpeza e padronização de dados.

Exemplo usando ProcessorPipeline com Validator:

```php
use KaririCode\Validator\Validators\EmailValidator;
use KaririCode\Validator\Validators\NotEmptyValidator;

$registro->register('validacao', 'email', new EmailValidator());
$registro->register('validacao', 'naoVazio', new NotEmptyValidator());

$pipelineValidacao = $construtor->buildPipeline('validacao', ['naoVazio', 'email']);

$ehValido = $pipelineValidacao->process($entradaUsuario);
```

## Desenvolvimento e Testes

Para fins de desenvolvimento e teste, este pacote utiliza Docker e Docker Compose para garantir consistência entre diferentes ambientes. Um Makefile é fornecido para conveniência.

### Pré-requisitos

- Docker
- Docker Compose
- Make (opcional, mas recomendado para facilitar a execução de comandos)

### Configuração para Desenvolvimento

1. Clone o repositório:

   ```bash
   git clone https://github.com/KaririCode-Framework/kariricode-processor-pipeline.git
   cd kariricode-processor-pipeline
   ```

2. Configure o ambiente:

   ```bash
   make setup-env
   ```

3. Inicie os containers Docker:

   ```bash
   make up
   ```

4. Instale as dependências:
   ```bash
   make composer-install
   ```

### Comandos Make Disponíveis

- `make up`: Inicia todos os serviços em segundo plano
- `make down`: Para e remove todos os containers
- `make build`: Constrói as imagens Docker
- `make shell`: Acessa o shell do container PHP
- `make test`: Executa os testes
- `make coverage`: Executa a cobertura de testes com formatação visual
- `make cs-fix`: Executa o PHP CS Fixer para corrigir o estilo do código
- `make quality`: Executa todos os comandos de qualidade (cs-check, test, security-check)

Para uma lista completa de comandos disponíveis, execute:

```bash
make help
```

## Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## Suporte e Comunidade

- **Documentação**: [https://kariricode.org/docs/processor-pipeline](https://kariricode.org/docs/processor-pipeline)
- **Rastreador de Problemas**: [GitHub Issues](https://github.com/KaririCode-Framework/kariricode-processor-pipeline/issues)
- **Comunidade**: [Comunidade KaririCode Club](https://kariricode.club)

## Agradecimentos

- A equipe do KaririCode Framework e contribuidores.
- Inspirado por padrões de pipeline e cadeias de processamento em arquitetura de software.

---

Construído com ❤️ pela equipe KaririCode. Capacitando desenvolvedores a criar aplicações PHP mais robustas e flexíveis.
