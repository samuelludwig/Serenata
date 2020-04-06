<?php

namespace Serenata\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

use PhpParser\Node;
use PhpParser\PrettyPrinterAbstract;

use Serenata\Analysis\Node\PropertyFetchPropertyInfoRetriever;

use Serenata\Parsing\InvalidTypeNode;
use Serenata\Parsing\TypeNodeUnwrapper;
use Serenata\Parsing\DocblockTypeParserInterface;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\PropertyFetch} node.
 */
final class PropertyFetchNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var PropertyFetchPropertyInfoRetriever
     */
    private $propertyFetchPropertyInfoRetriever;

    /**
     * @var LocalTypeScanner
     */
    private $localTypeScanner;

    /**
     * @var PrettyPrinterAbstract
     */
    private $prettyPrinter;

    /**
     * @var DocblockTypeParserInterface
     */
    private $docblockTypeParser;

    /**
     * @param PropertyFetchPropertyInfoRetriever $propertyFetchPropertyInfoRetriever
     * @param LocalTypeScanner                   $localTypeScanner
     * @param PrettyPrinterAbstract              $prettyPrinter
     * @param DocblockTypeParserInterface        $docblockTypeParser
     */
    public function __construct(
        PropertyFetchPropertyInfoRetriever $propertyFetchPropertyInfoRetriever,
        LocalTypeScanner $localTypeScanner,
        PrettyPrinterAbstract $prettyPrinter,
        DocblockTypeParserInterface $docblockTypeParser
    ) {
        $this->propertyFetchPropertyInfoRetriever = $propertyFetchPropertyInfoRetriever;
        $this->localTypeScanner = $localTypeScanner;
        $this->prettyPrinter = $prettyPrinter;
        $this->docblockTypeParser = $docblockTypeParser;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): TypeNode
    {
        if (!$context->getNode() instanceof Node\Expr\PropertyFetch &&
            !$context->getNode() instanceof Node\Expr\StaticPropertyFetch
        ) {
            throw new TypeDeductionException("Can't handle node of type " . get_class($context->getNode()));
        }

        $infoItems = [];

        try {
            $infoItems = $this->propertyFetchPropertyInfoRetriever->retrieve(
                $context->getNode(),
                $context->getTextDocumentItem(),
                $context->getPosition()
            );
        } catch (UnexpectedValueException $e) {
            return new InvalidTypeNode();
        }

        $types = [];

        foreach ($infoItems as $info) {
            $fetchedTypes = $this->fetchResolvedTypesFromTypeArrays($info['types']);

            if (count($fetchedTypes) > 0) {
                $types += array_combine($fetchedTypes, array_fill(0, count($fetchedTypes), true));
            }
        }

        // We use an associative array so we automatically avoid duplicate types.
        $types = array_keys($types);

        $expressionString = $this->prettyPrinter->prettyPrintExpr($context->getNode());

        $localType = $this->localTypeScanner->getLocalExpressionTypes(
            $context->getTextDocumentItem(),
            $context->getPosition(),
            $expressionString,
            $types
        );

        if (!$localType instanceof InvalidTypeNode) {
            return $localType;
        }

        $types = array_map(function (string $type): TypeNode {
            return $this->docblockTypeParser->parse($type);
        }, $types);

        return TypeNodeUnwrapper::unwrap(new UnionTypeNode($types));
    }
}
