<?php

namespace PhpIntegrator\Indexing;

/**
 * Interface for classes that return an appropriate file indexer for the specified file.
 */
interface FileIndexerFactoryInterface
{
    /**
     * @param string $filePath
     *
     * @return FileIndexerInterface
     */
    public function create(string $filePath): FileIndexerInterface;
}
