<?php

namespace Serenata\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

use PhpParser\Node;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;
use Serenata\Analysis\ClasslikeBuildingFailedException;

use Serenata\Parsing\InvalidTypeNode;
use Serenata\Parsing\TypeNodeUnwrapper;
use Serenata\Parsing\DocblockTypeParserInterface;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\ClassConstFetch} node.
 */
final class ClassConstFetchNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classlikeInfoBuilder;

    /**
     * @var DocblockTypeParserInterface
     */
    private $docblockTypeParser;

    /**
     * @param NodeTypeDeducerInterface      $nodeTypeDeducer
     * @param ClasslikeInfoBuilderInterface $classlikeInfoBuilder
     * @param DocblockTypeParserInterface   $docblockTypeParser
     */
    public function __construct(
        NodeTypeDeducerInterface $nodeTypeDeducer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder,
        DocblockTypeParserInterface $docblockTypeParser
    ) {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
        $this->docblockTypeParser = $docblockTypeParser;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): TypeNode
    {
        if (!$context->getNode() instanceof Node\Expr\ClassConstFetch) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $typesOfVar = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
            $context->getNode()->class,
            $context->getTextDocumentItem()
        ));

        $types = [];

        foreach ($typesOfVar as $type) {
            $info = null;

            try {
                $info = $this->classlikeInfoBuilder->build($type);
            } catch (ClasslikeBuildingFailedException $e) {
                continue;
            }

            if (isset($info['constants'][$context->getNode()->name->name])) {
                $types[] = TypeNodeUnwrapper::unwrap(new UnionTypeNode(array_map(function (string $type): TypeNode {
                    return $this->docblockTypeParser->parse($type);
                }, $this->fetchResolvedTypesFromTypeArrays(
                    $info['constants'][$context->getNode()->name->name]['types']
                ))));
            }
        }

        if ($types === []) {
            return new InvalidTypeNode();
        }

        return TypeNodeUnwrapper::unwrap(new UnionTypeNode($types));
    }
}
