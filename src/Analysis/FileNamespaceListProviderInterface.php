<?php

namespace Serenata\Analysis;

use RuntimeException;

use Serenata\Indexing\Structures;

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
     * @return array<string,array<string,mixed>> mapping namespace ID's to namespaces.
     */
    public function getAllForFile(Structures\File $file): array;
}
