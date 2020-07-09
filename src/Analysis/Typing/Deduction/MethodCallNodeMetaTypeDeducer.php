<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use PhpParser\Node;

use Serenata\Analysis\MetadataProviderInterface;

use Serenata\Parsing\InvalidTypeNode;
use Serenata\Parsing\TypeNodeUnwrapper;
use Serenata\Parsing\ToplevelTypeExtractorInterface;
use Serenata\Parsing\SpecialDocblockTypeIdentifierLiteral;

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
     * @var ToplevelTypeExtractorInterface
     */
    private $toplevelTypeExtractor;

    /**
     * @param NodeTypeDeducerInterface       $delegate
     * @param NodeTypeDeducerInterface       $nodeTypeDeducer
     * @param MetadataProviderInterface      $metadataProvider
     * @param ToplevelTypeExtractorInterface $toplevelTypeExtractor
     */
    public function __construct(
        NodeTypeDeducerInterface $delegate,
        NodeTypeDeducerInterface $nodeTypeDeducer,
        MetadataProviderInterface $metadataProvider,
        ToplevelTypeExtractorInterface $toplevelTypeExtractor
    ) {
        $this->delegate = $delegate;
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->metadataProvider = $metadataProvider;
        $this->toplevelTypeExtractor = $toplevelTypeExtractor;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): TypeNode
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
            return new InvalidTypeNode();
        }

        $typesOfVar = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $objectNode,
            $context->getTextDocumentItem()
        ));

        $staticTypes = [];

        foreach ($this->toplevelTypeExtractor->extract($typesOfVar) as $type) {
            if ($type instanceof IdentifierTypeNode &&
                !in_array((string) $type, SpecialDocblockTypeIdentifierLiteral::getValues(), true)
            ) {
                $staticTypes = array_merge(
                    $staticTypes,
                    $this->metadataProvider->getMetaStaticMethodTypesFor((string) $type, $methodName)
                );
            }
        }

        if (count($staticTypes) === 0) {
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
                $types[] = new IdentifierTypeNode($staticType->getReturnType());
            }
        }

        return TypeNodeUnwrapper::unwrap(new UnionTypeNode($types));
    }
}
