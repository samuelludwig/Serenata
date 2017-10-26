<?php

namespace PhpIntegrator\Analysis;

use RuntimeException;

/**
 * Interface for classes that retrieve a list of namespaces.
 */
interface NamespaceListProviderInterface
{
    /**
     * @throws RuntimeException
     *
     * @return array array<string, array> mapping namespace ID's to namespaces.
     */
    public function getAll(): array;
}
