<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Analysis\StructureListProviderInterface;

use PhpIntegrator\Analysis\Typing\FileStructureListProviderInterface;

/**
 * Command that shows a list of available classes, interfaces and traits.
 */
class ClassListCommand extends AbstractCommand
{
    /**
     * @var StructureListProviderInterface
     */
    private $structureListProvider;

    /**
     * @var FileStructureListProviderInterface
     */
    private $fileStructureListProvider;

    /**
     * @param StructureListProviderInterface     $structureListProvider
     * @param FileStructureListProviderInterface $fileStructureListProvider
     */
    public function __construct(
        StructureListProviderInterface $structureListProvider,
        FileStructureListProviderInterface $fileStructureListProvider
    ) {
        $this->structureListProvider = $structureListProvider;
        $this->fileStructureListProvider = $fileStructureListProvider;
    }

    /**
     * @inheritDoc
     */
    public function execute(ArrayAccess $arguments)
    {
        $file = isset($arguments['file']) ? $arguments['file'] : null;

        if ($file !== null) {
            return $this->fileStructureListProvider->getAllForFile($file);
        }

        return $this->structureListProvider->getAll();
    }
}
