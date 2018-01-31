<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PhpIntegrator\Indexing\Structures;

use PhpIntegrator\Utility\NodeHelpers;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Stmt\ClassLike} node.
 */
final class ClassLikeNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @inheritDoc
     */
    public function deduce(Node $node, Structures\File $file, string $code, int $offset): array
    {
        if (!$node instanceof Node\Stmt\ClassLike) {
            throw new UnexpectedValueException("Can't handle node of type " . get_class($node));
        }

        return $this->deduceTypesFromClassLikeNode($node, $file);
    }

    /**
     * @param Node\Stmt\ClassLike $node
     * @param Structures\File     $file
     *
     * @return string[]
     */
    private function deduceTypesFromClassLikeNode(Node\Stmt\ClassLike $node, Structures\File $file): array
    {
        if ($node->name === null) {
            return [NodeHelpers::getFqcnForAnonymousClassNode($node, $file->getPath())];
        }

        return [(string) $node->name];
    }
}
