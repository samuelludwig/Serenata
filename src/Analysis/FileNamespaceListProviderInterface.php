<?php

namespace Serenata\Analysis;

use Serenata\Indexing\Structures;

/**
 * Interface for classes that retrieve a  list of namespaces for a file.
 */
interface FileNamespaceListProviderInterface
{
    /**
     * @param Structures\File $file
     *
     * @return array<string,array<string,mixed>> mapping namespace ID's to namespaces.
     */
    public function getAllForFile(Structures\File $file): array;
}
