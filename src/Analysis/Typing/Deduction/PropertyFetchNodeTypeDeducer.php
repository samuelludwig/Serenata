<?php

namespace Serenata\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PhpParser\Node;
use PhpParser\PrettyPrinterAbstract;

use Serenata\Analysis\Node\PropertyFetchPropertyInfoRetriever;

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
     * @param PropertyFetchPropertyInfoRetriever $propertyFetchPropertyInfoRetriever
     * @param LocalTypeScanner                   $localTypeScanner
     * @param PrettyPrinterAbstract              $prettyPrinter
     */
    public function __construct(
        PropertyFetchPropertyInfoRetriever $propertyFetchPropertyInfoRetriever,
        LocalTypeScanner $localTypeScanner,
        PrettyPrinterAbstract $prettyPrinter
    ) {
        $this->propertyFetchPropertyInfoRetriever = $propertyFetchPropertyInfoRetriever;
        $this->localTypeScanner = $localTypeScanner;
        $this->prettyPrinter = $prettyPrinter;
    }

    /**
     * @inheritDoc
     */
    public function deduce(TypeDeductionContext $context): array
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
            return [];
        }

        $types = [];

        foreach ($infoItems as $info) {
            $fetchedTypes = $this->fetchResolvedTypesFromTypeArrays($info['types']);

            if (!empty($fetchedTypes)) {
                $types += array_combine($fetchedTypes, array_fill(0, count($fetchedTypes), true));
            }
        }

        // We use an associative array so we automatically avoid duplicate types.
        $types = array_keys($types);

        $expressionString = $this->prettyPrinter->prettyPrintExpr($context->getNode());

        $localTypes = $this->localTypeScanner->getLocalExpressionTypes(
            $context->getTextDocumentItem(),
            $context->getPosition(),
            $expressionString,
            $types
        );

        if (!empty($localTypes)) {
            return $localTypes;
        }

        return $types;
    }
}
