<?php

namespace Serenata\Analysis;

/**
 * Interface for classes that retrieve a list of namespaces.
 */
interface NamespaceListProviderInterface
{
    /**
     * @return array<string,array<string,mixed>> mapping namespace ID's to namespaces.
     */
    public function getAll(): array;
}
