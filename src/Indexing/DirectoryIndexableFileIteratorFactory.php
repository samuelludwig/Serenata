<?php

namespace Serenata\Indexing;

use Serenata\Indexing\Structures;

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
     * @param string[] $paths
     * @param string[] $extensionsToIndex
     * @param string[] $globsToExclude
     *
     * @return DirectoryIndexableFileIterator
     */
    public function create(
        array $paths,
        array $extensionsToIndex,
        array $globsToExclude = []
    ): DirectoryIndexableFileIterator {
        return new DirectoryIndexableFileIterator(
            $this->storage->getFiles(),
            $paths,
            $extensionsToIndex,
            $globsToExclude
        );
    }
}
