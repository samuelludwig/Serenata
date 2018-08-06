<?php

namespace Serenata\Analysis;

use Serenata\Analysis\Typing\FileClasslikeListProviderInterface;

use Serenata\Common\Position;

use Serenata\Indexing\Structures;
use Serenata\Indexing\StorageInterface;

use Serenata\Utility\TextDocumentItem;

/**
 * Determines in which class a position (offset) in a file is located.
 */
class FilePositionClasslikeDeterminer
{
    /**
     * @var FileClasslikeListProviderInterface
     */
    private $fileClasslikeListProvider;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @param FileClasslikeListProviderInterface $fileClasslikeListProvider
     * @param StorageInterface                   $storage
     */
    public function __construct(
        FileClasslikeListProviderInterface $fileClasslikeListProvider,
        StorageInterface $storage
    ) {
        $this->fileClasslikeListProvider = $fileClasslikeListProvider;
        $this->storage = $storage;
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return string|null
     */
    public function determine(TextDocumentItem $textDocumentItem, Position $position): ?string
    {
        $file = $this->storage->getFileByPath($textDocumentItem->getUri());

        $classesInFile = $this->fileClasslikeListProvider->getAllForFile($file);

        foreach ($classesInFile as $fqcn => $classInfo) {
            if ($position->getLine() >= $classInfo['startLine'] && $position->getLine() <= $classInfo['endLine']) {
                return $fqcn;
            }
        }

        return null;
    }
}
