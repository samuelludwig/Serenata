<?php

namespace PhpIntegrator\Indexing;

use PhpIntegrator\Indexing\Indexer;

use PhpIntegrator\Sockets\JsonRpcRequest;

/**
 * Prunes removed files from the index.
 */
class IndexFilePruner
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @param StorageInterface $storage
     */
    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @return void
     */
    public function prune(): void
    {
        $this->storage->beginTransaction();

        foreach ($this->storage->getFiles() as $file) {
            if (!file_exists($file->getPath())) {
                $this->storage->delete($file);
            }
        }

        $this->storage->commitTransaction();
    }
}
