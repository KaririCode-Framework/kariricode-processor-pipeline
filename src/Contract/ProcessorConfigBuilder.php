<?php

declare(strict_types=1);

namespace KaririCode\ProcessorPipeline\Contract;

use KaririCode\Contract\Processor\Attribute\ProcessableAttribute;

interface ProcessorConfigBuilder
{
    /**
     * Constrói a configuração dos processadores a partir de um atributo processável.
     *
     * @param ProcessableAttribute $attribute o atributo que fornece os processadores
     *
     * @return array a configuração dos processadores
     */
    public function build(ProcessableAttribute $attribute): array;
}
