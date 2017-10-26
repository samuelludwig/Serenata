<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Analysis\Typing\FileClasslikeListProviderInterface;

use PhpIntegrator\Common\Position;

use PhpIntegrator\Indexing\Structures;

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
     * @param FileClasslikeListProviderInterface $fileClasslikeListProvider
     */
    public function __construct(FileClasslikeListProviderInterface $fileClasslikeListProvider)
    {
        $this->fileClasslikeListProvider = $fileClasslikeListProvider;
    }

    /**
     * @param Position        $position
     * @param Structures\File $file
     *
     * @return string|null
     */
     public function determine(Position $position, Structures\File $file): ?string
     {
         $classesInFile = $this->fileClasslikeListProvider->getAllForFile($file);

         foreach ($classesInFile as $fqcn => $classInfo) {
             if ($position->getLine() >= $classInfo['startLine'] && $position->getLine() <= $classInfo['endLine']) {
                 return $fqcn;
             }
         }

         return null;
     }
}
