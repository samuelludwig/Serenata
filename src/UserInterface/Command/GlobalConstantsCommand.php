<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Analysis\GlobalConstantsProvider;

/**
 * Command that shows a list of global constants.
 */
class GlobalConstantsCommand extends AbstractCommand
{
    /**
     * @var GlobalConstantsProvider
     */
    private $globalConstantsProvider;

    /**
     * @param GlobalConstantsProvider $globalConstantsProvider
     */
    public function __construct(GlobalConstantsProvider $globalConstantsProvider)
    {
        $this->globalConstantsProvider = $globalConstantsProvider;
    }

    /**
     * @inheritDoc
     */
    public function execute(ArrayAccess $arguments)
    {
        return $this->getGlobalConstants();
    }

    /**
     * @return array
     */
    public function getGlobalConstants(): array
    {
        return $this->globalConstantsProvider->getAll();
    }
}
