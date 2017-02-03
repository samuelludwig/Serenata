<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Analysis\Conversion\FunctionConverter;

use PhpIntegrator\Indexing\IndexDatabase;

/**
 * Retrieves a list of global functions.
 */
class GlobalFunctionsProvider
{
    /**
     * @var FunctionConverter
     */
    protected $functionConverter;

    /**
     * @var IndexDatabase
     */
    protected $indexDatabase;

    /**
     * @param FunctionConverter $functionConverter
     * @param IndexDatabase     $indexDatabase
     */
    public function __construct(FunctionConverter $functionConverter, IndexDatabase $indexDatabase)
    {
        $this->functionConverter = $functionConverter;
        $this->indexDatabase = $indexDatabase;
    }

     /**
      * @return array
      */
     public function getAll(): array
     {
         $result = [];

         foreach ($this->indexDatabase->getGlobalFunctions() as $function) {
             $result[$function['fqcn']] = $this->functionConverter->convert($function);
         }

         return $result;
     }
}
