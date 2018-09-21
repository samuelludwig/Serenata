<?php

namespace Serenata\Indexing\Iterating;

use Iterator;
use SplFileInfo;
use FilterIterator;

use Serenata\Indexing\Structures;

/**
 * Filters out {@see \SplFileInfo} values that haven't been modified since a preconfigured time.
 */
final class ModificationTimeFilterIterator extends FilterIterator
{
    /**
     * @var Structures\File[]
     */
    private $fileModifiedMap;

    /**
     * @param Iterator          $iterator
     * @param Structures\File[] $filesInIndex
     */
    public function __construct(Iterator $iterator, array $filesInIndex)
    {
        parent::__construct($iterator);

        $this->fileModifiedMap = $this->createFileModifiedMap($filesInIndex);
    }

    /**
     * @inheritDoc
     */
    public function accept()
    {
        /** @var SplFileInfo $value */
        $value = $this->current();

        $filename = $value->getPathname();

        return
            !isset($this->fileModifiedMap[$filename]) ||
            $value->getMTime() > $this->fileModifiedMap[$filename]->getIndexedOn()->getTimestamp();
    }

    /**
     * @param Structures\File[] $filesInIndex
     *
     * @return Structures\File[]
     */
    private function createFileModifiedMap(array $filesInIndex): array
    {
        $map = [];

        foreach ($filesInIndex as $file) {
            $map[$file->getUri()] = $file;
        }

        return $map;
    }
}
