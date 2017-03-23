<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Analysis\Typing\FileClassListProviderInterface;

use PhpIntegrator\Utility\Position;

/**
 * Determines in which class a position (offset) in a file is located.
 */
class FilePositionClasslikeDeterminer
{
    /**
     * @var FileClassListProviderInterface
     */
    private $fileClassListProvider;

    /**
     * @param FileClassListProviderInterface $fileClassListProvider
     */
    public function __construct(FileClassListProviderInterface $fileClassListProvider)
    {
        $this->fileClassListProvider = $fileClassListProvider;
    }

    /**
     * @param Position $position
     * @param string   $filePath
     *
     * @return string|null
     */
     public function determine(Position $position, string $filePath): ?string
     {
         $classesInFile = $this->fileClassListProvider->getAllForFile($filePath);

         foreach ($classesInFile as $fqcn => $classInfo) {
             if ($position->getLine() >= $classInfo['startLine'] && $position->getLine() <= $classInfo['endLine']) {
                 return $fqcn;
             }
         }

         return null;
     }
}
