<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

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
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$context->getNode() instanceof Node\Stmt\Catch_) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $types = array_map(function (Node\Name $name) use ($context): array {
            return $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
                $name,
                $context->getTextDocumentItem()
            ));
        }, $context->getNode()->types);

        $types = array_reduce($types, function (array $subTypes, $carry): array {
            return array_merge($carry, $subTypes);
        }, []);

        return $types;
    }
}
