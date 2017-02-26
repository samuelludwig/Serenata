<?php

namespace PhpIntegrator\Analysis\Typing\Deduction;

use UnexpectedValueException;

use PhpIntegrator\Analysis\PropertyFetchPropertyInfoRetriever;

use PhpParser\Node;
use PhpParser\PrettyPrinterAbstract;

/**
 * Type deducer that can deduce the type of a {@see Node\Expr\PropertyFetch} node.
 */
class PropertyFetchNodeTypeDeducer extends AbstractNodeTypeDeducer
{
    /**
     * @var PropertyFetchPropertyInfoRetriever
     */
    protected $propertyFetchPropertyInfoRetriever;

    /**
     * @var LocalTypeScanner
     */
    protected $localTypeScanner;

    /**
     * @var PrettyPrinterAbstract
     */
    protected $prettyPrinter;

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
    public function deduce(Node $node, ?string $file, string $code, int $offset): array
    {
        if (!$node instanceof Node\Expr\PropertyFetch && !$node instanceof Node\Expr\StaticPropertyFetch) {
            throw new UnexpectedValueException("Can't handle node of type " . get_class($node));
        }

        return $this->deduceTypesFromPropertyFetchNode($node, $file, $code, $offset);
    }

    /**
     * @param Node\Expr\PropertyFetch|Node\Expr\StaticPropertyFetch $node
     * @param string|null                                           $file
     * @param string                                                $code
     * @param int                                                   $offset
     *
     * @return string[]
     */
    protected function deduceTypesFromPropertyFetchNode(
        Node\Expr $node,
        ?string $file,
        string $code,
        int $offset
    ): array {
        $infoItems = [];

        try {
            $infoItems = $this->propertyFetchPropertyInfoRetriever->retrieve($node, $file, $code, $offset);
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

        $expressionString = $this->prettyPrinter->prettyPrintExpr($node);

        $localTypes = $this->localTypeScanner->getLocalExpressionTypes($file, $code, $expressionString, $offset, $types);

        if (!empty($localTypes)) {
            return $localTypes;
        }

        return $types;
    }
}
