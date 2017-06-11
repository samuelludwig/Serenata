<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Analysis\Typing\FileStructureListProviderInterface;

use PhpIntegrator\Common\Position;

/**
 * Determines in which class a position (offset) in a file is located.
 */
class FilePositionClasslikeDeterminer
{
    /**
     * @var FileStructureListProviderInterface
     */
    private $fileStructureListProvider;

    /**
     * @param FileStructureListProviderInterface $fileStructureListProvider
     */
    public function __construct(FileStructureListProviderInterface $fileStructureListProvider)
    {
        $this->fileStructureListProvider = $fileStructureListProvider;
    }

    /**
     * @param Position $position
     * @param string   $filePath
     *
     * @return string|null
     */
     public function determine(Position $position, string $filePath): ?string
     {
         $classesInFile = $this->fileStructureListProvider->getAllForFile($filePath);

         foreach ($classesInFile as $fqcn => $classInfo) {
             if ($position->getLine() >= $classInfo['startLine'] && $position->getLine() <= $classInfo['endLine']) {
                 return $fqcn;
             }
         }

         return null;
     }
}
