<?php

namespace Serenata\Analysis\Typing\Deduction;

/**
 * Type deducer that can deduce the type of a {@see Node} object by delegating it to another (configurable) object.
 */
final class ConfigurableDelegatingNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var NodeTypeDeducerInterface|null
     */
    private $nodeTypeDeducer;

    /**
     * @param NodeTypeDeducerInterface|null $nodeTypeDeducer
     */
    public function __construct(?NodeTypeDeducerInterface $nodeTypeDeducer = null)
    {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$this->nodeTypeDeducer) {
            throw new TypeDeductionException('No node type deducer to delegate to configured!');
        }

        return $this->nodeTypeDeducer->deduce($context);
    }

    /**
     * @return NodeTypeDeducerInterface|null
     */
    public function getNodeTypeDeducer(): ?NodeTypeDeducerInterface
    {
        return $this->nodeTypeDeducer;
    }

    /**
     * @param NodeTypeDeducerInterface|null $nodeTypeDeducer
     */
    public function setNodeTypeDeducer(?NodeTypeDeducerInterface $nodeTypeDeducer): void
    {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
    }
}
