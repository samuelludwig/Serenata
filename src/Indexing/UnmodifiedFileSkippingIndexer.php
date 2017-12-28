<?php

namespace PhpIntegrator\Indexing;

use AssertionError;

/**
 * Decorator for {@see FileIndexerInterface} objects that skips indexing entirely if the source was not modified.
 */
final class UnmodifiedFileSkippingIndexer implements FileIndexerInterface
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
            $file = null;
        }

        $requestedCodeHash = $this->hashSource($code);

        if ($file !== null && $file->getLastIndexedSourceHash() === $requestedCodeHash) {
            return; // We already indexed the same code, skip it.
        }

        $this->delegate->index($filePath, $code);

        try {
            $file = $this->storage->getFileByPath($filePath);
        } catch (FileNotFoundStorageException $e) {
            throw new AssertionError("File {$filePath} is not in index, even though it was just indexed", 0, $e);
        }

        $file->setLastIndexedSourceHash($requestedCodeHash);

        $this->storage->beginTransaction();
        $this->storage->persist($file);
        $this->storage->commitTransaction();
    }

    /**
     * @param string $source
     *
     * @return string
     */
    private function hashSource(string $source): string
    {
        return md5($source);
    }
}
