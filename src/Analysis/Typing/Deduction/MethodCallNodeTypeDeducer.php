<?php

namespace Serenata\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

use PhpParser\Node;

use Serenata\Analysis\Node\MethodCallMethodInfoRetriever;

use Serenata\Parsing\InvalidTypeNode;
use Serenata\Parsing\TypeNodeUnwrapper;
use Serenata\Parsing\DocblockTypeParserInterface;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\MethodCall} or a {@see Node\Expr\StaticCall} node.
 */
final class MethodCallNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var MethodCallMethodInfoRetriever
     */
    private $methodCallMethodInfoRetriever;

    /**
     * @var DocblockTypeParserInterface
     */
    private $docblockTypeParser;

    /**
     * @param MethodCallMethodInfoRetriever $methodCallMethodInfoRetriever
     * @param DocblockTypeParserInterface   $docblockTypeParser
     */
    public function __construct(
        MethodCallMethodInfoRetriever $methodCallMethodInfoRetriever,
        DocblockTypeParserInterface $docblockTypeParser
    ) {
        $this->methodCallMethodInfoRetriever = $methodCallMethodInfoRetriever;
        $this->docblockTypeParser = $docblockTypeParser;
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

        $infoItems = null;

        try {
            $infoItems = $this->methodCallMethodInfoRetriever->retrieve(
                $context->getNode(),
                $context->getTextDocumentItem(),
                $context->getPosition()
            );
        } catch (UnexpectedValueException $e) {
            return new InvalidTypeNode();
        }

        $types = [];

        foreach ($infoItems as $info) {
            $types = array_merge($types, $this->fetchResolvedTypesFromTypeArrays($info['returnTypes']));
        }

        $types = array_map(function (string $type): TypeNode {
            return $this->docblockTypeParser->parse($type);
        }, $types);

        return TypeNodeUnwrapper::unwrap(new UnionTypeNode($types));
    }
}
