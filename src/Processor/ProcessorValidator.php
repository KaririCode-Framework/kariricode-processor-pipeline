<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Processor;

use KaririCode\Contract\Processor\Processor;
use KaririCode\Contract\Processor\ProcessorValidator as ProcessorValidatorContract;
use KaririCode\Contract\Processor\ValidatableProcessor;

class ProcessorValidator implements ProcessorValidatorContract
{
    public function validate(Processor $processor, string $processorName, array $messages): ?array
    {
        if ($processor instanceof ValidatableProcessor && !$processor->isValid()) {
            $errorKey = $processor->getErrorKey();

            return [
                'errorKey' => $errorKey,
                'message' => $messages[$processorName] ?? "Validation failed for $processorName",
            ];
        }

        return null;
    }
}
