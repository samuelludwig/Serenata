<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PhpIntegrator\Parsing;

use PhpParser\Node;

/**
 * Type deducer that can deduce the type of a {@see Parsing\Node\Keyword\Parent_} node.
 */
class ParentNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var NodeTypeDeducerInterface
     */
    protected $nodeTypeDeducer;

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
    public function deduce(Node $node, ?string $file, string $code, int $offset): array
    {
        if (!$node instanceof Parsing\Node\Keyword\Parent_) {
            throw new UnexpectedValueException("Can't handle node of type " . get_class($node));
        }

        return $this->deduceTypesFromParent($file, $code, $offset);
    }

    /**
     * @param string|null $file
     * @param string      $code
     * @param int         $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromParent(?string $file, string $code, int $offset): array
    {
        $node = new Node\Name('parent');

        return $this->nodeTypeDeducer->deduce($node, $file, $code, $offset);
    }
}
