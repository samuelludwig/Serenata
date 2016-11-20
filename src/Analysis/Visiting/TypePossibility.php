<?php

namespace PhpIntegrator\Analysis\Visiting;

use DomainException;

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

    /**
     * @param int $possibility
     *
     * @return int|null
     */
    public static function getReverse($possibility)
    {
        if ($possibility === self::TYPE_GUARANTEED) {
            return self::TYPE_IMPOSSIBLE;
        } elseif ($possibility === self::TYPE_IMPOSSIBLE) {
            return self::TYPE_GUARANTEED;
        } elseif ($possibility === self::TYPE_POSSIBLE) {
            return null; // Possible types are effectively negated and disappear.
        }

        throw new DomainException('Unknown type possibility specified');
    }
}
