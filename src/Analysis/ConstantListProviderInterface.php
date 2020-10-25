<?php

namespace Serenata\Analysis;

/**
 * Retrieves a list of (global) constants.
 */
interface ConstantListProviderInterface
{
    /**
     * @return array<string,array<string,mixed>> mapping FQCN's to constants.
     */
    public function getAll(): array;
}
