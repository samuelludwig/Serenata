<?php

namespace PhpIntegrator\Analysis\Visiting;

/**
 * Describes a type's possibility.
 */
class TypePossibility
{
    /**
     * @var int
     */
    const TYPE_GUARANTEED = 1;

    /**
     * @var int
     */
    const TYPE_POSSIBLE   = 2;

    /**
     * @var int
     */
    const TYPE_IMPOSSIBLE = 4;
}
