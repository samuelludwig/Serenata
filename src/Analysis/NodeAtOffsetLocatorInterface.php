<?php

namespace PhpIntegrator\Analysis;

/**
 * Interface for classes that locate the node at the specified offset in code.
 */
interface NodeAtOffsetLocatorInterface
{
    /**
     * @param string $code
     * @param int    $position
     *
     * @return NodeAtOffsetLocatorResult
     */
    public function locate(string $code, int $position): NodeAtOffsetLocatorResult;
}
