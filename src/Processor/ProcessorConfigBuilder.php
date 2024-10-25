<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Processor;

use KaririCode\Contract\Processor\Attribute\ProcessableAttribute;
use KaririCode\ProcessorPipeline\Contract\ProcessorConfigBuilder as ProcessorConfigBuilderContract;

readonly class ProcessorConfigBuilder implements ProcessorConfigBuilderContract
{
    public function build(ProcessableAttribute $attribute): array
    {
        $processors = $attribute->getProcessors();
        $processorsConfig = [];

        foreach ($processors as $key => $processor) {
            if ($this->isSimpleProcessor($processor)) {
                $processorsConfig[$processor] = $this->getDefaultProcessorConfig();
            } elseif ($this->isConfigurableProcessor($processor)) {
                $processorName = $this->determineProcessorName($key, $processor);
                $processorsConfig[$processorName] = $this->getProcessorConfig($processor);
            }
        }

        return $processorsConfig;
    }

    private function isSimpleProcessor(mixed $processor): bool
    {
        return is_string($processor);
    }

    private function isConfigurableProcessor(mixed $processor): bool
    {
        return is_array($processor);
    }

    private function getDefaultProcessorConfig(): array
    {
        return [];
    }

    private function determineProcessorName(string|int $key, array $processor): string
    {
        $nameNormalizer = new ProcessorNameNormalizer();

        return $nameNormalizer->normalize($key, $processor);
    }

    private function getProcessorConfig(array $processor): array
    {
        return $processor;
    }
}
