<?php

namespace Serenata\Tooltips;

use UnexpectedValueException;

use Serenata\Analysis\Node\PropertyFetchPropertyInfoRetriever;

use Serenata\Common\Position;


use PhpParser\Node;

use Serenata\Utility\TextDocumentItem;

/**
 * Provides tooltips for {@see Node\Expr\PropertyFetch} nodes.
 */
final class PropertyFetchNodeTooltipGenerator
{
    /**
     * @var PropertyFetchPropertyInfoRetriever
     */
    private $propertyFetchPropertyInfoRetriever;

    /**
     * @var PropertyTooltipGenerator
     */
    private $propertyTooltipGenerator;

    /**
     * @param PropertyFetchPropertyInfoRetriever $propertyFetchPropertyInfoRetriever
     * @param PropertyTooltipGenerator           $propertyTooltipGenerator
     */
    public function __construct(
        PropertyFetchPropertyInfoRetriever $propertyFetchPropertyInfoRetriever,
        PropertyTooltipGenerator $propertyTooltipGenerator
    ) {
        $this->propertyFetchPropertyInfoRetriever = $propertyFetchPropertyInfoRetriever;
        $this->propertyTooltipGenerator = $propertyTooltipGenerator;
    }

    /**
     * @param Node\Expr\PropertyFetch $node
     * @param TextDocumentItem        $textDocumentItem
     * @param Position                $position
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    public function generate(
        Node\Expr\PropertyFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): string {
        $infoElements = $this->propertyFetchPropertyInfoRetriever->retrieve($node, $textDocumentItem, $position);

        if (count($infoElements) === 0) {
            throw new UnexpectedValueException('No property fetch information was found for node');
        }

        // Fetch the first tooltip. In theory, multiple tooltips are possible, but we don't support these at the moment.
        $info = array_shift($infoElements);

        return $this->propertyTooltipGenerator->generate($info);
    }
}
