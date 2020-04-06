<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

use PhpParser\Node;

use Serenata\Parsing\TypeNodeUnwrapper;

/**
 * Type deducer that can deduce the type of a {@see Node\Stmt\Catch_} node.
 */
final class CatchNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @param NodeTypeDeducerInterface $nodeTypeDeducer
     */
    public function __construct(NodeTypeDeducerInterface $nodeTypeDeducer)
    {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): TypeNode
    {
        if (!$context->getNode() instanceof Node\Stmt\Catch_) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $types = array_map(function (Node\Name $name) use ($context): TypeNode {
            return $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
                $name,
                $context->getTextDocumentItem()
            ));
        }, $context->getNode()->types);

        return TypeNodeUnwrapper::unwrap(new UnionTypeNode($types));
    }
}
