<?php

namespace PhpIntegrator\Analysis\Visiting;

use DomainException;

/**
 * Describes a type's possibility.
 */
class TypePossibility
{
    /**
     * Indicates a type is guaranteed.
     *
     * @example In "if ($a === null)", the type of $a is guaranteed to be "null".
     *
     * @var int
     */
    const TYPE_GUARANTEED = 1;

    /**
     * Indicates a type is possible, but it is unsure if it effectively applies.
     *
     * Note the distinction with guaranteed types in cases such as:
     *
     *     if ($a instanceof A) {
     *         if ($a instanceof B) {
     *             ...
     *         }
     *     }
     *
     * In this case you could say that the type is possibly A or B, but this is not what is meant by "possible"; here
     * both A and B are guaranteed as you can say with absolute 100% certainty that $a is of both types. "Possible"
     * is weaker and merely states "based on analysis we can say that $a could be any of these types, but we can't know
     * for sure which one it exactly is".
     *
     * @example In "if (!$a)", the type of $a is possibly "null", "int" (with value 0), "string" (empty value), ...
     *
     * @var int
     */
    const TYPE_POSSIBLE   = 2;

    /**
     * Indicates that a type is impossible.
     *
     * Note the distinction between "if ($a !== null)" and "if (!$a)". The former can say for sure that "null" is not
     * the type of "$a", but the latter can only state that $a is truthy, whilst it can still be an int (with value 0),
     * float (with value 0.0), an empty string, ...
     *
     * @example In "if ($a !== null)", the type of $a could never possibly be "null".
     *
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
