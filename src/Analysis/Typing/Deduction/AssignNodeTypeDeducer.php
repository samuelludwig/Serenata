<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

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
    public function deduce(Node $node, $file, $code, $offset)
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
        try {
            $nodeTypeDeducer = $this->nodeTypeDeducerFactory->create($node->expr);

            return $nodeTypeDeducer->deduce($node->expr, $file, $code, $node->getAttribute('startFilePos'));
        } catch (UnexpectedValueException $e) {
            return [];
        }
    }
}
