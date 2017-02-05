<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Analysis\ClassListProvider;

/**
 * Command that shows a list of available classes, interfaces and traits.
 */
class ClassListCommand extends AbstractCommand
{
    /**
     * @var ClassListProvider
     */
    protected $classListProvider;

    /**
     * @param ClassListProvider $classListProvider
     */
    public function __construct(ClassListProvider $classListProvider)
    {
        $this->classListProvider = $classListProvider;
    }

    /**
     * @inheritDoc
     */
    public function execute(ArrayAccess $arguments)
    {
        $file = isset($arguments['file']) ? $arguments['file'] : null;

        if ($file !== null) {
            return $this->classListProvider->getClassListForFile($file);
        }

        return $this->classListProvider->getAll();
    }
}
