<?php

namespace PhpIntegrator\Analysis\Typing;

use RuntimeException;

/**
 * Interface for classes that can retrieve a structure list, possibly for a specific file.
 */
interface FileStructureListProviderInterface
{
    /**
     * @throws RuntimeException
     *
     * @return array
     */
    public function getAll(): array;

    /**
     * @param string $filePath
     *
     * @throws RuntimeException
     *
     * @return array
     */
    public function getAllForFile(string $filePath): array;
}
