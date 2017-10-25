<?php

namespace PhpIntegrator\Analysis;

use RuntimeException;

/**
 * Retrieves a list of (global) constants.
 */
interface ConstantListProviderInterface
{
    /**
     * @throws RuntimeException
     *
     * @return array array<string, array> mapping FQCN's to constants.
     */
    public function getAll(): array;
}
