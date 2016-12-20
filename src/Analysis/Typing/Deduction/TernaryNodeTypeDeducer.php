<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\Ternary} node.
 */
class TernaryNodeTypeDeducer extends AbstractNodeTypeDeducer
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
        if (!$node instanceof Node\Expr\Ternary) {
            throw new UnexpectedValueException("Can't handle node of type " . get_class($node));
        }

        return $this->deduceTypesFromTernaryNode($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\Ternary $node
     * @param string|null      $file
     * @param string           $code
     * @param int              $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromTernaryNode(Node\Expr\Ternary $node, $file, $code, $offset)
    {
        $firstOperandTypes = [];
        $relevantNode = $node->if ?: $node->cond;
        $nodeTypeDeducer = $this->nodeTypeDeducerFactory->create($relevantNode);

        try {
            $firstOperandTypes = $nodeTypeDeducer->deduce(
                $relevantNode,
                $file,
                $code,
                $node->getAttribute('startFilePos')
            );
        } catch (UnexpectedValueException $e) {
            $firstOperandTypes = [];
        }

        $secondOperandTypes = [];
        $relevantNode = $node->else;
        $nodeTypeDeducer = $this->nodeTypeDeducerFactory->create($relevantNode);

        try {

            $secondOperandTypes = $nodeTypeDeducer->deduce(
                $relevantNode,
                $file,
                $code,
                $node->getAttribute('startFilePos')
            );
        } catch (UnexpectedValueException $e) {
            $secondOperandTypes = [];
        }

        return array_unique(array_merge($firstOperandTypes, $secondOperandTypes));
    }
}
