<?php

namespace Serenata\Analysis\Typing\Deduction;

use PhpParser\Node;

use Serenata\Analysis\MetadataProviderInterface;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\MethodCall} or a {@see Node\Expr\StaticCall} node based on
 * data supplied by meta files and delegates to another deducer if no such data is present.
 */
final class MethodCallNodeMetaTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var NodeTypeDeducerInterface
     */
    private $delegate;

    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @var MetadataProviderInterface
     */
    private $metadataProvider;

    /**
     * @param NodeTypeDeducerInterface  $delegate
     * @param NodeTypeDeducerInterface  $nodeTypeDeducer
     * @param MetadataProviderInterface $metadataProvider
     */
    public function __construct(
        NodeTypeDeducerInterface $delegate,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        MetadataProviderInterface $metadataProvider
    ) {
        $this->delegate = $delegate;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): array
    {
        if (!$context->getNode() instanceof Node\Expr\MethodCall &&
            !$context->getNode() instanceof Node\Expr\StaticCall
        ) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $objectNode = ($context->getNode() instanceof Node\Expr\MethodCall) ?
            $context->getNode()->var :
            $context->getNode()->class;

        $methodName = $context->getNode()->name;

        if (!$methodName instanceof Node\Identifier) {
            return [];
        }

        $typesOfVar = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $objectNode,
            $context->getTextDocumentItem()
        ));

        $staticTypes = [];

        foreach ($typesOfVar as $type) {
            $staticTypes = array_merge(
                $staticTypes,
                $this->metadataProvider->getMetaStaticMethodTypesFor($type, $methodName)
            );
        }

        if (empty($staticTypes)) {
            return $this->delegate->deduce($context);
        }

        $types = [];

        foreach ($staticTypes as $staticType) {
            if (count($context->getNode()->args) <= $staticType->getArgumentIndex()) {
                continue;
            }

            $relevantArgumentNode = $context->getNode()->args[$staticType->getArgumentIndex()];

            if (get_class($relevantArgumentNode->value) !== $staticType->getValueNodeType()) {
                continue;
            }

            if ($relevantArgumentNode->value instanceof Node\Scalar\String_ &&
                $relevantArgumentNode->value->value === $staticType->getValue()
            ) {
                $types[] = $staticType->getReturnType();
            }
        }

        return $types;
    }
}
