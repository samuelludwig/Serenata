<?php

namespace PhpIntegrator\Analysis;

/**
 * Inerface for classes that can check if a global function exists.
 */
interface GlobalFunctionExistenceCheckerInterface
{
    /**
     * @param string $fqcn
     *
     * @return bool
     */
    public function doesGlobalFunctionExist(string $fqcn): bool;
}
