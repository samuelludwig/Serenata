<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node} object.
 *
 * This is a thin type deducer that can deduce the type of any node by delegating the type deduction to a more
 * appropriate deducer returned by the configured factory.
 */
class NodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var NodeTypeDeducerFactoryInterface
     */
    protected $nodeTypeDeducerFactory;

    /**
     * @param NodeTypeDeducerFactoryInterface $nodeTypeDeducerFactory
     */
    public function __construct(NodeTypeDeducerFactoryInterface $nodeTypeDeducerFactory)
    {
        $this->nodeTypeDeducerFactory = $nodeTypeDeducerFactory;
    }

    /**
     * @inheritDoc
     */
    public function deduce(Node $node, $file, $code, $offset)
    {
        $nodeTypeDeducer = null;

        try {
            $nodeTypeDeducer = $this->nodeTypeDeducerFactory->create($node);
        } catch (NoTypeDeducerFoundException $e) {
            return [];
        }

        return $nodeTypeDeducer->deduce($node, $file, $code, $offset);
    }
}
