<?php

namespace PhpIntegrator\Analysis\Typing;

use RuntimeException;

/**
 * Interface for classes that can retrieve a structure list for a specific file.
 */
interface FileStructureListProviderInterface
{
    /**
     * @param string $filePath
     *
     * @throws RuntimeException
     *
     * @return array
     */
    public function getAllForFile(string $filePath): array;
}
