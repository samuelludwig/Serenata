<?php

namespace Serenata\Indexing;

use DateTime;
use LogicException;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use Serenata\Indexing\Structures\File;

use Serenata\Utility\TextDocumentItem;

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
    public function index(TextDocumentItem $textDocumentItem): ExtendedPromiseInterface
    {
        $file = null;

        try {
            $file = $this->storage->getFileByUri($textDocumentItem->getUri());
        } catch (FileNotFoundStorageException $e) {
            $file = null;
        }

        $requestedCodeHash = $this->hashSource($textDocumentItem->getText());

        if ($file === null || $file->getLastIndexedSourceHash() !== $requestedCodeHash) {
            $indexingPromise = $this->delegate->index($textDocumentItem);

            $promise = $indexingPromise->then(function ($value) use ($requestedCodeHash, $textDocumentItem) {
                try {
                    $file = $this->storage->getFileByUri($textDocumentItem->getUri());
                } catch (FileNotFoundStorageException $e) {
                    throw new LogicException(
                        "File {$textDocumentItem->getUri()} is not in index, even though it was just indexed",
                        0,
                        $e
                    );
                }

                $this->persistUpdatedHash($file, $requestedCodeHash);

                return $value;
            });

            assert($promise instanceof ExtendedPromiseInterface);

            return $promise;
        }

        $this->persistUpdatedHash($file, $requestedCodeHash);

        $deferred = new Deferred();
        $deferred->resolve(null);

        return $deferred->promise();
    }

    /**
     * @param File   $file
     * @param string $hash
     */
    private function persistUpdatedHash(File $file, string $hash): void
    {
        $file->setIndexedOn(new DateTime());
        $file->setLastIndexedSourceHash($hash);

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
