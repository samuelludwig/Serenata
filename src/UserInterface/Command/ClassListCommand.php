<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Analysis\Typing\FileClassListProviderInterface;

/**
 * Command that shows a list of available classes, interfaces and traits.
 */
class ClassListCommand extends AbstractCommand
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
     * @inheritDoc
     */
    public function execute(ArrayAccess $arguments)
    {
        $file = isset($arguments['file']) ? $arguments['file'] : null;

        if ($file !== null) {
            return $this->fileClassListProvider->getAllForFile($file);
        }

        return $this->fileClassListProvider->getAll();
    }
}
