<?php

namespace PhpIntegrator\Analysis\Visiting;

use PhpParser\Node;

/**
 * Keeps track of {@see VariableTypeInfo} objects for a set of variable( name)s.
 */
class VariableTypeInfoMap
{
    /**
     * @var array
     */
    protected $map = [];

    /**
     * @param string $variable
     *
     * @return VariableTypeInfo
     */
    public function get($variable)
    {
        $this->createIfNecessary($variable);

        return $this->map[$variable];
    }

    /**
     * @param string $variable
     *
     * @return bool
     */
    public function has($variable)
    {
        return isset($this->map[$variable]);
    }

    /**
     * @param string    $variable
     * @param Node|null $bestMatch
     */
    public function setBestMatch($variable, Node $bestMatch = null)
    {
        $this->createIfNecessary($variable);

        $this->get($variable)->setTypePossibilities([]);
        $this->get($variable)->setBestMatch($bestMatch);
    }

    /**
     * @param string $variable
     * @param string $type
     * @param int    $line
     */
    public function setBestTypeOverrideMatch($variable, $type, $line)
    {
        $this->createIfNecessary($variable);

        $this->get($variable)->setBestTypeOverrideMatch($type);
        $this->get($variable)->setBestTypeOverrideMatchLine($line);
    }

    /**
     * @param string[] $exclusionList
     */
    public function removeAllExcept(array $exclusionList)
    {
        $newMap = [];

        foreach ($this->map as $variable => $data) {
            if (in_array($variable, $exclusionList)) {
                $newMap[$variable] = $data;
            }
        }

        $this->map = $newMap;
    }

    /**
     * @return void
     */
    public function clear()
    {
        $this->map = [];
    }

    /**
     * @param string $variable
     */
    protected function createIfNecessary($variable)
    {
        if ($this->has($variable)) {
            return;
        }

        $this->create($variable);
    }

    /**
     * @param string $variable
     */
    protected function create($variable)
    {
        $this->map[$variable] = new VariableTypeInfo();
    }
}
