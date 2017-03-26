<?php

namespace PhpIntegrator\Analysis\Typing;

use PhpIntegrator\Utility\ImmutableSet;

/**
 * Represents a list of docblock types.
 */
final class DocblockTypeList extends ImmutableSet
{
    /**
     * @param string[] ...$elements
     */
    public function __construct(string ...$elements)
    {
        parent::__construct(...$elements);
    }
}
