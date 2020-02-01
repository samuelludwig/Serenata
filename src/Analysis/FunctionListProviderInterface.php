<?php

namespace Serenata\Analysis;

use RuntimeException;

/**
 * Retrieves a list of (global) functions.
 */
interface FunctionListProviderInterface
{
    /**
     * @throws RuntimeException
     *
     * @return array<string,array<string, array>> mapping FQCN's to functions.
     */
    public function getAll(): array;
}
