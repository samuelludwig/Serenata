<?php

namespace Serenata\Parsing;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * Transforms a tree of {@see TypeNode} objects into a tree of new objects by applying a transformation on them.
 */
interface DocblockTypeTransformerInterface
{
    /**
     * @param S             $docblockType
     * @param callable(S):T $transformer  Closure that should return a new instance of a {@see TypeNode}.
     *
     * @return T
     *
     * @template S of TypeNode
     * @template T of TypeNode
     */
    public function transform(TypeNode $docblockType, callable $transformer): TypeNode;
}
