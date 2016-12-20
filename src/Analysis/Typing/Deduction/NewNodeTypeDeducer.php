<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use LogicException;
use UnexpectedValueException;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\New_} node.
 */
class NewNodeTypeDeducer extends AbstractNodeTypeDeducer
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
        if (!$node instanceof Node\Expr\New_) {
            throw new UnexpectedValueException("Can't handle node of type " . get_class($node));
        }

        return $this->deduceTypesFromNewNode($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\New_ $node
     * @param string|null    $file
     * @param string         $code
     * @param int            $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromNewNode(Node\Expr\New_ $node, $file, $code, $offset)
    {
        try {
            $nodeTypeDeducer = $this->nodeTypeDeducerFactory->create($node->class);

            return $nodeTypeDeducer->deduceTypesFromNode($node->class, $file, $code, $offset);
        } catch (UnexpectedValueException $e) {
            return [];
        }

        throw new LogicException('Should never be reached');
    }
}
