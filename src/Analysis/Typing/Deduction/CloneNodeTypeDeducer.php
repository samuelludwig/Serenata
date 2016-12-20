<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use LogicException;
use UnexpectedValueException;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\Clone_} node.
 */
class CloneNodeTypeDeducer extends AbstractNodeTypeDeducer
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
        if (!$node instanceof Node\Expr\Clone_) {
            throw new UnexpectedValueException("Can't handle node of type " . get_class($node));
        }

        return $this->deduceTypesFromCloneNode($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\Clone_ $node
     * @param string|null      $file
     * @param string           $code
     * @param int              $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromCloneNode(Node\Expr\Clone_ $node, $file, $code, $offset)
    {
        try {
            $nodeTypeDeducer = $this->nodeTypeDeducerFactory->create($node->expr);

            return $nodeTypeDeducer->deduce($node->expr, $file, $code, $offset);
        } catch (UnexpectedValueException $e) {
            return [];
        }

        throw new LogicException('Should never be reached');
    }
}
