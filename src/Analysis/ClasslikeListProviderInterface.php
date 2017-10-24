<?php

namespace PhpIntegrator\Analysis;

use RuntimeException;

/**
 * Retrieves a list of classlikes.
 */
interface ClasslikeListProviderInterface
{
    /**
     * @throws RuntimeException
     *
     * @return array[]
     */
    public function getAll(): array;
}
