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
     * @return array[]
     */
    public function getAll(): array;
}
