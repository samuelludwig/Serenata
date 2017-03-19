<?php

namespace PhpIntegrator\Utility;

use LogicException;

use PhpParser\Node;

/**
 * Contains static helper functions for working with nodes.
 */
class NodeHelpers
{
    /**
     * Takes a class name and turns it into its string representation.
     *
     * @param Node\Name $name
     *
     * @return string
     */
    public static function fetchClassName(Node\Name $name): string
    {
        $newName = (string) $name;

        if ($name->isFullyQualified() && $newName[0] !== '\\') {
            $newName = '\\' . $newName;
        }

        return $newName;
    }

    /**
     * Finds the first ancestor node that has any of the specified types.
     *
     * @param Node     $node
     * @param string[] ...$types
     *
     * @return Node|null
     */
    public static function findAncestorOfAnyType(Node $node, string ...$types): ?Node
    {
        $parent = $node->getAttribute('parent', false);

        if ($parent === false) {
            throw new LogicException("Can't find ancestor without node parent data being attached");
        }

        while ($parent) {
            foreach ($types as $type) {
                if (is_a($parent, $type, false)) {
                    return $parent;
                }
            }

            $parent = $parent->getAttribute('parent');
        }

        return null;
    }

    /**
     * Moves from the specified node upwards through the hierarchy, looking for a node with any of the specified types.
     * Includes the node itself in the search.
     *
     * Same as {@see findAncestorOfType}, but includes the passed node in the saerch.
     *
     * @param Node     $node
     * @param string[] ...$types
     *
     * @return Node|null
     */
    public static function findNodeOfAnyTypeInNodePath(Node $node, string ...$types): ?Node
    {
        foreach ($types as $type) {
            if (is_a($node, $type, false)) {
                return $node;
            }
        }

        return static::findAncestorOfAnyType($node, ...$types);
    }
}
