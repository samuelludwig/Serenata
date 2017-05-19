<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Analysis\Conversion\FunctionConverter;

/**
 * Retrieves a list of (global) functions.
 */
interface FunctionListProviderInterface
{
     /**
      * @return array[]
      */
     public function getAll(): array;
}
