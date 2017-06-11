<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Analysis\Typing\FileStructureListProviderInterface;

/**
 * Command that shows a list of available classes, interfaces and traits.
 */
class ClassListCommand extends AbstractCommand
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
     * @inheritDoc
     */
    public function execute(ArrayAccess $arguments)
    {
        $file = isset($arguments['file']) ? $arguments['file'] : null;

        if ($file !== null) {
            return $this->fileStructureListProvider->getAllForFile($file);
        }

        return $this->fileStructureListProvider->getAll();
    }
}
