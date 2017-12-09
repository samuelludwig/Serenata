<?php

namespace PhpIntegrator\Indexing;

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
     * @var FileExistenceCheckerInterface
     */
    private $fileExistenceChecker;

    /**
     * @param StorageInterface              $storage
     * @param FileExistenceCheckerInterface $fileExistenceChecker
     */
    public function __construct(StorageInterface $storage, FileExistenceCheckerInterface $fileExistenceChecker)
    {
        $this->storage = $storage;
        $this->fileExistenceChecker = $fileExistenceChecker;
    }

    /**
     * @return void
     */
    public function prune(): void
    {
        $this->storage->beginTransaction();

        foreach ($this->storage->getFiles() as $file) {
            if (!$this->fileExistenceChecker->exists($file->getPath())) {
                $this->storage->delete($file);
            }
        }

        $this->storage->commitTransaction();
    }
}
