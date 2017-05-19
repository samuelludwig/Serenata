<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Analysis\Conversion\FunctionConverter;

/**
 * Retrieves a list of (global) constants.
 */
interface ConstantListProviderInterface
{
     /**
      * @return array[]
      */
     public function getAll(): array;
}
