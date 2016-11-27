<?php

namespace PhpIntegrator\Analysis\Visiting;

use OutOfBoundsException;

use PhpParser\Node;

/**
 * Holds information about an expression's type.
 */
class ExpressionTypeInfo
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
     * @see TypePossibility
     *
     * @var array
     */
    protected $typePossibilities = [];

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
     * @param string $type
     *
     * @throws OutOfBoundsException
     *
     * @return int
     */
    public function getPossibilityOfType($type)
    {
        if (!isset($this->typePossibilities[$type])) {
            throw new OutOfBoundsException('No such type found in the list of type possibilities');
        }

        return $this->typePossibilities[$type];
    }

    /**
     * @param string $type
     * @param int    $possibility
     *
     * @return static
     */
    public function setPossibilityOfType($type, $possibility)
    {
        $this->typePossibilities[$type] = $possibility;
        return $this;
    }

    /**
     * @param string $type
     *
     * @return static
     */
    public function removePossibilityOfType($type)
    {
        unset($this->typePossibilities[$type]);
        return $this;
    }

    /**
     * @return array
     */
    public function getTypePossibilities()
    {
        return $this->typePossibilities;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isTypeImpossible($type)
    {
        try {
            return $this->getPossibilityOfType($type) === TypePossibility::TYPE_IMPOSSIBLE;
        } catch (OutOfBoundsException $e) {
            return false;
        }
    }

    /**
     * @param array $typePossibilities
     *
     * @return static
     */
    public function setTypePossibilities(array $typePossibilities)
    {
        $this->typePossibilities = $typePossibilities;
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

    /**
     * Retrieves a list of applicable types based on type information.
     *
     * This takes a list of types (that e.g. a variable is supposed to have) and filters out any types that do not apply
     * based on the information from this object. Note that in some cases, the types returned do not necessarily contain
     * any of the types specified in the parameter.
     *
     * @param string[] $typeList
     *
     * @return string[]
     */
    public function getApplicableTypesFromTypes(array $typeList)
    {
        $guaranteedTypes = $this->getGuaranteedTypes();

        // Types guaranteed by conditionals take precendece over the best match types as if they did not apply, we
        // could never have ended up in the conditional in the first place. However, sometimes conditionals don't
        // know the exact type, but only know that the type must be one in a list of possible types (e.g. in an if
        // statement such as "if (!$a)" $a could still be an int, a float, a string, ...). In this case, the list
        // of conditionals is effectively narrowed down further by the type specified by a best match (i.e. the
        // best match types act as a whitelist for the conditional types).
        $types = array_intersect($guaranteedTypes, $typeList);

        if (empty($types)) {
            if (empty($guaranteedTypes)) {
                $types = $typeList;
            } else {
                // We got inside the if statement, so the type MUST be of one of the guaranteed types. However, if
                // an assignment said that $a is a string and the if statement checks if $a is a bool, in theory we
                // can never end up in the if statement at all as the condition will never pass. Still, for the
                // sake of deducing the type, we choose to return the types guaranteed by the if statement rather
                // than no types at all (as that isn't useful to anyone).
                $types = $guaranteedTypes;
            }
        }

        return $this->filterImpossibleTypesFromTypes($types);
    }

    /**
     * @return string[]
     */
    protected function getGuaranteedTypes()
    {
        $guaranteedTypes = [];

        foreach ($this->getTypePossibilities() as $type => $possibility) {
            if ($possibility === TypePossibility::TYPE_GUARANTEED) {
                $guaranteedTypes[] = $type;
            }
        }

        return $guaranteedTypes;
    }

    /**
     * @param string[] $types
     *
     * @return string[]
     */
    protected function filterImpossibleTypesFromTypes(array $types)
    {
        $filteredTypes = [];

        foreach ($types as $type) {
            if (!$this->isTypeImpossible($type)) {
                $filteredTypes[] = $type;
            }
        }

        return $filteredTypes;
    }
}
