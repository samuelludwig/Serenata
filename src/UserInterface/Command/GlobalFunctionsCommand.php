<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Analysis\functionListProviderInterface;

/**
 * Command that shows a list of global functions.
 */
class GlobalFunctionsCommand
{
    /**
     * @var functionListProviderInterface
     */
    private $functionListProvider;

    /**
     * @param functionListProviderInterface $functionListProvider
     */
    public function __construct(functionListProviderInterface $functionListProvider)
    {
        $this->functionListProvider = $functionListProvider;
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
         return $this->functionListProvider->getAll();
     }
}
