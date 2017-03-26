<?php

namespace PhpIntegrator\Utility;

use PhpIntegrator\Utility\ImmutableSet;

/**
 * Represents a list of (parameter, property, constant) types.
 *
 * This is a value object and immutable.
 */
final class TypeList extends ImmutableSet
{
    /**
     * @param string[] ...$elements
     */
    public function __construct(string ...$elements)
    {
        parent::__construct(...$elements);
    }
}
