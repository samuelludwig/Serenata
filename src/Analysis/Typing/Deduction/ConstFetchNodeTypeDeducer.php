<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

use PhpParser\Node;

use Serenata\Common\FilePosition;

use Serenata\Indexing\Structures;
use Serenata\Indexing\ManagerRegistry;

use Serenata\NameQualificationUtilities\StructureAwareNameResolverFactoryInterface;

use Serenata\Parsing\SpecialDocblockTypeIdentifierLiteral;

use Serenata\Utility\NodeHelpers;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\ConstFetch} node.
 */
final class ConstFetchNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var StructureAwareNameResolverFactoryInterface
     */
    private $structureAwareNameResolverFactory;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory
     * @param ManagerRegistry                            $managerRegistry
     */
    public function __construct(
        StructureAwareNameResolverFactoryInterface $structureAwareNameResolverFactory,
        ManagerRegistry $managerRegistry
    ) {
        $this->structureAwareNameResolverFactory = $structureAwareNameResolverFactory;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): TypeNode
    {
        if (!$context->getNode() instanceof Node\Expr\ConstFetch) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $name = NodeHelpers::fetchClassName($context->getNode()->name);

        if ($name === 'null') {
            return new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::NULL_);
        } elseif ($name === 'true' || $name === 'false') {
            return new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::BOOL_);
        }

        $filePosition = new FilePosition(
            $context->getTextDocumentItem()->getUri(),
            $context->getPosition()
        );

        $fqsen = $this->structureAwareNameResolverFactory->create($filePosition)->resolve($name, $filePosition);

        /** @var Structures\ConstantLike|null $globalConstant */
        $globalConstant = $this->managerRegistry->getRepository(Structures\Constant::class)->findOneBy([
            'fqcn' => $fqsen,
        ]);

        if ($globalConstant === null) {
            return new IdentifierTypeNode(SpecialDocblockTypeIdentifierLiteral::MIXED_);
        }

        return $globalConstant->getType();
    }
}
