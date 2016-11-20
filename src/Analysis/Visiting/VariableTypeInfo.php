<?php

namespace PhpIntegrator\Analysis\Visiting;

use PhpParser\Node;

/**
 * Holds information about a variable's type.
 */
class VariableTypeInfo
{
    /**
     * The node that best describes the item.
     *
     * This is usually a node such as an assignment expression or a method parameter that the item is involved in. For
     * example, if a node "$a = $b" is encountered, followed by a node "$a = $c", then the best match for the type of
     * $a would be the node that describes "$a = $c".
     *
     * @var Node|null
     */
    protected $bestMatch;

    /**
     * Describes an override of the type.
     *
     * Type overrides are usually present in inline docblocks that override the type. This field would be filled with
     * that type (override) that is set for this item. For example, "/** @var Foo $a * /" describes the type of $a to
     * be overridden to be "Foo".
     *
     * @var string|null
     */
    protected $bestTypeOverrideMatch;

    /**
     * The line to type override was encountered at.
     *
     * @var int|null
     */
    protected $bestTypeOverrideMatchLine;

    /**
     * A map of conditional types that the item may have.
     *
     * Whenever the item is encountered inside a conditional (if statement, ternary expression, ...), there are certain
     * assumptions that can be made about the type. For example, a check such as "if ($a === null)" clearly states that
     * if the condition passes, the type of the expression must be null. At that point it doesn't matter if the type
     * could previously be "Foo|null", as the conditional has now effectively limited the possible types.
     *
     * Conditional types can be:
     *   - Guaranteed - For example, in "if ($a === null)", the type of $a is guaranteed to be "null".
     *   - Possible   - For example, in "if (!$a)", the type of $a is possibly "null", "int" (with value 0), "string"
     *                  (empty value), ...
     *   - Impossible - For example, in "if ($a !== null)", the type of $a could never possibly be "null".
     *
     * @var array
     */
    protected $conditionalTypes = [];

    /**
     * @return Node|null
     */
    public function getBestMatch()
    {
        return $this->bestMatch;
    }

    /**
     * @param Node|null $bestMatch
     *
     * @return static
     */
    public function setBestMatch(Node $bestMatch = null)
    {
        $this->bestMatch = $bestMatch;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBestTypeOverrideMatch()
    {
        return $this->bestTypeOverrideMatch;
    }

    /**
     * @param string|null $bestTypeOverrideMatch
     *
     * @return static
     */
    public function setBestTypeOverrideMatch($bestTypeOverrideMatch)
    {
        $this->bestTypeOverrideMatch = $bestTypeOverrideMatch;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getBestTypeOverrideMatchLine()
    {
        return $this->bestTypeOverrideMatchLine;
    }

    /**
     * @param int|null $bestTypeOverrideMatchLine
     *
     * @return static
     */
    public function setBestTypeOverrideMatchLine($bestTypeOverrideMatchLine)
    {
        $this->bestTypeOverrideMatchLine = $bestTypeOverrideMatchLine;
        return $this;
    }

    /**
     * @return array
     */
    public function getConditionalTypes()
    {
        return $this->conditionalTypes;
    }

    /**
     * @param array $conditionalTypes
     *
     * @return static
     */
    public function setConditionalTypes(array $conditionalTypes)
    {
        $this->conditionalTypes = $conditionalTypes;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasBestMatch()
    {
        return ($this->getBestMatch() !== null);
    }

    /**
     * @return bool
     */
    public function hasBestTypeOverrideMatch()
    {
        return ($this->getBestTypeOverrideMatch() !== null);
    }
}
