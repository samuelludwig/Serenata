<?php

namespace PhpIntegrator\Analysis;

/**
 * Inerface for classes that can check if a global constant exists.
 */
interface GlobalConstantExistenceCheckerInterface
{
    /**
     * @param string $fqcn
     *
     * @return bool
     */
    public function doesGlobalConstantExist(string $fqcn): bool;
}
