<?php

namespace PhpIntegrator\Analysis\Visiting;

use PhpParser\Node;

/**
 * Holds information about a variable's type.
 */
class VariableTypeInfo
{
    /**
     * @var Node|null
     */
    protected $bestMatch;

    /**
     * @var string|null
     */
    protected $bestTypeOverrideMatch;

    /**
     * @var int|null
     */
    protected $bestTypeOverrideMatchLine;

    /**
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
