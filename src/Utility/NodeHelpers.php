<?php

namespace Serenata\Utility;

use LogicException;

use PhpParser\Node;

/**
 * Contains static helper functions for working with nodes.
 */
final class NodeHelpers
{
    /**
     * @param Node\Stmt\Class_ $node
     * @param string           $filePath
     *
     * @return string
     */
    public static function getFqcnForAnonymousClassNode(Node\Stmt\Class_ $node, string $filePath): string
    {
        $startFilePos = $node->getAttribute('startFilePos');

        if ($startFilePos === null) {
            throw new LogicException('Anonymous class node must have "startFilePos" attribute set');
        }

        return '\\' . sprintf('anonymous_%s_%s', md5($filePath), $startFilePos);
    }

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
     * Indicates if the specified name node is using a name that is reserved.
     *
     * php-parser identifies some some reserved keywords as name nodes in specific situations. For example, the code
     * "parent::foo()" will consist of a StaticCall node that has a Name node containing the name "parent".
     *
     * @param Node\Name $name
     *
     * @return bool
     */
    public static function isReservedNameNode(Node\Name $name): bool
    {
        $reservedNames = [
            'parent',
            'self',
            'static',
        ];

        return in_array($name->toString(), $reservedNames, true);
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
        /** @var Node|false $parent */
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

            /** @var Node|false $parent */
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
