<?php

namespace Serenata\Indexing;

use DateTime;
use LogicException;

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

        if ($file === null || $file->getLastIndexedSourceHash() !== $requestedCodeHash) {
            $this->delegate->index($filePath, $code);

            try {
                $file = $this->storage->getFileByPath($filePath);
            } catch (FileNotFoundStorageException $e) {
                throw new LogicException("File {$filePath} is not in index, even though it was just indexed", 0, $e);
            }
        }

        // Even if we don't index, still update the hash. We're not trying to cancel the index, just to avoid costly
        // recomputation that has no effect.
        $file->setIndexedOn(new DateTime());
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
