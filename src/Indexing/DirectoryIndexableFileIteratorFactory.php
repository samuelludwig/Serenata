<?php

namespace Serenata\Indexing;

/**
 * Creates instances of {@see DirectoryIndexableFileIterator}.
 */
class DirectoryIndexableFileIteratorFactory
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @param StorageInterface $storage
     * @param PathNormalizer   $pathNormalizer
     */
    public function __construct(StorageInterface $storage, PathNormalizer $pathNormalizer)
    {
        $this->storage = $storage;
    }

    /**
     * @param string   $uri
     * @param string[] $extensionsToIndex
     * @param string[] $globsToExclude
     *
     * @return DirectoryIndexableFileIterator
     */
    public function create(
        string $uri,
        array $extensionsToIndex,
        array $globsToExclude = []
    ): DirectoryIndexableFileIterator {
        return new DirectoryIndexableFileIterator(
            $this->storage->getFiles(),
            $uri,
            $extensionsToIndex,
            $globsToExclude
        );
    }
}
