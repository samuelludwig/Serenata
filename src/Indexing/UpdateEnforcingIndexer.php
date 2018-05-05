<?php

namespace Serenata\Indexing;

/**
 * Decorator for {@see FileIndexerInterface} objects that only updates indexed files and fails on files that aren't in
 * the index yet.
 */
final class UpdateEnforcingIndexer implements FileIndexerInterface
{
    /**
     * @var FileIndexerInterface
     */
    private $delegate;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @param FileIndexerInterface $delegate
     * @param StorageInterface     $storage
     */
    public function __construct(FileIndexerInterface $delegate, StorageInterface $storage)
    {
        $this->delegate = $delegate;
        $this->storage = $storage;
    }

    /**
     * @inheritDoc
     */
    public function index(string $filePath, string $code): void
    {
        $file = null;

        try {
            $file = $this->storage->getFileByPath($filePath);
        } catch (FileNotFoundStorageException $e) {
            throw new IndexingFailedException('Skipping creation of new file during indexing', 0, $e);
        }

        $this->delegate->index($filePath, $code);
    }
}
