<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Tests\Stubs;

use KaririCode\Contract\Processor\ConfigurableProcessor;

final class StubConfigurableProcessor implements ConfigurableProcessor
{
    private int $minLength = 0;
    private int $maxLength = PHP_INT_MAX;

    /** @var array<string, mixed> Last configuration received */
    public array $lastConfig = [];

    public function configure(array $options): void
    {
        $this->lastConfig = $options;
        $this->minLength = $options['minLength'] ?? $this->minLength;
        $this->maxLength = $options['maxLength'] ?? $this->maxLength;
    }

    public function process(mixed $input): mixed
    {
        $length = mb_strlen((string) $input);

        if ($length < $this->minLength || $length > $this->maxLength) {
            throw new \InvalidArgumentException(
                "Length {$length} out of range [{$this->minLength}, {$this->maxLength}].",
            );
        }

        return $input;
    }
}
