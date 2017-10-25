<?php

namespace PhpIntegrator\Analysis;

use RuntimeException;

use PhpIntegrator\Indexing\Structures;

/**
 * Interface for classes that retrieve a  list of namespaces for a file.
 */
interface FileNamespaceListProviderInterface
{
    /**
     * @param Structures\File $file
     *
     * @throws RuntimeException
     *
     * @return array array<string, array> mapping namespace ID's to namespaces.
     */
    public function getAllForFile(Structures\File $file): array;
}
