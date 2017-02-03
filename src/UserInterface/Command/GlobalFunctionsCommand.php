<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Analysis\GlobalFunctionsProvider;

/**
 * Command that shows a list of global functions.
 */
class GlobalFunctionsCommand
{
    /**
     * @var GlobalFunctionsProvider
     */
    protected $globalFunctionsProvider;

    /**
     * @param GlobalFunctionsProvider $globalFunctionsProvider
     */
    public function __construct(GlobalFunctionsProvider $globalFunctionsProvider)
    {
        $this->globalFunctionsProvider = $globalFunctionsProvider;
    }

    /**
     * @return array
     */
     public function execute(ArrayAccess $arguments)
     {
         return $this->getGlobalFunctions();
     }

     /**
      * @return array
      */
     public function getGlobalFunctions(): array
     {
         return $this->globalFunctionsProvider->getAll();
     }
}
