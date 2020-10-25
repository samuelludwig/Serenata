<?php

namespace Serenata\Analysis;

/**
 * Retrieves a list of (global) functions.
 */
interface FunctionListProviderInterface
{
    /**
     * @return array<string,array<string,array>> mapping FQCN's to functions.
     */
    public function getAll(): array;
}
