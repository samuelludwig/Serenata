<?php

namespace PhpIntegrator\Analysis\Visiting;

use OutOfBoundsException;

use PhpParser\Node;

/**
 * Maps a variable to information about its type.
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
     * @throws OutOfBoundsException
     *
     * @return array
     */
    public function get($variable)
    {
        if (!isset($this->map[$variable])) {
            throw new OutOfBoundsException("The variable {$variable} was not found");
        }

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
        $this->map[$variable]['conditionalTypes'] = [];
        $this->map[$variable]['bestMatch'] = $bestMatch;
    }

    /**
     * @param string $variable
     * @param string $type
     * @param int    $line
     */
    public function setBestTypeOverrideMatch($variable, $type, $line)
    {
        $this->map[$variable]['bestTypeOverrideMatch'] = $type;
        $this->map[$variable]['bestTypeOverrideMatchLine'] = $line;
    }

    /**
     * @param string $variable
     * @param array  $conditionalTypes
     */
    public function mergeConditionalTypes($variable, array $conditionalTypes)
    {
        $existingConditionalTypes = isset($this->map[$variable]['conditionalTypes']) ?
            $this->map[$variable]['conditionalTypes'] :
            [];

        $this->map[$variable]['conditionalTypes'] = array_merge($existingConditionalTypes, $conditionalTypes);
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
}
