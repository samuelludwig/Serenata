<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use LogicException;
use UnexpectedValueException;

use PhpIntegrator\Parsing;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Parsing\Node\Keyword\Static_} node.
 */
class StaticNodeTypeDeducer extends AbstractNodeTypeDeducer
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
        if (!$node instanceof Parsing\Node\Keyword\Static_) {
            throw new UnexpectedValueException("Can't handle node of type " . get_class($node));
        }

        return $this->deduceTypesFromStatic($file, $code, $offset);
    }

    /**
     * @param string|null $file
     * @param string      $code
     * @param int         $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromStatic($file, $code, $offset)
    {
        try {
            $node = new Node\Name('static');

            $nodeTypeDeducer = $this->nodeTypeDeducerFactory->create($node);

            return $nodeTypeDeducer->deduce($node, $file, $code, $offset);
        } catch (UnexpectedValueException $e) {
            return [];
        }

        throw new LogicException('Should never be reached');
    }
}
