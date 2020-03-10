<?php

namespace Serenata\Analysis;

use RuntimeException;

/**
 * Interface for classes that retrieve a list of namespaces.
 */
interface NamespaceListProviderInterface
{
    /**
     * @throws RuntimeException
     *
     * @return array<string,array<string,mixed>> mapping namespace ID's to namespaces.
     */
    public function getAll(): array;
}
