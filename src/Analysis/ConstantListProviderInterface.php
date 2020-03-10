<?php

namespace Serenata\Analysis;

use RuntimeException;

/**
 * Retrieves a list of (global) constants.
 */
interface ConstantListProviderInterface
{
    /**
     * @throws RuntimeException
     *
     * @return array<string,array<string,mixed>> mapping FQCN's to constants.
     */
    public function getAll(): array;
}
