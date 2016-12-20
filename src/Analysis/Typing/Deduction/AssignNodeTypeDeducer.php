<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use LogicException;
use UnexpectedValueException;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\Assign} node.
 */
class AssignNodeTypeDeducer extends AbstractNodeTypeDeducer
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
    public function deduceTypesFromNode(Node $node, $file, $code, $offset)
    {
        if (!$node instanceof Node\Expr\Assign) {
            throw new UnexpectedValueException("Can't handle node of type " . get_class($node));
        }

        return $this->deduceTypesFromAssignNode($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\Assign $node
     * @param string|null      $file
     * @param string           $code
     * @param int              $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromAssignNode(Node\Expr\Assign $node, $file, $code, $offset)
    {
        if ($node->expr instanceof Node\Expr\Ternary) {
            $firstOperandTypes = [];
            $relevantNode = $node->expr->if ?: $node->expr->cond;
            $nodeTypeDeducer = $this->nodeTypeDeducerFactory->create($relevantNode);

            try {
                $firstOperandTypes = $nodeTypeDeducer->deduceTypesFromNode(
                    $relevantNode,
                    $file,
                    $code,
                    $node->getAttribute('startFilePos')
                );
            } catch (UnexpectedValueException $e) {
                $firstOperandTypes = [];
            }

            $secondOperandTypes = [];
            $relevantNode = $node->expr->else;
            $nodeTypeDeducer = $this->nodeTypeDeducerFactory->create($relevantNode);

            try {

                $secondOperandTypes = $nodeTypeDeducer->deduceTypesFromNode(
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

        try {
            $nodeTypeDeducer = $this->nodeTypeDeducerFactory->create($node->expr);

            return $nodeTypeDeducer->deduceTypesFromNode($node->expr, $file, $code, $node->getAttribute('startFilePos'));
        } catch (UnexpectedValueException $e) {
            return [];
        }
    }
}
